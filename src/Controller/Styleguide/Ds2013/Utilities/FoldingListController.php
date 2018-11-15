<?php
declare(strict_types=1);

namespace App\Controller\Styleguide\Ds2013\Utilities;

use App\Controller\BaseController;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use BBC\ProgrammesPagesService\Service\ServicesService;
use Cake\Chronos\Chronos;
use Symfony\Component\HttpFoundation\Request;

class FoldingListController extends BaseController
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


        $date = new Chronos('now');
        $service = $servicesService->findByPidFull(new Pid('p00fzl6n'));





        return $this->renderWithChrome('styleguide/ds2013/utilities/foldingList.html.twig', [

            'date' => $date,
            'service' => $service,
        ]);
    }
}
