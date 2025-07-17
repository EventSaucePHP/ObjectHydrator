<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use const T_AS;
use const T_CONST;
use const T_FUNCTION;
use const T_NAME_QUALIFIED;
use const T_STRING;
use const T_USE;
use const T_WHITESPACE;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use RuntimeException;
use function array_key_exists;
use function array_shift;
use function assert;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_file;
use function is_string;
use function preg_match;
use function preg_match_all;
use function str_ends_with;
use function strpos;
use function strrpos;
use function substr;
use function token_get_all;
use function trim;

class NaivePropertyTypeResolver implements PropertyTypeResolver
{
    public function typeFromConstructorParameter(
        ReflectionParameter $parameter,
        ReflectionMethod $constructor
    ): PropertyType {
        $type = $parameter->getType();

        if ( ! $type instanceof ReflectionNamedType || $type->getName() !== 'array') {
            return PropertyType::fromReflectionType($type);
        }

        $declaringClass = $constructor->getDeclaringClass();
        $useStatements = $this->resolveUseStatementMap($declaringClass);

        if ($propertyType = $this->resolveFromConstructorDocComment($parameter, $constructor, $useStatements)) {
            return $propertyType;
        }

        return PropertyType::fromReflectionType($type);
    }

    public function typeFromProperty(ReflectionProperty $property, ?ReflectionMethod $constructor): PropertyType
    {
        $propertyType = $property->getType();

        $resolvedType = PropertyType::fromReflectionType($propertyType);
        $concreteType = $resolvedType->firstType();

        if (!$propertyType instanceof ReflectionNamedType || !$concreteType) {
            return $resolvedType;
        }

        if (!$property->isPromoted()) {
            $associative = $this->isAssociativeBasedOnPropertyDocComment($property);
        } elseif ($constructor) {
            $associative = $this->isAssociativeBasedOnConstructorDocComment($property, $constructor);
        } else {
            $associative = false;
        }

        $concreteType->associative = $associative;

        return $resolvedType;
    }

    public function typeFromMethod(ReflectionMethod $method): PropertyType
    {
        $returnType = $method->getReturnType();

        $resolvedType = PropertyType::fromReflectionType($returnType);
        $concreteType = $resolvedType->firstType();

        if (!$returnType instanceof ReflectionNamedType || !$concreteType) {
            return $resolvedType;
        }

        $concreteType->associative = $this->isAssociativeBasedOnReturnDocComment($method);

        return $resolvedType;
    }

    private function resolveUseStatementMap(ReflectionClass $declaringClass): array
    {
        static $cache = [];
        $className = $declaringClass->name;

        if (array_key_exists($className, $cache)) {
            return $cache[$className];
        }

        $fileName = $declaringClass->getFileName();

        if ( ! is_string($fileName) || ! is_file($fileName)) {
            throw new RuntimeException("No filename available for class $className");
        }

        $phpCode = file_get_contents($fileName) ?: throw new RuntimeException('Unable to read source file: ' . $fileName);
        $useMap = [];
        $tokens = token_get_all($phpCode);

        while ($token = array_shift($tokens)) {
            if ( ! is_array($token) || ($token[0] ?? 0) !== T_USE) {
                continue;
            }

            $token = $this->tokenAfterWhitespace($tokens);

            if ( ! is_array($token)) {
                continue;
            }

            if ($token[0] === T_FUNCTION || $token[0] === T_CONST) {
                continue;
            }

            assert(in_array($token[0], [T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, T_STRING]));
            $fqcn = trim($token[1], '\\');
            $token = $this->tokenAfterWhitespace($tokens);

            if ($token === ';') {
                $useMap[$this->fqcnToClassName($fqcn)] = $fqcn;
            } elseif (is_array($token) && $token[0] === T_AS) {
                $token = $this->tokenAfterWhitespace($tokens);
                $useMap[$token[1]] = $fqcn;
            }
        }

        return $cache[$className] = $useMap;
    }

