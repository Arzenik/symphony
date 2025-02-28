<?php

namespace App\DataFixtures;

use App\DataFixtures\ShortLinkFixtures;
use App\DataFixtures\TagFixtures;
use App\Entity\ShortLink;
use App\Entity\Tag;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ShortLinkTagFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /**
         * @var ShortLink[]
         */
        $links = $manager->getRepository(ShortLink::class)->findAll();
        /**
         * @var Tag[]
         */
        $tags = $manager->getRepository(Tag::class)->findAll();

        foreach ($links as $link) {
            $tagCount = $this->faker->numberBetween(0, 3);
            $selectedTags = $this->faker->randomElements($tags, $tagCount);
            foreach ($selectedTags as $tag) {
                $link->addTag($tag);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ShortLinkFixtures::class,
            TagFixtures::class,
        ];
    }
}
