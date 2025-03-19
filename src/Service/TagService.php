<?php

namespace App\Service;

use App\DTO\CreateTagDTO;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

class TagService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TagRepository $tagRepository,
    ) {}
    public function createTag(CreateTagDTO $dto): Tag
    {
        $tag = new Tag();
        $tag->setName($dto->name);
        $tag->setColor($dto->color);
        $this->em->persist($tag);
        $this->em->flush();
        return $tag;
    }
}