    private function tokenAfterWhitespace(array &$tokens): array|string|null
    {
        start:
        $current = $tokens[0] ?? false;

        if (is_array($current) && $current[0] === T_WHITESPACE) {
            array_shift($tokens);
            goto start;
        }

        return array_shift($tokens);
    }

    private function fqcnToClassName(string $fqcn): string
    {
        return substr($fqcn, strrpos($fqcn, '\\') + 1);
    }

    private function resolveFromConstructorDocComment(
        ReflectionParameter $parameter,
        ReflectionMethod $constructor,
        array $useMap,
    ): false|PropertyType {
        $fqcn = $constructor->getDeclaringClass()->name;
        $namespace = substr($fqcn, 0, (int) strrpos($fqcn, '\\'));
        $docBlock = $constructor->getDocComment();

        if ($docBlock === false) {
            return false;
        }

        $result = (int) preg_match_all(
            '/\*\s+@param\s+([A-Za-z0-9\\\[\]<>\s,]+)\s+\$([A-Za-z_0-9]+)/m',
            $docBlock,
            $matches,
            PREG_SET_ORDER
        );

        if ($result === 0) {
            return false;
        }

        $parameterType = NULL;

        foreach ($matches as [, $type, $paramName]) {
            if ($paramName !== $parameter->name) {
                continue;
            }

            $type = $this->extractItemType(trim($type));

            if (in_array(ltrim($type, '\\'), ['bool', 'boolean', 'int', 'integer', 'float', 'double', 'string', 'array', 'object', 'null', 'mixed'])) {
                continue;
            }

            if (class_exists($type)) {
                $parameterType = $type;
                goto found;
            }

            $base = $type;
            $separatorPosition = strpos($base, '\\');

            if ($separatorPosition !== false) {
                $base = substr($base, 0, $separatorPosition);
            }

            if (array_key_exists($base, $useMap)) {
                $parameterType = $useMap[$base];
            } else {
                $parameterType = ltrim($namespace . '\\' . $base, '\\');
            }
        }

        if ( ! $parameterType) {
            return false;
        }

        found:
        $reflectionClass = new ReflectionClass($parameterType);

        return PropertyType::collectionContaining($reflectionClass);
    }

    private function extractItemType(string $type): string
    {
        if ($type === 'array') {
            return $type;
        }

        if (str_ends_with($type, '[]')) {
            return substr($type, 0, -2);
        }

        if (preg_match('/(?:array|list)<(?:(int(?:eger)?|string|mixed),\s*)?([A-Za-z0-9\\\]+)>/', $type, $matches)) {
            return $matches[2];
        }

        throw new LogicException('Unable to resolve item type for type: ' . $type);
    }

    private function isAssociativeBasedOnConstructorDocComment(ReflectionProperty $parameter, ReflectionMethod $constructor): bool
    {
        $docBlock = $constructor->getDocComment();
        if (!$docBlock) {
            return false;
        }

        return (bool) preg_match(
            '/\*\s+@param\s+[^$]*array<\s*string\s*,\s*[^>]+\s*>[^$]*\$' . preg_quote($parameter->name, '/') . '\b/m',
            $docBlock
        );
    }

    private function isAssociativeBasedOnPropertyDocComment(ReflectionProperty $property): bool
    {
        $docBlock = $property->getDocComment();
        if (!$docBlock) {
            return false;
        }

        return (bool) preg_match('/\*\s+@var\s+[^*]*?\barray\s*<\s*string\s*,\s*[^>]+\s*>/m', $docBlock);
    }

    private function isAssociativeBasedOnReturnDocComment(ReflectionMethod $method): bool
    {
        $docBlock = $method->getDocComment();
        if (!$docBlock) {
            return false;
        }

        return (bool) preg_match('/\*\s+@return\s+[^*]*?\barray\s*<\s*string\s*,\s*[^>]+\s*>/m', $docBlock);
    }
}
