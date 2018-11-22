<?php
declare(strict_types=1);
namespace App\Ds2013\Presenters\Section\Episode\Map\Panels\Main;

use App\Ds2013\Presenter;
use App\DsShared\Helpers\PlayTranslationsHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Podcast;
use BBC\ProgrammesPagesService\Domain\Entity\Version;
use BBC\ProgrammesPagesService\Domain\ValueObject\PartialDate;
use Cake\Chronos\Chronos;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DetailsPresenter extends Presenter
{
    /** @var Episode */
    private $episode;

    /** @var PlayTranslationsHelper */
    private $playTranslationsHelper;

    /** @var UrlGeneratorInterface */
    private $router;

    /** @var Podcast|null */
    private $podcast;

    /** @var Version|null */
    private $downloadableVersion;

    /** @var Version[] */
    private $alternateVersions;

    public function __construct(
        PlayTranslationsHelper $playTranslationsHelper,
        UrlGeneratorInterface $router,
        Episode $episode,
        ?Version $downloadableVersion,
        array $alternateVersions,
        ?Podcast $podcast
    ) {
        parent::__construct();

        $this->episode = $episode;
        $this->playTranslationsHelper = $playTranslationsHelper;
        $this->router = $router;
        $this->downloadableVersion = $downloadableVersion;
        $this->alternateVersions = $alternateVersions;
        $this->podcast = $podcast;
    }

    public function getEpisode(): Episode
    {
        return $this->episode;
    }

    public function getPodcast(): ?Podcast
    {
        return $this->podcast;
    }

    public function canBeDownloaded(): bool
    {
        return $this->getDownloadableVersion() && $this->episode->isDownloadable();
    }

    public function getDownloadableVersion(): ?Version
    {
        return $this->downloadableVersion;
    }

    public function hasPreviousBroadcast(): bool
    {
        return $this->episode->getFirstBroadcastDate() && $this->episode->getFirstBroadcastDate()->isPast();
    }

    public function getReleaseDate(): ?PartialDate
    {
        return $this->episode->getReleaseDate();
    }

    /**
     * @see https://confluence.dev.bbc.co.uk/display/programmes/Versions+and+Availability
     */
    public function isAvailableIndefinitely(): bool
    {
        if (!$this->episode->getStreamableUntil()) {
            return true;
        }

        return !$this->episode->getStreamableUntil()->isWithinNext('1 year');
    }

    public function getStreamableTimeRemaining(): string
    {
        return $this->playTranslationsHelper->translateAvailableUntilToWords($this->episode, null, false);
    }

    public function getDuration(): string
    {
        return $this->playTranslationsHelper->secondsToWords($this->episode->getDuration());
    }

    public function hasAvailableAudioDescribedVersion(): bool
    {
        return $this->hasAlternateVersion('DubbedAudioDescribed');
    }

    public function hasAvailableSignedVersion(): bool
    {
        return $this->hasAlternateVersion('Signed');
    }

    private function hasAlternateVersion(string $versionType)
    {
        foreach ($this->alternateVersions as $version) {
            foreach ($version->getVersionTypes() as $type) {
                if ($type->getType() === $versionType) {
                    return true;
                }
            }
        }

        return false;
    }
}
