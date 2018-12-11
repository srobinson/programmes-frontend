<?php
declare(strict_types = 1);

namespace App\Controller\Schedules;

use App\Controller\Helpers\StructuredDataHelper;
use App\Controller\Traits\SchedulesPageResponseCodeTrait;
use App\Controller\Traits\UtcOffsetValidatorTrait;
use App\Ds2013\Presenters\Pages\Schedules\ByWeekPage\SchedulesByWeekPagePresenter;
use App\DsShared\Helpers\HelperFactory;
use App\ValueObject\BroadcastWeek;
use BBC\ProgrammesPagesService\Domain\ApplicationTime;
use BBC\ProgrammesPagesService\Domain\Entity\Broadcast;
use BBC\ProgrammesPagesService\Domain\Entity\BroadcastGap;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use BBC\ProgrammesPagesService\Service\BroadcastsService;
use BBC\ProgrammesPagesService\Service\ServicesService;
use Cake\Chronos\Chronos;
use DateTimeZone;
use InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ByWeekController extends SchedulesBaseController
{
    use UtcOffsetValidatorTrait;
    use SchedulesPageResponseCodeTrait;

    public function __invoke(
        Service $service,
        string $date,
        ServicesService $servicesService,
        BroadcastsService $broadcastService,
        HelperFactory $helperFactory,
        UrlGeneratorInterface $router,
        StructuredDataHelper $structuredDataHelper
    ) {
        if ($this->shouldRedirectToOverriddenUrl($service)) {
            return $this->cachedRedirect(
                $service->getNetwork()->getOption('pid_override_url'),
                $service->getNetwork()->getOption('pid_override_code'),
                3600
            );
        }

        $utcOffset = $this->request()->query->get('utcoffset');
        if (!$this->isValidDate($date) || !$this->isValidUtcOffset($utcOffset)) {
            throw $this->createNotFoundException('Invalid date supplied');
        }

        $this->setIstatsProgsPageType('schedules_week');
        $this->setContextAndPreloadBranding($service);
        $this->setInternationalStatusAndTimezoneFromContext($service);
        $this->setAtiContentId((string) $service->getPid(), 'pips');

        if ($utcOffset) {
            ApplicationTime::setLocalTimeZone($utcOffset);
        }

        try {
            $broadcastWeek = new BroadcastWeek($date);
        } catch (InvalidArgumentException $e) {
            throw $this->createNotFoundException('Invalid date');
        }

        $servicesInNetwork = $servicesService->findAllInNetworkActiveOn(
            $service->getNetwork()->getNid(),
            $broadcastWeek->end()
        );

        $daysOfBroadcasts = [];

        $broadcasts = [];
        if ($broadcastWeek->serviceIsActiveInThisPeriod($service)) {
            // Get broadcasts in relevant period
            $broadcasts = $broadcastService->findByServiceAndDateRange(
                $service->getSid(),
                $broadcastWeek->start(),
                $broadcastWeek->end(),
                BroadcastsService::NO_LIMIT
            );

            $daysOfBroadcasts = $this->groupBroadcasts($broadcasts);
            $daysOfBroadcasts = $this->addInBroadcastGaps($daysOfBroadcasts, $service);
        }

        $pagePresenter = new SchedulesByWeekPagePresenter(
            $service,
            $broadcastWeek->start(),
            $daysOfBroadcasts,
            $date,
            $servicesInNetwork
        );

        $viewData = [
            'broadcasts' => $daysOfBroadcasts,
            'broadcast_week' => $broadcastWeek,
            'service' => $service,
            'number_of_services_in_network' => count($servicesInNetwork),
            'twin_service' => $this->twinService($service, $servicesInNetwork),
            'page_presenter' => $pagePresenter,
            'schedule_reload' => $service->isInternational() && !$utcOffset,
            'schema' => $this->getSchema($structuredDataHelper, $broadcasts),
        ];

        $serviceIsActiveInThisPeriod = $this->serviceIsActiveDuringWeek($service, $broadcastWeek);
        $this->overridenDescription = "This is the weekly broadcast schedule for " . $service->getName();

        // This is from a trait and sets a 404 status code or noindex on the controller
        // as appropriate when we have no broadcasts
        $this->setResponseCodeAndNoIndexProperties($serviceIsActiveInThisPeriod, $broadcasts, $broadcastWeek);

        if ($this->request()->query->has('no_chrome')) {
            return $this->renderWithoutChrome('schedules/by_week.html.twig', $viewData);
        }
        return $this->renderWithChrome('schedules/by_week.html.twig', $viewData);
    }

    /**
     * @param Broadcast[][][] $daysOfBroadcasts
     * @param Service $service
     * @return Broadcast[][][]
     */
    private function addInBroadcastGaps(array $daysOfBroadcasts, Service $service): array
    {
        $tz = ApplicationTime::getLocalTimeZone();
        $newList = [];
        foreach ($daysOfBroadcasts as $date => $dayOfBroadcasts) {
            $newList[$date] = $this->createDayOfBroadcastsAndGaps($dayOfBroadcasts, $service, $tz, $date);
        }

        return $newList;
    }

    /**
     * @param Broadcast[][] $broadcastsToday
     * @param Service $service
     * @param DateTimeZone $tz
     * @param string $date in format Y-m-d
     * @return Broadcast[][]
     */
    private function addFinalBroadcastGapIfNecessary(array $broadcastsToday, Service $service, DateTimeZone $tz, string $date): array
    {

        $lastHour = end($broadcastsToday);
        $lastBroadcast = end($lastHour);
        $endOfDay = (new Chronos($date, $tz))->addDay()->startOfDay()->setTimezone(new DateTimeZone('UTC'));
        if ($lastBroadcast->getEndAt()->lt($endOfDay)) {
            $gap = new BroadcastGap($service, $lastBroadcast->getEndAt(), $endOfDay);
            $broadcastsToday[$lastBroadcast->getEndAt()->setTimezone($tz)->format('G')][] = $gap;
        }

        return $broadcastsToday;
    }

    /**
     * @param Broadcast[][] $dayOfBroadcasts
     * @param Service $service
     * @param DateTimeZone $tz
     * @param string $date in format Y-m-d
     * @return Broadcast[][]
     */
    private function createDayOfBroadcastsAndGaps(array $dayOfBroadcasts, Service $service, DateTimeZone $tz, string $date): array
    {
        $broadcastsToday = [];
        $priorBroadcast = null;
        foreach ($dayOfBroadcasts as $hour => $hourOfBroadcasts) {
            foreach ($hourOfBroadcasts as $broadcast) {
                // If there is space between the start of the current broadcast and
                // the end of the prior broadcast then inject a broadcast gap
                if ($priorBroadcast && $broadcast->getStartAt()->gt($priorBroadcast->getEndAt())) {
                    $broadcastsToday[$priorBroadcast->getEndAt()->setTimezone($tz)->format('G')][] = new BroadcastGap(
                        $service,
                        $priorBroadcast->getEndAt(),
                        $broadcast->getStartAt()
                    );
                }

                $broadcastsToday[$hour][] = $broadcast;
                $priorBroadcast = $broadcast;
            }
        }

        return $this->addFinalBroadcastGapIfNecessary($broadcastsToday, $service, $tz, $date);
    }

    /**
     * @param Broadcast[] $broadcasts
     * @return Broadcast[][][]
     */
    private function groupBroadcasts(array $broadcasts): array
    {
        $groupedBroadcasts = [];
        $tz = ApplicationTime::getLocalTimeZone();

        foreach ($broadcasts as $broadcast) {
            $start = $broadcast->getStartAt()->setTimezone($tz);
            $groupedBroadcasts[$start->format('Y/m/d')][$start->format('G')][] = $broadcast;
        }

        return $groupedBroadcasts;
    }

    private function serviceIsActiveDuringWeek(Service $service, BroadcastWeek $broadcastWeek): bool
    {
        return (!$service->getStartDate() || $service->getStartDate() <= $broadcastWeek->end()) && (!$service->getEndDate() || $broadcastWeek->start() < $service->getEndDate());
    }

    /**
     * @param Service $mainService
     * @param Service[] $servicesInNetwork
     * @return Service|null
     */
    private function twinService(Service $mainService, array $servicesInNetwork): ?Service
    {
        if (count($servicesInNetwork) !== 2) {
            return null;
        }

        // If there are two services, find the "other" service
        $otherServices = array_filter($servicesInNetwork, function (Service $sisterService) use ($mainService) {
            return ((string) $mainService->getSid() !== (string) $sisterService->getSid());
        });

        return reset($otherServices);
    }

    private function isValidDate($date): bool
    {
        // validate format
        if (!preg_match('#\d{4}/w\d{2}#', $date)) {
            return false;
        }

        // validate content
        list($year, $week) = explode('/', $date);
        $week = (int) str_replace('w', '', $week);

        if ($week < 1 || $week > 53 || $year < ByYearController::MINIMUM_VALID_YEAR) {
            return false;
        }

        return true;
    }

    /**
     * @param StructuredDataHelper $structuredDataHelper
     * @param Broadcast[] $broadcasts
     * @return array|null
     */
    private function getSchema(StructuredDataHelper $structuredDataHelper, array $broadcasts): ?array
    {
        $schemas = [];
        foreach ($broadcasts as $broadcast) {
            $episode = $structuredDataHelper->getSchemaForEpisode($broadcast->getProgrammeItem(), true);
            $episode['publication'] = $structuredDataHelper->getSchemaForBroadcast($broadcast);
            $schemas[] = $episode;
        }

        return $schemas ? $structuredDataHelper->prepare($schemas, true) : null;
    }
}
