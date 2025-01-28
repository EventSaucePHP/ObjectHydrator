<?php

namespace EventSauce\ObjectHydrator\Fixtures\CastToExpectedType;

final class ClassWithIds
{
    public function __construct(
        #[IdCaster]
        public AuthorId $authorId,
        #[IdCaster]
        public BlogId $blogId,
    )
    {
    }
}
