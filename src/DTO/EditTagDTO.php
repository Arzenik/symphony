<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class EditTagDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public readonly string $name,

        #[Assert\NotBlank]
        #[Assert\Regex('/^#[0-9A-Fa-f]{6}$/')]
        public readonly string $color,
    ) {
    }
} 