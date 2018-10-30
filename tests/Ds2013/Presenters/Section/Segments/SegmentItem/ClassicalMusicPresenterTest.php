<?php
declare(strict_types=1);

namespace Tests\App\Ds2013\Presenters\Section\Segments\SegmentItem;

use App\Builders\MusicSegmentBuilder;
use App\Builders\SegmentEventBuilder;
use App\Ds2013\Presenters\Section\Segments\SegmentItem\ClassicalMusicPresenter;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Contribution;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Segment;
use BBC\ProgrammesPagesService\Domain\Entity\SegmentEvent;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use Tests\App\BaseTemplateTestCase;

class ClassicalMusicPresenterTest extends BaseTemplateTestCase
{
    public function testTemplate()
    {
        $composer = $this->createConfiguredMock(Contribution::class, [
            'getCreditRole' => 'composer',
            'getPid' => new Pid('cnt000001'),
        ]);
        $mockContext = $this->getMockBuilder(Episode::class)->disableOriginalConstructor()->getMock();
        $segment = MusicSegmentBuilder::any()->with(['contributions' => [$composer]])->build();
        $segmentEvent = SegmentEventBuilder::any()->with(['segment' => $segment])->build();
        $crawler = $this->presenterCrawler(new ClassicalMusicPresenter($mockContext, $segmentEvent, 'anything', null));

        $this->assertCount(1, $crawler->filter('div.segment--music'));
    }

    /** @dataProvider setupContributionsProvider */
    public function testSetupContributions(
        array $expectedPrimaryContributions,
        array $expectedSecondaryContributions,
        ?Contribution $providedPrimaryContribution,
        array $providedContributions
    ) {
        $segment = $this->createConfiguredMock(Segment::class, ['getContributions' => $providedContributions]);
        $segmentEvent = $this->createConfiguredMock(SegmentEvent::class, ['getSegment' => $segment]);
        $context = $this->getMockBuilder(Episode::class)->disableOriginalConstructor()->getMock();
        $presenter = new ClassicalMusicPresenter($context, $segmentEvent, 'anything', null);

        $this->assertEquals($expectedPrimaryContributions, $presenter->getPrimaryContributions());
        $this->assertEquals($expectedSecondaryContributions, $presenter->getOtherContributions());
        $this->assertEquals($providedPrimaryContribution, $presenter->getPrimaryContribution());
    }

    public function setupContributionsProvider(): array
    {
        $composer = $this->createConfiguredMock(Contribution::class, [
            'getCreditRole' => 'composer',
            'getPid' => new Pid('cnt000001'),
        ]);
        $anotherComposer = $this->createConfiguredMock(Contribution::class, [
            'getCreditRole' => 'composer',
            'getPid' => new Pid('cnt000010'),
        ]);
        $performer = $this->createConfiguredMock(Contribution::class, [
            'getCreditRole' => 'performer',
            'getPid' => new Pid('cnt000002'),
        ]);
        $transcriber = $this->createConfiguredMock(Contribution::class, [
            'getCreditRole' => 'transcriber',
            'getPid' => new Pid('cnt000003'),
        ]);

        return [
            'with no contributions' => [[], [], null, []],
            'composer only' => [[$composer], [], $composer, [$composer]],
            'composer and performer' => [[$composer], [$performer], $composer, [$composer, $performer]],
            'composer and composer' => [[$composer], [$anotherComposer], $composer, [$composer, $anotherComposer]],
            'composer, performer and transcriber' => [[$composer], [$performer, $transcriber], $composer, [$performer, $composer, $transcriber]],
        ];
    }
}
