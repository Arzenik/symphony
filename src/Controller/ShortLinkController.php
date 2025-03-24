<?php

namespace App\Controller;

use App\DTO\CreateShortLinkDTO;
use App\DTO\EditShortLinkDTO;
use App\DTO\GetShortLinksDTO;
use App\Service\ShortLinkService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

    #[Route('/short-links/{shortCode}', methods: ['PUT'], name: 'edit_short_link')]
    public function edit(
        string $shortCode,
        #[MapRequestPayload]
        EditShortLinkDTO $dto,
        ShortLinkService $shortLinkService,
    ): Response {
        $shortLink = $shortLinkService->editShortLink($shortCode, $dto);

        return $this->json($shortLink);
    }

    #[Route('/short-links', methods: ['GET'], name: 'get_short_links')]
    public function list(
        Request $request,
        ShortLinkService $shortLinkService,
    ): Response {
        $dto = new GetShortLinksDTO(
            limit: $request->query->getInt('limit', 20),
            orderBy: $request->query->get('orderBy', 'desc'),
            page: $request->query->getInt('page', 1),
            sortBy: $request->query->get('sortBy', 'createdAt'),
            tags: $request->query->all('tags')
        );

        $result = $shortLinkService->getShortLinks($dto);

        return $this->json($result);
    }

    #[Route('/short-links/{shortCode}', methods: ['GET'], name: 'get_short_link')]
    public function get(
        string $shortCode,
        ShortLinkService $shortLinkService,
    ): Response {
        $shortLink = $shortLinkService->getShortLinkByCode($shortCode);

        return $this->json($shortLink);
    }

    #[Route('/r/{shortCode}', methods: ['GET'], name: 'redirect_short_link')]
    public function redirectToUrl(
        string $shortCode,
        Request $request,
        ShortLinkService $shortLinkService,
    ): Response {
        $shortLink = $shortLinkService->processVisit(
            shortCode: $shortCode,
            ip: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent', '')
        );

        return new RedirectResponse(
            url: $shortLink->getUrl(),
            status: Response::HTTP_MOVED_PERMANENTLY,
            headers: ['Cache-Control' => 'no-store']
        );
    }

    #[Route('/short-links/{shortCode}', methods: ['DELETE'], name: 'delete_short_link')]
    public function delete(
        string $shortCode,
        ShortLinkService $shortLinkService,
    ): Response {
        $shortLinkService->deleteShortLink($shortCode);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/short-links/{shortCode}/visits', methods: ['GET'], name: 'get_short_link_visits')]
    public function getVisits(
        string $shortCode,
        ShortLinkService $shortLinkService,
    ): Response {
        $visits = $shortLinkService->getVisits($shortCode);
        return $this->json($visits);
    }
}
