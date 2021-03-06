<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Utilities\DateList;

use App\ValueObject\BroadcastDay;
use App\ValueObject\BroadcastPeriod;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use Cake\Chronos\Chronos;
use Cake\Chronos\ChronosInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use BBC\ProgrammesPagesService\Domain\ApplicationTime;

class DayDateListItemPresenter extends AbstractDateListItemPresenter
{
    public function __construct(UrlGeneratorInterface $router, ChronosInterface $datetime, Service $service, int $offset, Chronos $unavailableAfterDate, array $options = [])
    {
        $options = array_merge(['user_timezone' => 'GMT'], $options);
        parent::__construct($router, $datetime->addDays($offset), $service, $offset, $unavailableAfterDate, $options);
    }

    public function getLink(): string
    {
        return $this->router->generate(
            'schedules_by_day',
            ['pid' => (string) $this->service->getPid(), 'date' => $this->datetime->format('Y/m/d')],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function isGmt(): bool
    {
        return $this->options['user_timezone'] == 'GMT';
    }

    /**
     * Using getDateTime()->isToday() would create a new Chronos object for each call. So therefore we use
     * ApplicationTime as it is only created once.
     */
    public function isToday(): bool
    {
        return ApplicationTime::getTime()->toDateString() == $this->getDateTime()->toDateString();
    }

    protected function getBroadcastPeriod(string $medium): BroadcastPeriod
    {
        return new BroadcastDay($this->datetime, $medium);
    }
}
