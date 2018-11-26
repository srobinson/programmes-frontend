<?php

namespace Tests\App\DataFixtures\ORM\Clips;

use BBC\ProgrammesPagesService\Data\ProgrammesDb\Entity\Clip;
use BBC\ProgrammesPagesService\Data\ProgrammesDb\Entity\Episode;
use BBC\ProgrammesPagesService\Data\ProgrammesDb\Entity\Programme;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Tests\App\DataFixtures\ORM\ProgrammeEpisodes\BrandFixtures;
use Tests\App\DataFixtures\ORM\ProgrammeEpisodes\EpisodesFixtures;

class ClipsListFixture extends AbstractFixture implements DependentFixtureInterface
{
    /** @var ObjectManager */
    private $manager;

    public function getDependencies()
    {
        return [
            BrandFixtures::class,
        ];
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $brand = $this->getReference('b006q2x0');
        $this->buildClip('c1000001', 'This is clip 1', $brand);
        $this->buildClip('c1000002', 'This is clip 2', $brand);
        $this->buildClip('c1000003', 'This is clip 3', $brand);
        $this->buildClip('c1000004', 'This is clip 4', $brand);
        $this->buildClip('c1000005', 'This is clip 5', $brand);
        $this->buildClip('c1000006', 'This is clip 6', $brand);

        $episode = new Episode('p1000001', 'Parent-less episode');
        $this->manager->persist($episode);

        $this->buildClip(
            'c2000001',
            'This is a clip with tleo episode',
            $episode
        );

        $this->manager->flush();
    }

    private function buildClip(
        string $pid,
        string $title,
        Programme $parent
    ) {
        $clip = new Clip($pid, $title);
        $clip->setStreamable(true);
        $clip->setDuration(60);
        $clip->setParent($parent);

        $this->manager->persist($clip);
        $this->addReference($pid, $clip);
    }
}
