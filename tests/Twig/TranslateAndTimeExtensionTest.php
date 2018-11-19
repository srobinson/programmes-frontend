<?php
declare(strict_types = 1);

namespace Tests\App\Twig;

use App\DsShared\Helpers\HelperFactory;
use App\Translate\TranslateProvider;
use App\Twig\TranslateAndTimeExtension;
use BBC\ProgrammesPagesService\Domain\ApplicationTime;
use BBC\ProgrammesPagesService\Domain\ValueObject\PartialDate;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use RMP\Translate\Translate;

class TranslateAndTimeExtensionTest extends TestCase
{
    private $mockTranslate;

    /** @var  TranslateAndTimeExtension */
    private $translateAndTimeExtension;

    public function setUp()
    {
        $this->mockTranslate = $this->createMock(Translate::class);
        /** @var TranslateProvider|PHPUnit_Framework_MockObject_MockObject $translateProvider */
        $translateProvider = $this->createMock(TranslateProvider::class);
        $translateProvider->method('getTranslate')->willReturn($this->mockTranslate);
        $helperFactory = $this->createMock(HelperFactory::class);
        $this->translateAndTimeExtension = new TranslateAndTimeExtension($translateProvider, $helperFactory);
    }

    public function tearDown()
    {
        ApplicationTime::blank();
    }

    public function testTrWrapper()
    {
        $this->mockTranslate->expects($this->once())
            ->method('translate')
            ->with('wibble', ['%count%' => 'eleventy'], 110)
            ->willReturn('Utter Nonsense');
        $result = $this->translateAndTimeExtension->trWrapper('wibble', ['%count%' => 'eleventy'], 110);
        $this->assertEquals('Utter Nonsense', $result);
    }

    /**
     * @dataProvider localDateDataProvider
     */
    public function testLocalDate($time, $timeZone, $expected)
    {
        $dateTime = new DateTime($time, new DateTimeZone('UTC'));
        ApplicationTime::setTime($dateTime->getTimestamp());
        ApplicationTime::setLocalTimeZone($timeZone);
        $this->assertEquals(
            $expected,
            $this->translateAndTimeExtension->localDate(ApplicationTime::getTime(), 'Y-m-d H:i:s')
        );
    }

    public function localDateDataProvider()
    {
        return [
            ['2017-06-01 13:00:00', 'Europe/Berlin', '2017-06-01 15:00:00'],
            ['2017-06-01 13:00:00', 'Europe/London', '2017-06-01 14:00:00'],
            ['2017-01-01 13:00:00', 'Europe/London', '2017-01-01 13:00:00'],
            ['2017-06-01 13:00:00', 'UTC', '2017-06-01 13:00:00'],
        ];
    }

    public function testTimeZoneNoteEuropeLondon()
    {
        $dateTime = new DateTime('2017-06-01 13:00:00', new DateTimeZone('UTC'));
        ApplicationTime::setTime($dateTime->getTimestamp());
        ApplicationTime::setLocalTimeZone('Europe/London');
        $this->assertEquals(
            '',
            $this->translateAndTimeExtension->timeZoneNote(ApplicationTime::getTime())
        );
    }

    public function testTimeZoneNoteUtc()
    {
        $dateTime = new DateTime('2017-06-01 13:00:00', new DateTimeZone('UTC'));
        ApplicationTime::setTime($dateTime->getTimestamp());
        ApplicationTime::setLocalTimeZone('UTC');
        $this->mockTranslate->expects($this->once())
            ->method('translate')
            ->with('gmt')
            ->willReturn('GMT');

        $this->assertEquals(
            '<span class="timezone--note">GMT</span>',
            $this->translateAndTimeExtension->timeZoneNote(ApplicationTime::getTime())
        );
    }

    public function testTimeZoneNoteIntl()
    {
        $dateTime = new DateTime('2017-06-01 13:00:00', new DateTimeZone('UTC'));
        ApplicationTime::setTime($dateTime->getTimestamp());
        ApplicationTime::setLocalTimeZone('Pacific/Chatham');
        $this->mockTranslate->expects($this->once())
            ->method('translate')
            ->with('gmt')
            ->willReturn('GMT');

        $this->assertEquals(
            '<span class="timezone--note">GMT+12:45</span>',
            $this->translateAndTimeExtension->timeZoneNote(ApplicationTime::getTime())
        );
    }

    public function testTimeZoneNoteIntlNegative()
    {
        $dateTime = new DateTime('2017-06-12 12:00:00', new DateTimeZone('UTC'));
        ApplicationTime::setTime($dateTime->getTimestamp());
        ApplicationTime::setLocalTimeZone('Pacific/Marquesas');
        $this->mockTranslate->expects($this->once())
            ->method('translate')
            ->with('gmt')
            ->willReturn('GMT');

        $this->assertEquals(
            '<span class="timezone--note">GMT-09:30</span>',
            $this->translateAndTimeExtension->timeZoneNote(ApplicationTime::getTime())
        );
    }

    /**
     * @dataProvider localPartialDateDataProvider
     */
    public function testLocalPartialDate($partialDate, $formats, $expected)
    {
        $this->mockTranslate->expects($this->atLeastOnce())
            ->method('getLocale')
            ->willReturn('en-GB');

        $this->assertEquals(
            $expected,
            $this->translateAndTimeExtension->localPartialDate($partialDate, ...$formats)
        );
    }

    public function localPartialDateDataProvider()
    {
        return [
            'year' => [
                new PartialDate(2018),
                ['dd MMMM Y', 'MMMM Y', 'Y'],
                '<time datetime="2018">2018</time>',
            ],
            'year_and_month' => [
                new PartialDate(2018, 11),
                ['dd MMMM Y', 'MMMM Y', 'Y'],
                '<time datetime="2018-11">November 2018</time>',
            ],
            'full_date' => [
                new PartialDate(2018, 11, 16),
                ['dd MMMM Y', 'MMMM Y', 'Y'],
                '<time datetime="2018-11-16">16 November 2018</time>',
            ],
        ];
    }
}
