<?php
declare(strict_types=1);

namespace App\Controller\Podcast;

use App\Controller\BaseController;
use App\Ds2013\PresenterFactory;
use BBC\ProgrammesPagesService\Domain\Entity\Collection;
use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\PodcastsService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use BBC\ProgrammesPagesService\Service\PromotionsService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * Podcast full page. Future implementation.
 *
 * When a user click in podcast panel, it takes you to this full page.
 */
class PodcastController extends BaseController
{
    public function __invoke(
        CoreEntity $coreEntity,
        PodcastsService $podcastsService,
        ProgrammesAggregationService $programmesAggregationService,
        PromotionsService $promotionsService
    ) {
        if (!$coreEntity instanceof CoreEntity && !$coreEntity instanceof Collection) {
            throw new NotFoundHttpException(sprintf('Core Entity with PID "%s" is not a programme or collection', $coreEntity->getPid()));
        }

        if ((!$coreEntity instanceof Collection) && !$coreEntity->isTleo()) {
            return $this->cachedRedirectToRoute('programme_podcast_episodes_download', ['pid' => $coreEntity->getTleo()->getPid()], 301);
        }

        $this->setContextAndPreloadBranding($coreEntity);

        $this->overridenDescription = 'Podcast downloads for ' . $coreEntity->getTitle();
        $podcast = $podcastsService->findByCoreEntity($coreEntity);

        $programme = null;
        if ($coreEntity instanceof Collection) {
            $programme = $coreEntity->getParent();
        } else {
            $programme = $coreEntity;
        }

        $programmeItems = $programmesAggregationService->findStreamableOnDemandEpisodes($coreEntity);

        $promotions = $promotionsService->findActivePromotionsByEntityGroupedByType($coreEntity);

        $genres = $programme->getGenres();
        $genre = reset($genres);
        if ($genre) {
            $genre = $genre->getTopLevel();
        }


        return $this->renderWithChrome('podcast/podcast.html.twig', [
            'programme' => $programme,
            'entity' => $coreEntity,
            'podcast' => $podcast,
            'programmeItems' => $programmeItems,
            'promotions' => $promotions,
            'genre' => $genre,
        ]);
    }
}
