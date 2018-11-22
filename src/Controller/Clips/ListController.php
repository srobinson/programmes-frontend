<?php

namespace App\Controller\Clips;

use App\Controller\BaseController;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;
use BBC\ProgrammesPagesService\Domain\Entity\Brand;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use BBC\ProgrammesPagesService\Service\AbstractService;
use BBC\ProgrammesCachingLibrary\CacheInterface;

class ListController extends BaseController
{
    private const ISTATS_PAGE_ID = 'programme_clips';
    private const DISPLAYED_ITEMS_LIMIT = 24;
    private const PARAMS_PROGRAMME_KEY = 'programme';
    private const PARAMS_SERIES_KEY = 'series';
    private const PARAMS_CLIPS_KEY = 'clips';
    private const PARAMS_CLIPS_COUNT_KEY = 'clipsCount';
    private const PARAMS_PAGINATOR_PRESENTER_KEY = 'paginatorPresenter';
    private const PARAMS_SHOW_ALL_PID_KEY = 'showAllPid';

    public function __invoke(
        Programme $programme,
        ProgrammesAggregationService $aggregationService,
        ProgrammesService $programmesService
    ) {
        $this->setIstatsProgsPageType(self::ISTATS_PAGE_ID);
        $this->setContextAndPreloadBranding($programme);

        $page = $this->getPage();

        $clips = $aggregationService->findStreamableDescendantClips(
            $programme,
            self::DISPLAYED_ITEMS_LIMIT,
            $page
        );

        $clipsCount = $aggregationService->countStreamableDescendantClips($programme);

        $series = $programmesService->findChildrenSeriesByParent(
            $programme->getTleo(),
            AbstractService::DEFAULT_LIMIT,
            AbstractService::DEFAULT_PAGE,
            CacheInterface::NORMAL,
            true
        );

        $parameters = [
            self::PARAMS_PROGRAMME_KEY => $programme,
            self::PARAMS_SERIES_KEY => $series,
            self::PARAMS_CLIPS_KEY => $clips,
            self::PARAMS_CLIPS_COUNT_KEY => $clipsCount,
        ];
        if ($clipsCount > self::DISPLAYED_ITEMS_LIMIT) {
            $parameters[self::PARAMS_PAGINATOR_PRESENTER_KEY] = new PaginatorPresenter(
                $page,
                self::DISPLAYED_ITEMS_LIMIT,
                $clipsCount
            );
        }
        if (!$programme->isTleo() && $programme->getTleo() instanceof Programme) {
            $parameters[self::PARAMS_SHOW_ALL_PID_KEY] = $programme->getTleo()->getPid();
        }

        return $this->renderWithChrome('clips/list.html.twig', $parameters);
    }
}
