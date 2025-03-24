<?php

namespace App\Service;

use App\DTO\CreateTagDTO;
use App\DTO\EditTagDTO;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TagService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TagRepository $tagRepository,
    ) {}

    public function getTags(): array
    {
        return $this->tagRepository->findAll();
    }

    public function createTag(CreateTagDTO $dto): Tag
    {
        $tag = new Tag();
        $tag
            ->setName($dto->name)
            ->setColor($dto->color);

        $this->em->persist($tag);
        $this->em->flush();

        return $tag;
    }

    public function getTag(int $id): Tag
    {
        $tag = $this->tagRepository->find($id);
        
        if (!$tag) {
            throw new NotFoundHttpException('Tag not found');
        }

        return $tag;
    }

    public function editTag(int $id, EditTagDTO $dto): Tag
    {
        $tag = $this->tagRepository->find($id);
        
        if (!$tag) {
            throw new NotFoundHttpException('Tag not found');
        }

        $tag
            ->setName($dto->name)
            ->setColor($dto->color);

        $this->em->flush();

        return $tag;
    }

    public function deleteTag(int $id): void
    {
        $tag = $this->tagRepository->find($id);
        
        if (!$tag) {
            throw new NotFoundHttpException('Tag not found');
        }

        $this->em->remove($tag);
        $this->em->flush();
    }
}
