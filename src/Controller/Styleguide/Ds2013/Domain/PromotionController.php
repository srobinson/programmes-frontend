<?php
declare(strict_types=1);

namespace App\Controller\Styleguide\Ds2013\Domain;

use App\Controller\BaseController;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use BBC\ProgrammesPagesService\Service\PromotionsService;
use BBC\ProgrammesPagesService\Service\ServicesService;
use Symfony\Component\HttpFoundation\Request;

class PromotionController extends BaseController
{
    public function __invoke(
        ProgrammesService $programmesService,
        CoreEntitiesService $coreEntitiesService,
        PromotionsService $promotionsService,
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
        $promotion = $promotionsService->findActivePromotionsByContext($coreEntitiesService->findByPidFull(new Pid('b006q2x0')), 1)[0];


        return $this->renderWithChrome('styleguide/ds2013/domain/promotion.html.twig', [
            'promotion' => $promotion,
        ]);
    }
}
