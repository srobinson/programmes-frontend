<?php
declare(strict_types=1);

namespace App\Controller\Styleguide\Ds2013\Domain;

use App\Controller\BaseController;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Domain\ValueObject\Sid;
use BBC\ProgrammesPagesService\Service\BroadcastsService;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use BBC\ProgrammesPagesService\Service\ServicesService;
use Cake\Chronos\Chronos;
use Symfony\Component\HttpFoundation\Request;

class BroadcastObjectController extends BaseController
{
    public function __invoke(
        BroadcastsService $broadcastService,
        CoreEntitiesService $coreEntitiesService,
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

        $start = Chronos::createFromTime(17, 00, 00);
        $end = Chronos::createFromTime(18, 30, 00);

        $broadcast = $broadcastService->findByServiceAndDateRange(new Sid('bbc_one_hd'), $start->addDay(), $end->addDay());

        $pastBroadcast = $broadcastService->findByServiceAndDateRange(new Sid('bbc_one_hd'), $start->subDay(), $end->subDay());

        $service = $servicesService->findByPidFull(new Pid('p00fzl6n'));

        $radioService = $servicesService->findByPidFull(new Pid('p00fzl7j'));

        $liveBroadcast = $broadcastService->findOnNowByService($service);
        $liveRadioBroadcast = $broadcastService->findOnNowByService($radioService);
        $radioBroadcast = $broadcastService->findByServiceAndDateRange(new Sid('bbc_radio_fourfm'), $start->addDay(), $end->addDay());

        $pastRadioBroadcast = $broadcastService->findByServiceAndDateRange(new Sid('bbc_radio_fourfm'), $start->subDay(), $end->subDay());

        return $this->renderWithChrome('styleguide/ds2013/domain/broadcast_object.html.twig', [
            'broadcast' => $broadcast[0],
            'pastBroadcast' => $pastBroadcast[0],
            'liveBroadcast' =>  $liveBroadcast,
            'radioBroadcast' => $radioBroadcast[0],
            'pastRadioBroadcast' => $pastRadioBroadcast[0],
            'liveRadioBroadcast' => $liveRadioBroadcast,
        ]);
    }
}
