<?php
declare(strict_types = 1);
namespace App\Controller\FindByPid;

use App\Controller\BaseController;
use App\Controller\Helpers\StructuredDataHelper;
use App\Ds2013\PresenterFactory;
use App\DsShared\Helpers\StreamableHelper;
use App\ExternalApi\Ada\Service\AdaClassService;
use App\ExternalApi\Ada\Service\AdaProgrammeService;
use App\ExternalApi\FavouritesButton\Service\FavouritesButtonService;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use BBC\ProgrammesPagesService\Domain\Entity\Version;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\ContributionsService;
use BBC\ProgrammesPagesService\Service\GroupsService;
use BBC\ProgrammesPagesService\Service\PodcastsService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use BBC\ProgrammesPagesService\Service\RelatedLinksService;
use BBC\ProgrammesPagesService\Service\SegmentEventsService;
use BBC\ProgrammesPagesService\Service\VersionsService;
use Cake\Chronos\ChronosInterval;
use GuzzleHttp\Promise\FulfilledPromise;

class ClipController extends BaseController
{
    public function __invoke(
        AdaClassService $adaClassService,
        AdaProgrammeService $adaProgrammeService,
        Clip $clip,
        ContributionsService $contributionsService,
        FavouritesButtonService $favouritesButtonService,
        GroupsService $groupsService,
        PodcastsService $podcastsService,
        PresenterFactory $presenterFactory,
        ProgrammesAggregationService $aggregationService,
        RelatedLinksService $relatedLinksService,
        SegmentEventsService $segmentEventsService,
        StreamableHelper $streamableHelper,
        StructuredDataHelper $structuredDataHelper,
        VersionsService $versionsService
    ) {
        $this->setIstatsProgsPageType('programmes_clip');
        $this->setIstatsReleaseDate($clip);
        $this->setIstatsReleaseYear($clip);
        $this->setParentIstats($clip);
        $this->setContextAndPreloadBranding($clip);
        $this->setAtiContentId((string) $clip->getPid(), 'pips');

        $linkedVersions = $versionsService->findLinkedVersionsForProgrammeItem($clip);

        $relatedLinks = [];
        if ($clip->getRelatedLinksCount() > 0) {
            $relatedLinks = $relatedLinksService->findByRelatedToProgramme($clip, ['related_site', 'miscellaneous']);
        }

        $parentClips = [];
        $tleoClips = [];
        if ($clip->getParent()) {
            /** @var ProgrammeContainer|Episode $parent */
            $parent = $clip->getParent();
            if ($parent->getAvailableClipsCount() > 0) {
                $parentClips = $this->getClipsExcept($aggregationService, $parent, $clip->getPid());
            }
            /** @var ProgrammeContainer|Episode $tleo */
            $tleo = $clip->getTleo();
            if ($tleo && $tleo->getAvailableClipsCount() > 0 && (string) $parent->getPid() !== (string) $clip->getTleo()->getPid()) {
                $tleoClips = $this->getClipsExcept($aggregationService, $tleo, $clip->getPid());
            }
        }

        $featuredIn = $groupsService->findByCoreEntityMembership($clip, 'Collection');

        $relatedProgrammesPromise = new FulfilledPromise([]);
        $relatedTopicsPromise = new FulfilledPromise([]);
        if ($clip->getOption('show_enhanced_navigation')) {
            $relatedProgrammesPromise = $adaProgrammeService->findSuggestedByProgrammeItem($clip);
            $relatedTopicsPromise = $adaClassService->findRelatedClassesByContainer($clip, true, 10);
        }

        $segmentsListPresenter = null;
        $segmentEvents = [];
        if ($clip->getSegmentEventCount() > 0 && $linkedVersions['canonicalVersion']) {
            $segmentEvents = $segmentEventsService->findByVersionWithContributions($linkedVersions['canonicalVersion']);
        }

        $contributions = [];
        if ($clip->getContributionsCount() > 0) {
            $contributions = $contributionsService->findByContributionToProgramme($clip);
        }

        $podcast = null;
        if ($clip->getTleo() instanceof ProgrammeContainer && $clip->getTleo()->isPodcastable()) {
            $podcast = $podcastsService->findByCoreEntity($clip->getTleo());
        }

        $resolvedPromises = $this->resolvePromises([
            'favouritesButton' => $favouritesButtonService->getContent(),
            'relatedTopics' => $relatedTopicsPromise,
            'relatedProgrammes' => $relatedProgrammesPromise,
        ]);

        $parameters = [
            'programme' => $clip,
            'clipIsAudio' => $streamableHelper->shouldStreamViaPlayspace($clip),
            'featuredIn' => $featuredIn,
            'parentClips' => $parentClips,
            'schema' => $this->getSchema($structuredDataHelper, $clip),
            'tleoClips' => $tleoClips,
            'relatedLinks' => $relatedLinks,
            'segmentsListPresenter' => $segmentsListPresenter,
            'contributions' => $contributions,
            'podcast' => $podcast,
            'downloadableVersion' => $linkedVersions['downloadableVersion'],
            'streamableVersion' => $linkedVersions['streamableVersion'],
            'segmentEvents' => $segmentEvents,
        ];

        return $this->renderWithChrome('find_by_pid/clip.html.twig', array_merge($resolvedPromises, $parameters));
    }

