<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

use function array_filter;
use function array_push;
use function array_values;
use function count;
use function file_get_contents;
use function in_array;
use function is_a;
use function is_array;
use function str_ends_with;
use function token_get_all;
use function trim;

use const T_CLASS;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_NAMESPACE;
use const T_NEW;
use const T_WHITESPACE;
use const TOKEN_PARSE;

/**
 * @internal
 */
class ClassFinder
{
    public function __construct(private array $classes)
    {
    }

    /**
     * @param class-string ...$interfaces
     */
    public function thatImplement(string ...$interfaces): static
    {
        $classes = array_filter($this->classes, function (string $class) use ($interfaces) {
            foreach ($interfaces as $type) {
                if (is_a($class, $type, true)) {
                    return true;
                }
            }

            return false;
        });

        return new static($classes);
    }

    /**
     * @param class-string ...$attributes
     */
    public function thatHasAttribute(string ...$attributes): static
    {
        $classes = array_filter($this->classes, function (string $class) use ($attributes) {
            $reflectionClass = new ReflectionClass($class);

            foreach ($attributes as $attribute) {
                $attributes = $reflectionClass->getAttributes($attribute);

                if (count($attributes) > 0) {
                    return true;
                }
            }

            return false;
        });

        return new static($classes);
    }

    public static function fromDirectory(string $directory): static
    {
        $classes = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ( ! $file->isFile()) {
                continue;
            }

            $realPath = $file->getRealPath();

            if ( ! str_ends_with($realPath, '.php')) {
                continue;
            }

            $source = file_get_contents($realPath) ?: '';
            array_push($classes, ...static::findClasses($source));
        }

        return new static($classes);
    }

    private static function findClasses(string $source): array
    {
        $classes = [];
        $tokens = token_get_all($source, TOKEN_PARSE);
        $tokens = array_filter(
            $tokens,
            fn(array|string $token) => ! in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE]),
        );
        $tokens = array_values($tokens);

        $namespace = '';

        foreach ($tokens as $index => $token) {
            if ( ! is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = static::collectNamespace($index + 1, $tokens);
            }

            if ($token[0] !== T_CLASS || static::isNew($index - 1, $tokens)) {
                continue;
            }

            $classToken = $tokens[$index + 1] ?? '';

            if ( ! is_array($classToken)) {
                continue;
            }

            $classes[] = trim("$namespace\\$classToken[1]", '\\');
        }

        return $classes;
    }

    private static function collectNamespace(int $index, array $tokens): string
    {
        $token = $tokens[$index] ?? '';

        if ( ! is_array($token)) {
            return '';
        }

        if ($token[0] === T_NAME_QUALIFIED) {
            return (string) $token[1];
        }

        return '';
    }

    private static function isNew(int $index, array $tokens): bool
    {
        $token = $tokens[$index] ?? '';

        if ( ! is_array($token)) {
            return false;
        }

        $type = $token[0] ?? '';

        return $type === T_NEW;
    }

    public function classes(): array
    {
        return array_values($this->classes);
    }
}
