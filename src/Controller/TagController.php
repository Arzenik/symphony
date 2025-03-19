<?php

namespace App\Controller;

use App\DTO\CreateTagDTO;
use App\Entity\Tag;
use App\Service\TagService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class TagController extends AbstractController
{
    #[Route('/tags', methods: ['POST'], name: 'create_tag')]
    public function create(
        #[MapRequestPayload]
        CreateTagDTO $dto,
        TagService $tagService,
    ): Response {
        $tag = $tagService->createTag($dto);
        return $this->json(status: Response::HTTP_CREATED, data: $tag,);
    }

    #[Route('/tags/{id}', methods: ['DELETE'], name: 'delete_tag')]
    public function delete(Tag $tag): Response
    {
        dd($tag);
        return $this->json(
            status: Response::HTTP_NO_CONTENT,
            data: ['message' => 'Tag supprimé avec succès']
        );
    }
}