    private function setIstatsReleaseDate(Clip $clip): void
    {
        if ($clip->getReleaseDate()) {
            $this->setIstatsExtraLabels(['clip_release_date' => $clip->getReleaseDate()->asDateTime()->format('c')]);
        } elseif ($clip->getStreamableFrom()) {
            $this->setIstatsExtraLabels(['clip_release_date' => $clip->getStreamableFrom()->format('c')]);
        }
    }

    private function setIstatsReleaseYear(Clip $clip): void
    {
        if ($clip->getReleaseDate()) {
            $this->setIstatsExtraLabels(['clip_release_year' => $clip->getReleaseDate()->asDateTime()->format('Y')]);
        } elseif ($clip->getStreamableFrom()) {
            $this->setIstatsExtraLabels(['clip_release_year' => $clip->getStreamableFrom()->format('Y')]);
        }
    }

    private function setParentIstats(Clip $clip): void
    {
        $parent = $clip->getParent();
        if ($parent instanceof ProgrammeItem) {
            $this->setIstatsExtraLabels(['parent_available' => $parent->hasPlayableDestination() ? 'true' : 'false']);
            $this->setIstatsExtraLabels(['parent_entity_type' => $parent->getType()]);
        }
    }

    private function getSchema(
        StructuredDataHelper $structuredDataHelper,
        Clip $clip
    ): array {
        $clipSchema = $structuredDataHelper->buildSchemaForClip($clip);
        $parent = $clip->getParent();

        if ($parent instanceof Episode) {
            $clipSchema['partOfEpisode'] = $structuredDataHelper->getSchemaForEpisode($parent, true);
        } elseif ($parent instanceof ProgrammeContainer) {
            if ($parent->isTlec()) {
                $clipSchema['partOfSeries'] = $structuredDataHelper->getSchemaForProgrammeContainer($parent);
            } else {
                $clipSchema['partOfSeries'] = $structuredDataHelper->getSchemaForProgrammeContainer($parent->getTleo());
                $clipSchema['partOfSeason'] = $structuredDataHelper->getSchemaForProgrammeContainer($parent);
            }
        }

        $duration = new ChronosInterval(null, null, null, null, null, null, $clip->getDuration());
        $clipSchema['timeRequired'] = (string) $duration;

        if ($clip->getStreamableUntil()) {
            $clipSchema['expires'] = $clip->getStreamableUntil();
        }

        $genres = $clip->getGenres();
        if ($genres) {
            $clipSchema['genre'] = array_map(function ($genre) {
                return $genre->getUrlKeyHierarchy();
            }, $genres);
        }

        return $clipSchema;
    }

    /**
     * @param ProgrammesAggregationService $aggregationService
     * @param Programme $programme
     * @param Pid $pid
     * @return Clip[]
     */
    private function getClipsExcept(ProgrammesAggregationService $aggregationService, Programme $programme, Pid $pid): array
    {
        $clips = $aggregationService->findStreamableDescendantClips($programme, 5);
        $filteredClips = array_filter($clips, function (Clip $clip) use ($pid) {
            return (string) $clip->getPid() !== (string) $pid;
        });

        return \array_slice($filteredClips, 0, 4);
    }
}
