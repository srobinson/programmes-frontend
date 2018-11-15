<?php
declare(strict_types = 1);

namespace App\Controller\Styleguide\Ds2013\Utilities;

use App\Builders\CollapsedBroadcastBuilder;
use App\Builders\ServiceBuilder;
use App\Controller\BaseController;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Domain\ValueObject\Sid;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use Symfony\Component\HttpFoundation\Request;

class CtaInSituController extends BaseController
{
    public function __invoke(Request $request, CoreEntitiesService $coreEntitiesService)
    {
        if ($request->query->has('branding_context')) {
            $coreEntity = $coreEntitiesService->findByPidFull(new Pid($request->query->get('branding_context')));
            $this->setContextAndPreloadBranding($coreEntity);
        }

        $tvClip = $coreEntitiesService->findByPidFull(new Pid('p057d91w'));
        $audioClip = $coreEntitiesService->findByPidFull(new Pid('p06f4nt2'));
        $tvEpisode = $coreEntitiesService->findByPidFull(new Pid('b08xhhn4'));
        $audioEpisode = $coreEntitiesService->findByPidFull(new Pid('b0bfz5k0'));
        //Oddities
        $tvAudioEpisode = $coreEntitiesService->findByPidFull(new Pid('b013n0g2'));
        $radioVideoEpisode = $coreEntitiesService->findByPidFull(new Pid('p041v365'));
        $radioVideoClip = $coreEntitiesService->findByPidFull(new Pid('p00r929z'));

        $gallery = $coreEntitiesService->findByPidFull(new Pid('p019kj0y'));
        $collection = $coreEntitiesService->findByPidFull(new Pid('p03f6313'));

        $radioServices = [ServiceBuilder::any()->with(['sid' => new Sid('bbc_radio_one')])->build()];
        $tvServices = [ServiceBuilder::any()->with(['sid' => new Sid('bbc_one_london')])->build()];
        $radioCollapsedBroadcast = CollapsedBroadcastBuilder::anyLive()->with(['isBlanked' => false, 'services' => $radioServices, 'programmeItem' => $audioEpisode])->build();
        $tvCollapsedBroadcast = CollapsedBroadcastBuilder::anyLive()->with(['isBlanked' => false, 'services' => $tvServices, 'programmeItem' => $tvEpisode])->build();

        return $this->renderWithChrome(
            'styleguide/ds2013/Utilities/cta_in_situ.html.twig',
            [
                'radioCollapsedBroadcast' => $radioCollapsedBroadcast,
                'tvCollapsedBroadcast' => $tvCollapsedBroadcast,
                'tvClip' => $tvClip,
                'audioClip' => $audioClip,
                'tvEpisode' => $tvEpisode,
                'audioEpisode' => $audioEpisode,
                'radioVideoClip' => $radioVideoClip,
                'tvAudioEpisode' => $tvAudioEpisode,
                'radioVideoEpisode' => $radioVideoEpisode,
                'gallery' => $gallery,
                'collection' => $collection,
            ]
        );
    }
}
