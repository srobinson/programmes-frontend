<?php
namespace App\Ds2013\Presenters\Section\Clip\Details;

use App\Ds2013\Presenter;
use App\DsShared\Helpers\PlayTranslationsHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Contribution;
use BBC\ProgrammesPagesService\Domain\Entity\Podcast;
use BBC\ProgrammesPagesService\Domain\Entity\Version;
use BBC\ProgrammesPagesService\Domain\ValueObject\PartialDate;

class ClipDetailsPresenter extends Presenter
{
    /** @var Clip */
    private $clip;

    /** @var PlayTranslationsHelper */
    private $playTranslationsHelper;

    /** @var Contribution[] */
    private $contributions;

    /** @var Version|null */
    private $version;

    /** @var Podcast|null */
    private $podcast;

    public function __construct(PlayTranslationsHelper $playTranslationsHelper, Clip $clip, array $contributions, ?Version $version, ?Podcast $podcast, array $options = [])
    {
        $this->clip = $clip;
        $this->version = $version;
        $this->podcast = $podcast;
        $this->contributions = $contributions;
        $this->playTranslationsHelper = $playTranslationsHelper;

        parent::__construct($options);
    }

    public function getClip(): Clip
    {
        return $this->clip;
    }

    public function getVersion(): ?Version
    {
        return $this->version;
    }

    public function getPodcast(): ?Podcast
    {
        return $this->podcast;
    }

    public function canBeDownloaded(): bool
    {
        return $this->version && $this->version->isDownloadable() && $this->clip->isDownloadable();
    }

    /**
     * @return Contribution[]
     */
    public function getContributions(): array
    {
        return $this->contributions;
    }

    public function getReleaseDate(): ?PartialDate
    {
        return $this->clip->getReleaseDate();
    }

    public function isAvailableIndefinitely(): bool
    {
        if (!$this->clip->getStreamableUntil()) {
            return true;
        }

        return !$this->clip->getStreamableUntil()->isWithinNext('1 year');
    }

    public function getStreamableTimeRemaining(): string
    {
        return $this->playTranslationsHelper->translateAvailableUntilToWords($this->clip, null, false);
    }

    public function getWordyDuration(): ?string
    {
        if ($this->clip->getDuration()) {
            return $this->playTranslationsHelper->secondsToWords($this->clip->getDuration());
        }

        return null;
    }
}
