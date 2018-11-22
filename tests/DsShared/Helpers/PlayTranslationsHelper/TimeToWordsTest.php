<?php
declare(strict_types = 1);
namespace Tests\App\DsShared\Helpers\PlayTranslationsHelper;

use App\DsShared\Helpers\AvailabilityTimeToWordsHelper;
use App\DsShared\Helpers\PlayTranslationsHelper;
use App\Translate\TranslateProvider;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RMP\Translate\Translate;

class TimeToWordsTest extends TestCase
{
    private $mockTranslate;

    private $helper;

    public function setUp()
    {
        $this->mockTranslate = $this->createMock(Translate::class);
        $mockTranslateProvider = $this->createMock(TranslateProvider::class);
        $mockTranslateProvider->method('getTranslate')->willReturn($this->mockTranslate);
        $this->helper = new PlayTranslationsHelper($mockTranslateProvider);
    }

    public function testNoTimeRemainingToWords()
    {
        $timeRemaining = DateTimeImmutable::createFromFormat('U', '0')
            ->diff(DateTimeImmutable::createFromFormat('U', '0'));

        $translation = $this->helper->timeIntervalToWords($timeRemaining);
        $this->assertEquals('', $translation);
    }

    /**
     * @dataProvider secondsDataProvider
     */
    public function testSecondsRemainingToWords($seconds, $prefix)
    {
        // This insanity exists becuase DateInterval (and ChronosInterval)
        // created from a format string do not calculate the number of days in them.
        // Because of course they don't.
        $timeRemaining = DateTimeImmutable::createFromFormat('U', '0')
            ->diff(DateTimeImmutable::createFromFormat('U', (string) $seconds));

        $this->setMockTranslate($prefix . '_seconds', $seconds);

        $translation = $this->helper->timeIntervalToWords($timeRemaining, false, $prefix);
        $this->assertEquals('A Translation', $translation);
    }

    public function secondsDataProvider()
    {
        return [
            [1, 'iplayer_listen_remaining'],
            [29, 'iplayer_listen_remaining'],
            [59, 'iplayer_play_remaining'],
        ];
    }

    /**
     * @dataProvider roundSecondsToMinutesDataProvider
     */
    public function testRoundSecondsToMinutesRemainingToWords($minutes, $seconds, $expectedMinutes, $prefix)
    {
        $timeRemaining = DateTimeImmutable::createFromFormat('U', '0')
            ->diff(DateTimeImmutable::createFromFormat('U', (string) (($minutes * 60) + $seconds)));

        $this->setMockTranslate($prefix . '_minutes', $expectedMinutes);

        $translation = $this->helper->timeIntervalToWords($timeRemaining, false, $prefix);
        $this->assertEquals('A Translation', $translation);
    }

    public function roundSecondsToMinutesDataProvider()
    {
        return [
            [1, 0, 1, 'iplayer_listen_remaining'],
            [29, 29, 29, 'iplayer_listen_remaining'],
            [49, 59, 50, 'iplayer_play_remaining'],
        ];
    }

    /**
     * @dataProvider minutesDataProvider
     */
    public function testMinutesRemainingToWords($minutes, $prefix)
    {
        $timeRemaining = DateTimeImmutable::createFromFormat('U', '0')
            ->diff(DateTimeImmutable::createFromFormat('U', (string) ($minutes * 60)));

        $this->setMockTranslate($prefix . '_minutes', $minutes);

        $translation = $this->helper->timeIntervalToWords($timeRemaining, false, $prefix);
        $this->assertEquals('A Translation', $translation);
    }

    public function minutesDataProvider()
    {
        return [
            [1, 'iplayer_listen_remaining'],
            [29, 'iplayer_watch_remaining'],
            [59, 'iplayer_play_remaining'],
        ];
    }

    /**
     * @dataProvider hoursDataProvider
     */
    public function testHoursRemainingToWords($hours, $prefix)
    {
        // All times get $hours and 2 minutes. To test the joining logic
        $timeRemaining = DateTimeImmutable::createFromFormat('U', '0')
            ->diff(DateTimeImmutable::createFromFormat('U', (string) (120 + $hours * 3600)));

        $this->mockTranslate->expects($this->at(0))
            ->method('translate')
            ->with(
                $prefix . '_hours',
                ['%count%' => $hours],
                $hours
            )->willReturn('A Translation');
        $this->mockTranslate->expects($this->at(1))
            ->method('translate')
            ->with(
                $prefix . '_minutes',
                ['%count%' => 2],
                2
            )->willReturn('A Translation');
        $translation = $this->helper->timeIntervalToWords($timeRemaining, true, $prefix);
        $this->assertEquals('A Translation, A Translation', $translation);
    }

    public function hoursDataProvider()
    {
        return [
            [1, 'iplayer_listen_remaining'],
            [6, 'iplayer_watch_remaining'],
            [23, 'iplayer_play_remaining'],
        ];
    }

    /**
     * @dataProvider weeksDataProvider
     */
    public function testWeeksRemainingToWords($days, $weeks, $prefix)
    {
        $timeRemaining = DateTimeImmutable::createFromFormat('U', '0')
            ->diff(DateTimeImmutable::createFromFormat('U', (string) (120 + $days * 3600 * 24)));

        $this->setMockTranslate($prefix . '_weeks', $weeks);

        $translation = $this->helper->timeIntervalToWords($timeRemaining, false, $prefix);
        $this->assertEquals('A Translation', $translation);
    }

    public function weeksDataProvider()
    {
        return [
            [31, 4, 'iplayer_listen_remaining'],
            [35, 5, 'iplayer_watch_remaining'],
        ];
    }

    /**
     * @dataProvider monthsDataProvider
     */
    public function testMonthsRemainingToWords($days, $months, $prefix)
    {
        $timeRemaining = DateTimeImmutable::createFromFormat('U', '0')
            ->diff(DateTimeImmutable::createFromFormat('U', (string) (120 + $days * 3600 * 24)));

        $this->setMockTranslate($prefix . '_months', $months);

        $translation = $this->helper->timeIntervalToWords($timeRemaining, false, $prefix);
        $this->assertEquals('A Translation', $translation);
    }

    public function monthsDataProvider()
    {
        return [
            [36, 1, 'iplayer_listen_remaining'],
            [63, 2, 'iplayer_watch_remaining'],
            [364, 11, 'iplayer_watch_remaining'],
        ];
    }

    public function testYearsRemainingToWords()
    {
        $timeRemaining = DateTimeImmutable::createFromFormat('U', '0')
            ->diff(DateTimeImmutable::createFromFormat('U', (string) (3600 * 24 * 366)));

        $translationPrefix = 'iplayer_time';
        $this->mockTranslate->expects($this->once())
            ->method('translate')
            ->with($translationPrefix . '_years')
            ->willReturn('A Translation');
        $translation = $this->helper->timeIntervalToWords($timeRemaining, false, $translationPrefix);
        $this->assertEquals('A Translation', $translation);
    }

    private function setMockTranslate($translationKey, $count)
    {
        $this->mockTranslate->expects($this->once())
            ->method('translate')
            ->with(
                $translationKey,
                ['%count%' => $count],
                $count
            )->willReturn('A Translation');
    }
}
