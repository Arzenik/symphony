<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;


class CreateTagDTO
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,

        #[Assert\CssColor(
            formats: Assert\CssColor::HEX_LONG,
        )]
        public ?string $color = null,
    ) {}
}
