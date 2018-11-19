<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters;

use App\Ds2013\Presenters\Domain\CoreEntity\Programme\ProgrammePresenterBase;
use App\DsShared\Helpers\LocalisedDaysAndMonthsHelper;
use App\DsShared\Helpers\PlayTranslationsHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use Cake\Chronos\Chronos;
use InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Sub-presenter for ProgrammePresenter
 */
class ProgrammeBodyPresenter extends ProgrammePresenterBase
{
    /** @var array */
    protected $options = [
        'show_synopsis' => true,
        'show_duration' => false,
        'wordy_duration' => true,
        'body_suffix' => null,
        'show_child_availability' => false,
        'show_release_date' => true,
    ];

    /** @var PlayTranslationsHelper */
    protected $playTranslationsHelper;

    /** @var LocalisedDaysAndMonthsHelper  */
    protected $localisedDaysAndMonthsHelper;


    public function __construct(
        UrlGeneratorInterface $router,
        PlayTranslationsHelper $playTranslationsHelper,
        LocalisedDaysAndMonthsHelper $localisedDaysAndMonthsHelper,
        Programme $programme,
        array $options = []
    ) {
        // Clips show duration by default
        if ($programme instanceof Clip) {
            $this->options['show_duration'] = true;
        }
        parent::__construct($router, $programme, $options);
        $this->playTranslationsHelper = $playTranslationsHelper;
        $this->localisedDaysAndMonthsHelper = $localisedDaysAndMonthsHelper;
    }

    public function getDurationInWords(): string
    {
        if (!$this->programme instanceof ProgrammeItem) {
            throw new InvalidArgumentException("Cannot get duration for non-programmeitem");
        }
        return $this->playTranslationsHelper->secondsToWords($this->programme->getDuration());
    }

    public function getFormattedDuration(): string
    {
        if (!$this->programme instanceof ProgrammeItem) {
            throw new InvalidArgumentException("Cannot get duration for non-programmeitem");
        }

        return $this->playTranslationsHelper->secondsToFormattedDuration($this->programme->getDuration());
    }

    public function getSynopsisTruncationLength()
    {
        if ($this->options['truncation_length']) {
            return $this->options['truncation_length'] * 1.5;
        }
        return null;
    }

    public function hasDefinedPositionUnderParentProgramme(): bool
    {
        $parent = $this->programme->getParent();
        if ($parent instanceof ProgrammeContainer
            && !is_null($parent->getExpectedChildCount())
            && !is_null($this->programme->getPosition())
        ) {
            return true;
        }
        return false;
    }

    public function shouldShowDuration(): bool
    {
        return (
            $this->options['show_duration'] &&
            $this->programme instanceof ProgrammeItem &&
            $this->programme->getDuration()
        );
    }

    public function containerHasAvailableEpisodes()
    {
        if ($this->programme->isTlec() && $this->programme instanceof ProgrammeContainer) {
            return $this->programme->getAvailableEpisodesCount() > 0;
        }
        return false;
    }

    public function getReleaseDate(): string
    {
        // @TODO handle partial release dates correctly. See PROGRAMMES-6733
        if (!$this->hasReleaseDate()) {
            return '';
        }
        $releaseDate = new Chronos($this->programme->getReleaseDate());
        return $this->localisedDaysAndMonthsHelper->getFormatedDay($releaseDate);
    }


    public function hasReleaseDate(): bool
    {
        return ($this->getOption('show_release_date') && ($this->programme instanceof ProgrammeItem) && $this->programme->getReleaseDate());
    }
}
