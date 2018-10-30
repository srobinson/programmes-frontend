<?php
declare(strict_types = 1);
namespace App\Controller;

use App\ExternalApi\RecEng\Service\RecEngService;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use GuzzleHttp\Promise\FulfilledPromise;

class RecommendationsController extends BaseController
{
    public function __invoke(
        Programme $programme,
        string $extension,
        RecEngService $recEngService,
        ProgrammesAggregationService $programmeAggregationService
    ) {
        $this->setContextAndPreloadBranding($programme);

        // Show 10 items when rendering the main page, show 2 on includes
        $limit = ($extension === '' ? 10 : 2);

        $episode = $this->getEpisode($programme, $programmeAggregationService);

        $promises = [
            'recommendations' => new FulfilledPromise([]),
        ];
        if ($episode) {
            $promises['recommendations'] = $recEngService->getRecommendations($episode, $limit);
        }

        if ($extension === '') {
            // We haven't got around to rendering the main route yet,
            // replace this with a call to $this->renderWithChrome later.
            throw $this->createNotFoundException('No such page');
        }

        return $this->renderWithoutChrome(
            'recommendations/show' . $extension . '.html.twig',
            array_merge($this->resolvePromises($promises), [
                'programme' => $programme,
            ])
        );
    }

    /**
     * recEng requires an Episode pid, so this determines which to use based on the type of Programme passed into it
     * Takes nullable args of latest, upcoming and last on episodes as this is called in TLEC controller and these are already fetched
     */
    private function getEpisode(
        Programme $programme,
        ProgrammesAggregationService $programmeAggregationService
    ): ?Episode {
        if ($programme instanceof ProgrammeContainer && $programme->getAvailableEpisodesCount()) {
            $onDemandEpisodes = $programmeAggregationService->findStreamableOnDemandEpisodes($programme, 1);
            if (!empty($onDemandEpisodes)) {
                // Theoretically if getAvailableEpisodesCount returns > 0, then we should have onDemandEpisodes
                // but cache lifetimes can mismatch.
                $onDemandEpisode = reset($onDemandEpisodes);
                if ($onDemandEpisode instanceof Episode) {
                    return $onDemandEpisode;
                }
            }
        }

        if ($programme instanceof Episode && $programme->hasPlayableDestination()) {
            return $programme;
        }

        if ($programme instanceof Clip) {
            $parent = $programme->getParent();
            if ($parent instanceof Episode && $parent->hasPlayableDestination()) {
                return $parent;
            }
        }

        return null;
    }
}
