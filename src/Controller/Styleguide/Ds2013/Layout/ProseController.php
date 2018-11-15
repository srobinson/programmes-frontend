<?php
declare(strict_types=1);

namespace App\Controller\Styleguide\Ds2013\Layout;

use App\Controller\BaseController;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use BBC\ProgrammesPagesService\Service\ServicesService;
use Symfony\Component\HttpFoundation\Request;

class ProseController extends BaseController
{
    public function __invoke(
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

        return $this->renderWithChrome('styleguide/ds2013/Layout/prose.html.twig', [
        ]);
    }
}
