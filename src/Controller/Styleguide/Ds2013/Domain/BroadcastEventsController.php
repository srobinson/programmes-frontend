<?php
declare(strict_types=1);

namespace App\Controller\Styleguide\Ds2013\Domain;

use App\Controller\BaseController;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use BBC\ProgrammesPagesService\Service\ServicesService;
use Symfony\Component\HttpFoundation\Request;

class BroadcastEventsController extends BaseController
{
    public function __invoke(
        CollapsedBroadcastsService $collapsedBroadcastService,
        CoreEntitiesService $coreEntitiesService,
        Request $request,
        ServicesService $servicesService
    ) {

        if ($request->query->has('branding_context')) {
            $coreEntity = $coreEntitiesService->findByPidFull(new Pid($request->query->get('branding_context')));
            $this->setContextAndPreloadBranding($coreEntity);
        }
        if ($request->query->has('service')) {
            $service = $servicesService->findByPidFull(new Pid($request->query->get('service')));
            $this->setContextAndPreloadBranding($service);
        }
        $programOfEastenders = $coreEntitiesService->findByPidFull(new Pid('b006m86d'));
        $collapsedBroadcastOfEastenders = $collapsedBroadcastService->findPastByProgrammeWithFullServicesOfNetworksList($programOfEastenders, 1);

        $programOfWorldServiceNews = $coreEntitiesService->findByPidFull(new Pid('p002vsmz'));
        $collapsedBroadcastOfWorldServiceNews = $collapsedBroadcastService->findPastByProgrammeWithFullServicesOfNetworksList($programOfWorldServiceNews, 1);
        return $this->renderWithChrome('styleguide/ds2013/domain/broadcast_event.html.twig', [
            'collapsedBroadcastOfEastenders' => $collapsedBroadcastOfEastenders[0],
            'collapsedBroadcastOfWorldServiceNews' => $collapsedBroadcastOfWorldServiceNews[0],
        ]);
    }
}
