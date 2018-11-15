<?php
declare(strict_types=1);

namespace App\Controller\Styleguide\Ds2013\Domain;

use App\Builders\BrandBuilder;
use App\Builders\EpisodeBuilder;
use App\Controller\BaseController;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use BBC\ProgrammesPagesService\Service\ServicesService;
use Cake\Chronos\Date;
use Symfony\Component\HttpFoundation\Request;

class ProgrammeObjectController extends BaseController
{
    public function __invoke(
        ProgrammesService $programmesService,
        CoreEntitiesService $coreEntitiesService,
        ProgrammesAggregationService $programmeAggregationService,
        ServicesService $servicesService,
        Request $request
    ) {

        if ($request->query->has('branding_context')) {
            $coreEntity = $coreEntitiesService->findByPidFull(new Pid($request->query->get('branding_context')));
            $this->setContextAndPreloadBranding($coreEntity);
        }
        if ($request->query->has('service')) {
            $service = $servicesService->findByPidFull(new Pid($request->query->get('service')));
            $this->setContextAndPreloadBranding($service);
        }
        $programme = $programmesService->findByPidFull(new Pid('b006q2x0'));
        $episode = $programmeAggregationService->findStreamableOnDemandEpisodes($programme, 1)[0];
        $masterBrand = $episode->getMasterBrand();
        $title = $episode->getTitle();
        $synopses = $episode->getSynopses();
        $image = $episode->getImage();
        $tvService = $masterBrand->getName();

        $videoClip = $programmesService->findByPidFull(new Pid('p0170bpd'));

        $radioProgramme = $programmesService->findByPidFull(new Pid('b006qpgr'));
        $radioEpisode = $programmeAggregationService->findStreamableOnDemandEpisodes($radioProgramme, 1)[0];
        $radioMasterBrand = $radioEpisode->getMasterBrand();
        $radioService = $radioMasterBrand->getName();
        $radioTitle = $radioEpisode->getTitle();
        $radioSynopses = $radioEpisode->getSynopses();
        $radioImage = $radioEpisode->getImage();

        $radioClip = $programmesService->findByPidFull(new Pid('p017w3h4'));

        $moreThanOneYear = Date::create()->addYear(1);
        $moreThanTwoYears = Date::create()->addYear(10);


        return $this->renderWithChrome('styleguide/ds2013/domain/programme_object.html.twig', [

            'episode' => $episode,
            'episodeAvailableLong' => $this->getProgramme($moreThanOneYear, $programme, $title, $synopses, $image, $masterBrand),
            'episodeAvailableIndefinitely' => $this->getProgramme($moreThanTwoYears, $programme, $title, $synopses, $image, $masterBrand),
            'episodeWithNoImage' => $this->getProgramme($moreThanTwoYears, $programme, $title, $synopses, $image, $masterBrand, true, 'audio_video', false),
            'unavailableEpisodeWithNoImage' => $this->getProgramme(null, $programme, $title, $synopses, $image, $masterBrand, false, 'audio_video', false),
            'radioEpisode' => $this->getProgramme($moreThanOneYear, $radioProgramme, $radioTitle, $radioSynopses, $radioImage, $radioMasterBrand, true, 'audio'),
            'radioEpisodeAvailablityLong' => $this->getProgramme($moreThanTwoYears, $radioProgramme, $radioTitle, $radioSynopses, $radioImage, $radioMasterBrand, true, 'audio'),
            'radioEpisodeWithNoImage' => $this->getProgramme($moreThanTwoYears, $radioProgramme, $radioTitle, $radioSynopses, $radioImage, $radioMasterBrand, true, 'audio', false),
            'videoClip' => $videoClip,
            'radioClip' => $radioClip,
            'brand' => $this->getBrand($programme->getTitle(), $masterBrand, $synopses, $image, true, true),
            'brandWithNoImage' => $this->getBrand($programme->getTitle(), $masterBrand, $synopses, $image, false, false),
            'radioBrand' => $this->getBrand($radioProgramme->getTitle(), $radioMasterBrand, $radioSynopses, $radioImage, true, true),
            'radioBrandWithNoImage' => $this->getBrand($radioProgramme->getTitle(), $radioMasterBrand, $radioSynopses, $radioImage, true, false),
            'tvService' => $tvService,
            'radioService' => $radioService,
        ]);
    }

    private function getProgramme(
        $streamableUntil,
        $parent,
        $title,
        $synopses,
        $image,
        $masterBrand,
        $isStreamable = true,
        $mediaType = 'audio_video',
        $showImage = true
    ) {
        if ($showImage) {
            return EpisodeBuilder::anyTVEpisode()->with([
                'streamableFrom' => Date::create(),
                'streamableUntil' => $streamableUntil,
                'isStreamable' => $isStreamable,
                'title' => $title,
                'mediaType' => $mediaType,
                'image' => $image,
                'parent' => $parent,
                'synopses' => $synopses,
                'masterBrand' => $masterBrand,
            ])->build();
        }

        return EpisodeBuilder::anyTVEpisode()->with([
            'streamableFrom' => Date::create(),
            'streamableUntil' => $streamableUntil,
            'isStreamable' => $isStreamable,
            'title' => $title,
            'mediaType' => $mediaType,
            'parent' => $parent,
            'synopses' => $synopses,
            'masterBrand' => $masterBrand,
        ])->build();
    }

    private function getBrand(
        $title,
        $masterBrand,
        $synopses,
        $image,
        $isStreamable = true,
        $showImage = true
    ) {
        if ($showImage) {
            return BrandBuilder::any()->with([
                'isStreamable' => $isStreamable,
                'title' => $title,
                'image' => $image,
                'synopses' => $synopses,
                'masterBrand' => $masterBrand,
                'availableEpisodesCount' => 100,
            ])->build();
        }

        return BrandBuilder::any()->with([
            'isStreamable' => $isStreamable,
            'title' => $title,
            'synopses' => $synopses,
            'masterBrand' => $masterBrand,
        ])->build();
    }
}
