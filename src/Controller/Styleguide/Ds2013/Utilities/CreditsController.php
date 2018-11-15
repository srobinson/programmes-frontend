<?php
declare(strict_types=1);

namespace App\Controller\Styleguide\Ds2013\Utilities;

use App\Controller\BaseController;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\ContributionsService;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use BBC\ProgrammesPagesService\Service\ServicesService;
use Symfony\Component\HttpFoundation\Request;

class CreditsController extends BaseController
{
    public function __invoke(
        ProgrammesService $programmesService,
        ProgrammesAggregationService $programmeAggregationService,
        ContributionsService $contributionsService,
        Request $request,
        ServicesService $servicesService,
        CoreEntitiesService $coreEntitiesService
    ) {
        if ($request->query->has('branding_context')) {
            $coreEntity = $coreEntitiesService->findByPidFull(new Pid($request->query->get('branding_context')));
            $this->setContextAndPreloadBranding($coreEntity);
        }
        if ($request->query->has('service')) {
            $service = $servicesService->findByPidFull(new Pid($request->query->get('service')));
            $this->setContextAndPreloadBranding($service);
        }
        $programme = $programmesService->findByPid(new Pid('b08xhhn4'));
        $episode = $programmeAggregationService->findStreamableOnDemandEpisodes($programme)[0];
        $contributions = [];
        if ($episode->getContributionsCount() > 0) {
            $contributions = $contributionsService->findByContributionToProgramme($episode, 4);
        }
        return $this->renderWithChrome('styleguide/ds2013/utilities/credits.html.twig', [
            'contributions' => $contributions,
        ]);
    }
}
