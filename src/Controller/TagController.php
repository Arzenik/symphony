<?php

namespace App\Controller;

use App\DTO\CreateTagDTO;
use App\DTO\EditTagDTO;
use App\Service\TagService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class TagController extends AbstractController
{
    #[Route('/tags', methods: ['GET'], name: 'get_tags')]
    public function list(TagService $tagService): Response
    {
        $tags = $tagService->getTags();
        return $this->json($tags);
    }

    #[Route('/tags', methods: ['POST'], name: 'create_tag')]
    public function create(
        #[MapRequestPayload]
        CreateTagDTO $dto,
        TagService $tagService,
    ): Response {
        $tag = $tagService->createTag($dto);
        return $this->json($tag, Response::HTTP_CREATED);
    }

    #[Route('/tags/{id}', methods: ['GET'], name: 'get_tag')]
    public function get(
        int $id,
        TagService $tagService,
    ): Response {
        $tag = $tagService->getTag($id);
        return $this->json($tag);
    }

    #[Route('/tags/{id}', methods: ['PUT'], name: 'edit_tag')]
    public function edit(
        int $id,
        #[MapRequestPayload]
        EditTagDTO $dto,
        TagService $tagService,
    ): Response {
        $tag = $tagService->editTag($id, $dto);
        return $this->json($tag);
    }

    #[Route('/tags/{id}', methods: ['DELETE'], name: 'delete_tag')]
    public function delete(
        int $id,
        TagService $tagService,
    ): Response {
        $tagService->deleteTag($id);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
