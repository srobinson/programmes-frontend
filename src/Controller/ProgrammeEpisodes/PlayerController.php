<?php
declare(strict_types = 1);

namespace App\Controller\ProgrammeEpisodes;

use App\Controller\BaseController;
use App\Ds2013\PresenterFactory;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;
use BBC\ProgrammesCachingLibrary\CacheInterface;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;

class PlayerController extends BaseController
{
    public function __invoke(
        CollapsedBroadcastsService $collapsedBroadcastService,
        PresenterFactory $presenterFactory,
        ProgrammeContainer $programme,
        ProgrammesAggregationService $programmeAggregationService
    ) {
        $this->setContextAndPreloadBranding($programme);
        $page = $this->getPage();
        $limit = 10;

        $availableEpisodes = $programmeAggregationService->findStreamableOnDemandEpisodes(
            $programme,
            $limit,
            $page
        );

        // If you visit an out-of-bounds page then throw a 404. Page one should
        // always be a 200 so search engines don't drop their reference to the
        // page while a programme is off-air
        if (!$availableEpisodes && $page !== 1) {
            throw $this->createNotFoundException('Page does not exist');
        }

        $upcomingBroadcastCount = $collapsedBroadcastService->countUpcomingByProgramme($programme, CacheInterface::MEDIUM);
        $totalAvailableEpisodes = $programmeAggregationService->countStreamableOnDemandEpisodes($programme);

        $paginator = null;
        if ($totalAvailableEpisodes > $limit) {
            $paginator = new PaginatorPresenter($page, $limit, $totalAvailableEpisodes);
        }

        $subNavPresenter = $presenterFactory->episodesSubNavPresenter(
            $this->request()->attributes->get('_route'),
            $programme->getNetwork() === null || !$programme->getNetwork()->isInternational(),
            $programme->getFirstBroadcastDate() !== null,
            $totalAvailableEpisodes,
            $programme->getPid(),
            $upcomingBroadcastCount
        );

        return $this->renderWithChrome('programme_episodes/player.html.twig', [
            'programme' => $programme,
            'availableEpisodes' => $availableEpisodes,
            'paginatorPresenter' => $paginator,
            'subNavPresenter' => $subNavPresenter,
        ]);
    }
}
