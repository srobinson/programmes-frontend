<?php
declare(strict_types = 1);
namespace App\Ds2013\Presenters\Domain\CoreEntity\Programme\CollapsedBroadcastSubPresenters;

use App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters\ProgrammeBodyPresenter;
use App\DsShared\Helpers\LiveBroadcastHelper;
use App\DsShared\Helpers\LocalisedDaysAndMonthsHelper;
use App\DsShared\Helpers\PlayTranslationsHelper;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CollapsedBroadcastProgrammeBodyPresenter extends ProgrammeBodyPresenter
{
    /** @var CollapsedBroadcast */
    private $collapsedBroadcast;

    /** @var LiveBroadcastHelper */
    private $liveBroadcastHelper;

    public function __construct(
        UrlGeneratorInterface $router,
        PlayTranslationsHelper $playTranslationsHelper,
        LiveBroadcastHelper $liveBroadcastHelper,
        LocalisedDaysAndMonthsHelper $localisedDaysAndMonthsHelper,
        CollapsedBroadcast $broadcast,
        Programme $programme,
        array $options = []
    ) {
        parent::__construct($router, $playTranslationsHelper, $localisedDaysAndMonthsHelper, $programme, $options);
        $this->collapsedBroadcast = $broadcast;
        $this->liveBroadcastHelper = $liveBroadcastHelper;
    }

    public function getSimulcastUrl(): string
    {
        return $this->liveBroadcastHelper->simulcastUrl($this->collapsedBroadcast, $this->options['context_service']);
    }

    public function isRepeat(): bool
    {
        return $this->collapsedBroadcast->isRepeat();
    }

    public function isWatchableLive(): bool
    {
        return $this->liveBroadcastHelper->isWatchableLive($this->collapsedBroadcast, $this->options['advanced_live']);
    }

    public function isWorldServiceForeignLanguage(): bool
    {
        $network = $this->programme->getNetwork();
        if ($network && $network->isWorldServiceInternational() && (string) $network->getNid() !== 'bbc_world_service') {
            return true;
        }
        return false;
    }

    public function rewindUrl(): string
    {
        $url = $this->liveBroadcastHelper->simulcastUrl($this->collapsedBroadcast, $this->options['context_service']);
        $url .= ((strpos($url, '?') !== false) ? '&' : '?') . 'rewindTo=current';
        return $url;
    }

    public function translatePlayFromStart(): string
    {
        return $this->playTranslationsHelper->translatePlayFromStart($this->programme, $this->options['context_service']);
    }

    public function translatePlayLive(): string
    {
        return $this->playTranslationsHelper->translatePlayLive($this->programme, $this->options['context_service']);
    }

    public function getTemplateVariableName(): string
    {
        return 'programme_body';
    }
}
