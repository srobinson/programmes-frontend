<?php
declare(strict_types=1);

namespace App\Controller\Styleguide\Ds2013\Utilities;

use App\Controller\BaseController;
use App\ExternalApi\Isite\Domain\ContentBlock\Faq;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Domain\ValueObject\Synopses;
use BBC\ProgrammesPagesService\Service\ContributionsService;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use BBC\ProgrammesPagesService\Service\ServicesService;
use Symfony\Component\HttpFoundation\Request;

class MoreLessController extends BaseController
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

        $synopses = new Synopses("On one night in Sheffield, lives are changed for ever as a mysterious woman, unable to remember her own name, falls from the night sky.", "On one night in Sheffield, lives are changed for ever as a mysterious woman, unable to remember her own name, falls from the night sky. 'We don't get aliens in Sheffield.' In a South Yorkshire city, Ryan Sinclair, Yasmin Khan and Graham O'Brien are about to have their lives changed for ever, as a mysterious woman, unable to remember her own name, falls from the night sky.", "On one night in Sheffield, lives are changed for ever as a mysterious woman, unable to remember her own name, falls from the night sky. 'We don't get aliens in Sheffield.' In a South Yorkshire city, Ryan Sinclair, Yasmin Khan and Graham O'Brien are about to have their lives changed for ever, as a mysterious woman, unable to remember her own name, falls from the night sky. Can they believe a word she says? And can she help solve the strange events taking place across the city?");

        $questions[] = [
            'question' => "Question 1",
            'answer' => "This is the answer to question 1",
        ];
        $questions[] = [
            'question' => "Question 2",
            'answer' => "This is the answer to question 2",
        ];
        $questions[] = [
            'question' => "Question 3",
            'answer' => "This is the answer to question 3",
        ];
        $faq = new Faq('', '', $questions);

        return $this->renderWithChrome('styleguide/ds2013/utilities/moreLess.html.twig', [
                'synopses' => $synopses,
                'faq' => $faq,
        ]);
    }
}
