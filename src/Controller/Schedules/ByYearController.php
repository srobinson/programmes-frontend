<?php
declare(strict_types = 1);

namespace App\Controller\Schedules;

use BBC\ProgrammesPagesService\Domain\ApplicationTime;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use Cake\Chronos\Date;

class ByYearController extends SchedulesBaseController
{
    /**
     * Value decided to avoid unusual values for the year like 0000, 0001, etc.
     * This avoid the problem of having some negative years displayed in the UI, which
     * in some cases might displays the selected year and years around of it.
     */
    const MINIMUM_VALID_YEAR = 1900;

    public function __invoke(Service $service, string $year)
    {
        if ($this->shouldRedirectToOverriddenUrl($service)) {
            return $this->cachedRedirect(
                $service->getNetwork()->getOption('pid_override_url'),
                $service->getNetwork()->getOption('pid_override_code'),
                3600
            );
        }

        if (!$this->isValidYear($year)) {
            throw $this->createNotFoundException('Invalid date supplied');
        }

        $this->setIstatsProgsPageType('schedules_year');
        $this->setContextAndPreloadBranding($service);
        $this->setAtiContentId((string) $service->getPid(), 'pips');


        $startOfYear = Date::createFromFormat('Y|', $year, ApplicationTime::getLocalTimeZone())->firstOfYear();
        $viewData = ['start_of_year' => $startOfYear, 'service' => $service];
        $this->overridenDescription = "This is the yearly broadcast schedule for " . $service->getName();
        // If the service is not active at all over the year, then the status code should be 404, so
        // that search engines do not index thousands of empty pages
        if (!$this->serviceIsActiveDuringYear($service, $startOfYear)) {
            $this->response()->setStatusCode(404);
        }
        return $this->renderWithChrome('schedules/by_year.html.twig', $viewData);
    }

    private function serviceIsActiveDuringYear(Service $service, Date $startOfYear): bool
    {
        return (!$service->getStartDate() || $service->getStartDate() <= $startOfYear->endOfYear()) && (!$service->getEndDate() || $startOfYear < $service->getEndDate());
    }

    private function isValidYear(string $year): bool
    {
        // validate format
        if (!preg_match('#\d{4}#', $year) || $year < ByYearController::MINIMUM_VALID_YEAR) {
            return false;
        }

        return true;
    }
}
