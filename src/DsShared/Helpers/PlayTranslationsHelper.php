<?php
declare(strict_types = 1);
namespace App\DsShared\Helpers;

use App\Translate\TranslatableTrait;
use App\Translate\TranslateProvider;
use BBC\ProgrammesPagesService\Domain\ApplicationTime;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use Cake\Chronos\Chronos;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

class PlayTranslationsHelper
{
    use TranslatableTrait;

    /** @var Chronos */
    private $now;

    private $validPrefixes = [
        'iplayer_listen_remaining',
        'iplayer_watch_remaining',
        'iplayer_play_remaining',
        'iplayer_time',
    ];

    public function __construct(TranslateProvider $translateProvider)
    {
        $this->translateProvider = $translateProvider;
    }

    public function translateAvailableUntilToWords(ProgrammeItem $programmeItem, Service $service = null, $showAvailableUntil = true): string
    {
        if (!$programmeItem->hasPlayableDestination()) {
            return '';
        }

        $mediaVerb = $this->mediaVerb($programmeItem, $service);
        $remainingTime = $this->availableUntil($programmeItem);

        if (is_null($remainingTime)) {
            // Indefinite availability
            return $this->tr('iplayer_' . $mediaVerb . '_now');
        }

        $translationPrefix = 'iplayer_' . $mediaVerb . '_remaining';
        $text = $this->timeIntervalToWords($remainingTime, false, $translationPrefix);
        if ($showAvailableUntil && $programmeItem->getStreamableUntil()) {
            $text .= ' (' . $this->localDateIntl($programmeItem->getStreamableUntil(), 'EEE dd MMMM yyyy, HH:mm') . ')';
        }
        return $text;
    }

    public function translatePlayLive(ProgrammeItem $programmeItem, ?Service $service = null): string
    {
        $mediaVerb = $this->mediaVerb($programmeItem, $service);
        return $this->tr('iplayer_' . $mediaVerb . '_live');
    }

    public function translatePlayFromStart(ProgrammeItem $programmeItem, ?Service $service = null): string
    {
        $mediaVerb = $this->mediaVerb($programmeItem, $service);
        return $this->tr('iplayer_' . $mediaVerb . '_from_start');
    }

    public function secondsToFormattedDuration(int $duration): string
    {
        $hours = intdiv($duration, 3600);
        $duration -= ($hours * 3600);
        $minutes = intdiv($duration, 60);
        $seconds = $duration - ($minutes * 60);

        if ($hours > 0) {
            return str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $seconds, 2, '0', STR_PAD_LEFT);
        }

        return str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $seconds, 2, '0', STR_PAD_LEFT);
    }

    public function secondsToWords(
        int $seconds,
        bool $longFormat = true,
        string $translationPrefix = 'iplayer_time'
    ) {
        // @TODO write something less hideously inefficient. Should I bite the bullet and rewrite this without DateTimey stuff?
        $timeInterval = DateTimeImmutable::createFromFormat('U', '0')
            ->diff(DateTimeImmutable::createFromFormat('U', (string) ($seconds)));

        return $this->timeIntervalToWords($timeInterval, $longFormat, $translationPrefix);
    }

    public function timeIntervalToWords(
        DateInterval $timeInterval,
        bool $longFormat = true,
        string $translationPrefix = 'iplayer_time'
    ): string {
        if ($timeInterval->days === false) {
            throw new InvalidArgumentException(
                'DateInterval passed to timeIntervalToWords must be created by DateTime->diff , not the constructor. Sorry'
            );
        }

        if (!in_array($translationPrefix, $this->validPrefixes)) {
            throw new InvalidArgumentException("$translationPrefix is not a valid translation prefix");
        }

        if ($timeInterval->y >= 1) {
            // Years remaining
            return $this->tr($translationPrefix . '_years');
        }

        // Note that days gives the total number of days in the dateinterval, different to d
        $daysRemaining = $timeInterval->days;
        $fiveWeeksInDays = 7 * 5;
        if ($daysRemaining > $fiveWeeksInDays) {
            // Months remaining
            return $this->tr($translationPrefix . '_months', $timeInterval->m);
        }

        if ($daysRemaining >= 31) {
            // Weeks remaining
            $weeksRemaining = (int) round(($daysRemaining / 7), 0);
            return $this->tr($translationPrefix . '_weeks', $weeksRemaining);
        }

        if ($daysRemaining >= 1) {
            // Days remaining
            return $this->tr($translationPrefix . '_days', $daysRemaining);
        }

        if ($timeInterval->h > 0 || $timeInterval->i > 0) {
            // Hours and/or minutes remaining
            $minutesRemaining = (int) round((($timeInterval->i * 60) + $timeInterval->s) / 60, 0);
            $hoursRemaining = $timeInterval->h;

            $strings = [];
            if ($hoursRemaining > 0) {
                $strings[] = $this->tr($translationPrefix . '_hours', $hoursRemaining);
            }
            if ($minutesRemaining > 0 && (!$hoursRemaining || $longFormat)) {
                $strings[] = $this->tr($translationPrefix . '_minutes', $minutesRemaining);
            }
            return implode($strings, ', ');
        }

        if ($timeInterval->s >= 1) {
            return $this->tr($translationPrefix . '_seconds', $timeInterval->s);
        }

        return '';
    }

    /**
     * Makes sense for available programmes only
     * @return null|DateInterval Null=indefinite, DateInterval = time remaining
     */
    private function availableUntil(ProgrammeItem $programmeItem): ?DateInterval
    {
        if ($programmeItem->getStreamableUntil()) {
            $now = ApplicationTime::getTime();
            $availableTimeRemaining = $now->diff($programmeItem->getStreamableUntil());
            if ($availableTimeRemaining->days > 365) {
                // >= 1Y counts as indefinite
                return null;
            }
            return $availableTimeRemaining;
        }
        // null end date counts as indefinite
        return null;
    }

    /**
     * Used in translations. Gets a translation verb from our programme's media type
     */
    private function mediaVerb(ProgrammeItem $programmeItem, ?Service $service = null): string
    {
        if ($programmeItem->isAudio()) {
            return 'listen';
        }

        if ($programmeItem->isVideo()) {
            return 'watch';
        }

        if ($programmeItem->isRadio()) {
            return 'listen';
        }

        if ($programmeItem->isTv()) {
            return 'watch';
        }

        if ($service && $service->isRadio()) {
            return 'listen';
        }

        if ($service && $service->isTv()) {
            return 'watch';
        }

        return 'play';
    }
}
