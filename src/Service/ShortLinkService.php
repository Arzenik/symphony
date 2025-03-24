<?php

namespace App\Service;

use Faker;
use App\DTO\CreateShortLinkDTO;
use App\DTO\EditShortLinkDTO;
use App\DTO\GetShortLinksDTO;
use App\Entity\ShortLink;
use App\Entity\Visit;
use App\Repository\ShortLinkRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ShortLinkService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ShortLinkRepository $shortLinkRepository,
        private TagRepository $tagRepository,
    ) {}

    public function createShortLink(CreateShortLinkDTO $dto): ShortLink
    {
        $shortLink = new ShortLink();
        $shortLink->setUrl($dto->url);
        if ($dto->shortCode === null) {
            $shortLink->setShortCode($this->generateShortLink());
        } else {
            // Make sure it doesn't already exist, otherwise throw 409
            // throw new HttpException(Response::HTTP_CONFLICT, 'Short code already exists');
            $shortLink->setShortCode($dto->shortCode);
        }

        $shortLink
            ->setMaxVisits($dto->maxVisits)
            ->setValidOn($dto->validOn)
            ->setExpiresAt($dto->expiresAt);

        $this->em->persist($shortLink);
        $this->em->flush();

        return $shortLink;
    }

    public function editShortLink(string $shortCode, EditShortLinkDTO $dto): ShortLink
    {
        $shortLink = $this->shortLinkRepository->findOneBy(['shortCode' => $shortCode]);
        
        if (!$shortLink) {
            throw new NotFoundHttpException('Short link not found');
        }

        $shortLink
            ->setUrl($dto->url)
            ->setTitle($dto->title)
            ->setMaxVisits($dto->maxVisits)
            ->setValidOn($dto->validOn)
            ->setExpiresAt($dto->expiresAt);

        if ($dto->shortCode !== null && $dto->shortCode !== $shortCode) {
            // Vérifier si le nouveau shortCode n'existe pas déjà pour un autre lien
            $existingLink = $this->shortLinkRepository->findOneBy(['shortCode' => $dto->shortCode]);
            if ($existingLink !== null && $existingLink->getId() !== $shortLink->getId()) {
                throw new HttpException(Response::HTTP_CONFLICT, 'Short code already exists');
            }
            $shortLink->setShortCode($dto->shortCode);
        }

        // Mise à jour des tags
        $shortLink->getTags()->clear();
        foreach ($dto->tags as $tagData) {
            if (isset($tagData['id'])) {
                $tag = $this->tagRepository->find($tagData['id']);
                if ($tag) {
                    $shortLink->addTag($tag);
                }
            }
        }

        $this->em->flush();

        return $shortLink;
    }

    public function generateShortLink(): string
    {
        $faker = Faker\Factory::create();
        $code = $faker->regexify('[A-Za-z0-9]{8}');
        // Check that code doesn't already exist
        // If it does, generate a new one
        return $code;
    }

    public function getShortLinks(GetShortLinksDTO $dto): array
    {
        $qb = $this->shortLinkRepository->createQueryBuilder('s');

        // Appliquer le tri
        $qb->orderBy('s.' . $dto->sortBy, $dto->orderBy);

        // Filtrer par tags si spécifié
        if (!empty($dto->tags)) {
            $qb->join('s.tags', 't')
               ->andWhere('t.id IN (:tags)')
               ->setParameter('tags', $dto->tags);
        }

        // Pagination
        $qb->setFirstResult(($dto->page - 1) * $dto->limit)
           ->setMaxResults($dto->limit);

        // Utiliser Paginator pour obtenir le nombre total
        $paginator = new Paginator($qb);
        $total = count($paginator);

        return [
            'total' => $total,
            'maxPage' => ceil($total / $dto->limit),
            'limit' => $dto->limit,
            'page' => $dto->page,
            'results' => $paginator->getIterator()->getArrayCopy(),
        ];
    }

    public function getShortLink(string $id): ShortLink
    {
        $shortLink = $this->shortLinkRepository->find($id);
        
        if (!$shortLink) {
            throw new NotFoundHttpException('Short link not found');
        }

        return $shortLink;
    }

    public function getShortLinkByCode(string $shortCode): ShortLink
    {
        $shortLink = $this->shortLinkRepository->findOneBy(['shortCode' => $shortCode]);
        
        if (!$shortLink) {
            throw new NotFoundHttpException('Short link not found');
        }

        return $shortLink;
    }

    public function processVisit(string $shortCode, string $ip, string $userAgent): ShortLink
    {
        $shortLink = $this->shortLinkRepository->findOneBy(['shortCode' => $shortCode]);
        
        if (!$shortLink) {
            throw new NotFoundHttpException('Short link not found');
        }

        // Vérifier si le lien est valide (date de validité)
        if ($shortLink->getValidOn() !== null && $shortLink->getValidOn() > new \DateTimeImmutable()) {
            throw new BadRequestHttpException('This link is not yet valid');
        }

        // Vérifier si le lien n'a pas expiré
        if ($shortLink->getExpiresAt() !== null && $shortLink->getExpiresAt() < new \DateTimeImmutable()) {
            throw new BadRequestHttpException('This link has expired');
        }

        // Vérifier le nombre maximum de visites
        if ($shortLink->getMaxVisits() !== null) {
            $visitCount = $this->em->getRepository(Visit::class)->count(['shortLink' => $shortLink]);
            if ($visitCount >= $shortLink->getMaxVisits()) {
                throw new BadRequestHttpException('Maximum number of visits reached');
            }
        }

        // Enregistrer la visite
        $visit = new Visit();
        $visit
            ->setShortLink($shortLink)
            ->setIp($ip)
            ->setUserAgent($userAgent)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($visit);
        $this->em->flush();

        return $shortLink;
    }

    public function deleteShortLink(string $shortCode): void
    {
        $shortLink = $this->shortLinkRepository->findOneBy(['shortCode' => $shortCode]);
        
        if (!$shortLink) {
            throw new NotFoundHttpException('Short link not found');
        }

        // Supprimer les relations avec les tags
        $shortLink->getTags()->clear();

        // Supprimer les visites associées
        $this->em->createQuery('DELETE FROM App\Entity\Visit v WHERE v.shortLink = :shortLink')
            ->setParameter('shortLink', $shortLink)
            ->execute();

        // Puis supprimer le lien court
        $this->em->remove($shortLink);
        $this->em->flush();
    }

    public function getVisits(string $shortCode): array
    {
        $shortLink = $this->shortLinkRepository->findOneBy(['shortCode' => $shortCode]);
        
        if (!$shortLink) {
            throw new NotFoundHttpException('Short link not found');
        }

        $visits = $this->em->getRepository(Visit::class)->findBy(
            ['shortLink' => $shortLink],
            ['createdAt' => 'DESC']
        );

        $totalVisits = count($visits);
        $visitsPerDay = [];
        $lastVisits = [];

        foreach ($visits as $visit) {
            // Grouper par jour pour les statistiques
            $date = $visit->getCreatedAt()->format('Y-m-d');
            if (!isset($visitsPerDay[$date])) {
                $visitsPerDay[$date] = 0;
            }
            $visitsPerDay[$date]++;

            // Collecter les 10 dernières visites avec détails
            if (count($lastVisits) < 10) {
                $lastVisits[] = [
                    'id' => $visit->getId(),
                    'createdAt' => $visit->getCreatedAt(),
                    'ip' => $visit->getIp(),
                    'userAgent' => $visit->getUserAgent(),
                ];
            }
        }

        // Trier les visites par jour
        krsort($visitsPerDay);

        return [
            'shortCode' => $shortCode,
            'url' => $shortLink->getUrl(),
            'title' => $shortLink->getTitle(),
            'statistics' => [
                'totalVisits' => $totalVisits,
                'visitsPerDay' => $visitsPerDay,
            ],
            'lastVisits' => $lastVisits,
            'maxVisits' => $shortLink->getMaxVisits(),
            'remainingVisits' => $shortLink->getMaxVisits() ? ($shortLink->getMaxVisits() - $totalVisits) : null,
        ];
    }
}
