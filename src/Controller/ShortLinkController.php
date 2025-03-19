<?php

namespace App\Controller;

use App\DTO\CreateShortLinkDTO;
use App\Service\ShortLinkService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class ShortLinkController extends AbstractController
{
    #[Route('/short-links', methods: ['POST'], name: 'create_short_link')]
    public function create(
        #[MapRequestPayload]
        CreateShortLinkDTO $dto,
        ShortLinkService $shortLinkService,
    ): Response {
        $shortLink = $shortLinkService->createShortLink($dto);

        return $this->json($shortLink, status: Response::HTTP_CREATED);
    }
}
