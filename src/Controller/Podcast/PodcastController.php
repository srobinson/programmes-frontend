<?php
declare(strict_types=1);

namespace App\Controller\Podcast;

use App\Controller\BaseController;
use App\Ds2013\PresenterFactory;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;
use BBC\ProgrammesPagesService\Domain\Entity\Collection;
use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\PodcastsService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use BBC\ProgrammesPagesService\Service\PromotionsService;
use BBC\ProgrammesPagesService\Service\VersionsService;
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
        PromotionsService $promotionsService,
        VersionsService $versionsService
    ) {
        if (!$coreEntity instanceof CoreEntity && !$coreEntity instanceof Collection) {
            throw new NotFoundHttpException(sprintf('Core Entity with PID "%s" is not a programme or collection', $coreEntity->getPid()));
        }

        if ((!$coreEntity instanceof Collection) && !$coreEntity->isTleo()) {
            return $this->cachedRedirectToRoute('programme_podcast_episodes_download', ['pid' => $coreEntity->getTleo()->getPid()], 301);
        }

        $this->setContextAndPreloadBranding($coreEntity);
        $this->setAtiContentId((string) $coreEntity->getPid(), 'pips');

        $this->overridenDescription = 'Podcast downloads for ' . $coreEntity->getTitle();
        $podcast = $podcastsService->findByCoreEntity($coreEntity);

        $page = $this->getPage();
        $limit = 30;

        $programme = null;
        if ($coreEntity instanceof Collection) {
            $programme = $coreEntity->getParent();
            $downloadableVersions = $versionsService->findDownloadableForGroupsDescendantEpisodes($coreEntity, $limit, $page);
            $downloadableEpisodesCount = $versionsService->countDownloadableForProgrammesDescendantEpisodes($coreEntity);
        } else {
            $programme = $coreEntity;
            $downloadableVersions = $versionsService->findDownloadableForProgrammesDescendantEpisodes($coreEntity, $limit, $page);
            $downloadableEpisodesCount = $versionsService->countDownloadableForProgrammesDescendantEpisodes($coreEntity);
        }

        $paginator = null;

        if ($downloadableEpisodesCount > $limit) {
            $paginator = new PaginatorPresenter($page, $limit, $downloadableEpisodesCount);
        }

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
            'downloadableVersions' => $downloadableVersions,
            'paginatorPresenter' => $paginator,
            'promotions' => $promotions,
            'genre' => $genre,
        ]);
    }
}
