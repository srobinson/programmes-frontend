<?php
namespace Tests\App\Ds2013\Presenters\Utilities\SMP;

use App\Builders\ClipBuilder;
use App\Builders\EpisodeBuilder;
use App\Builders\SegmentEventBuilder;
use App\Builders\VersionBuilder;
use App\Ds2013\Presenters\Utilities\SMP\SmpPresenter;
use App\DsShared\Helpers\GuidanceWarningHelper;
use App\DsShared\Helpers\SmpPlaylistHelper;
use App\DsShared\Helpers\StreamableHelper;
use App\ValueObject\CosmosInfo;
use BBC\ProgrammesPagesService\Domain\Enumeration\MediaTypeEnum;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SmpPresenterTest extends TestCase
{
    public function testGetSmpConfig()
    {
        $smpConfig = $this->presenter()->getSmpConfig();

        $this->assertEquals('#playout-st000001', $smpConfig['container']);
        $this->assertEquals('st000001', $smpConfig['pid']);
    }

    public function testSmpSettingsAutoplay()
    {
        $this->assertTrue($this->presenter()->getSmpConfig()['smpSettings']['autoplay']);
        $this->assertFalse($this->presenter(true, 'audio_video', false)->getSmpConfig()['smpSettings']['autoplay']);
    }

    public function testSmpSettingsUI()
    {
        $this->assertEquals([
            'controls' => ['enabled' => true, 'always' => false],
            'fullscreen' => ['enabled' => true],
        ], $this->presenter(true, MediaTypeEnum::VIDEO)->getSmpConfig()['smpSettings']['ui']);

        $this->assertEquals([
            'controls' => ['enabled' => true, 'always' => true],
            'fullscreen' => ['enabled' => false],
        ], $this->presenter(true, MediaTypeEnum::AUDIO)->getSmpConfig()['smpSettings']['ui']);
    }

    public function testStatsObject()
    {
        $this->assertEquals([
            'siteId' => 'bbc_____site',
            'product' => 'prod____name',
            'appName' => 'app____name',
            'appType' => 'responsive',
            'parentPID' => 'st000001',
            'parentPIDType' => 'clip',
            'sessionLabels' => [
                'bbc_site' => 'bbc_____site',
                'event_master_brand' => 'event___master___brand',
            ],
        ], $this->presenter()->getSmpConfig()['smpSettings']['statsObject']);
    }

    private function presenter(bool $useClip = true, $mediaType = 'audio_video', $autoplay = true): SmpPresenter
    {
        $stubRouter = $this->createConfiguredMock(UrlGeneratorInterface::class, [
            'generate' => 'stubbed/url/from/router',
        ]);

        if ($useClip) {
            $programme = ClipBuilder::any()->with(['pid' => new Pid('st000001'), 'mediaType' => $mediaType])->build();
        } else {
            $programme = EpisodeBuilder::any()->with(['pid' => new Pid('st000001'), 'mediaType' => $mediaType])->build();
        }

        $options = [];
        if ($autoplay === false) {
            $options = ['autoplay' => false];
        }

        return new SmpPresenter(
            $programme,
            VersionBuilder::any()->with(['pid' => new Pid('st000002')])->build(),
            [SegmentEventBuilder::any()->build(), SegmentEventBuilder::any()->build()],
            '',
            [
                'bbc_site' => 'bbc_____site',
                'prod_name' => 'prod____name',
                'app_name' => 'app____name',
                'event_master_brand' => 'event___master___brand',
            ],
            new SmpPlaylistHelper($this->createMock(GuidanceWarningHelper::class)),
            $stubRouter,
            $this->createMock(CosmosInfo::class),
            $this->createMock(StreamableHelper::class),
            $options
        );
    }
}
