<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class GetShortLinksDTO
{
    public function __construct(
        #[Assert\Positive]
        public readonly ?int $limit = 20,

        #[Assert\Choice(['id', 'desc'])]
        public readonly ?string $orderBy = 'desc',

        #[Assert\PositiveOrZero]
        public readonly ?int $page = 1,

        #[Assert\Choice(['createdAt', 'updatedAt', 'shortCode', 'visits'])]
        public readonly ?string $sortBy = 'createdAt',

        #[Assert\Type('array')]
        public readonly array $tags = [],
    ) {
    }
} 