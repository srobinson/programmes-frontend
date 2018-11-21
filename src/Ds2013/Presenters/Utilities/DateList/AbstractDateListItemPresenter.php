<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Utilities\DateList;

use App\Ds2013\Presenter;
use App\ValueObject\BroadcastPeriod;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use Cake\Chronos\Chronos;
use Cake\Chronos\ChronosInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractDateListItemPresenter extends Presenter
{
    /** @var ChronosInterface */
    protected $datetime;

    /** @var int */
    protected $offset;

    /** @var UrlGeneratorInterface */
    protected $router;

    /** @var Service */
    protected $service;

    /** @var  bool */
    private $isLink;

    public function __construct(UrlGeneratorInterface $router, ChronosInterface $datetime, Service $service, int $offset, Chronos $unavailableAfterDate, array $options = [])
    {
        parent::__construct($options);

        $this->datetime = $datetime;
        $this->service = $service;
        $this->offset = $offset;
        $this->router = $router;
        $this->isLink = $this->buildIsLink($unavailableAfterDate);
    }

    public function isLink(): bool
    {
        return $this->isLink;
    }

    public function getDateTime(): ChronosInterface
    {
        return $this->datetime;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getTemplateVariableName(): string
    {
        return 'date_list_item';
    }

    abstract public function getLink(): string;

    abstract protected function getBroadcastPeriod(string $medium): BroadcastPeriod;

    private function buildIsLink(Chronos $unavailableAfterDate) : bool
    {
        // if the date is more than $unavailableAfterDate from now, then don't allow a link (page will still exist)
        if ($this->offset === 0 || $this->datetime->gte($unavailableAfterDate)) {
            return false;
        }

        $network = $this->service->getNetwork();
        if (is_null($network)) {
            return false;
        }

        return $this->getBroadcastPeriod($network->getMedium())->serviceIsActiveInThisPeriod($this->service);
    }
}
