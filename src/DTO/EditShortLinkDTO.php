<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class EditShortLinkDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Url]
        public readonly string $url,

        #[Assert\Length(min: 2)]
        public readonly ?string $shortCode = null,

        public readonly ?string $title = null,

        #[Assert\PositiveOrZero]
        public readonly ?int $maxVisits = null,

        #[Assert\Type("array")]
        public readonly array $tags = [],

        public readonly ?\DateTimeImmutable $validOn = null,

        public readonly ?\DateTimeImmutable $expiresAt = null,
    ) {
    }
} 