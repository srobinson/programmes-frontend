<?php
declare(strict_types = 1);

namespace Tests\App\Ds2013\Presenters\Domain\CoreEntity\SharedSubPresenters;

use App\Builders\ClipBuilder;
use App\Builders\EpisodeBuilder;
use App\Builders\MasterBrandBuilder;
use App\Builders\NetworkBuilder;
use App\Ds2013\Presenters\Domain\CoreEntity\SharedSubPresenters\CoreEntityTitlePresenter;
use App\DsShared\Helpers\StreamableHelper;
use App\DsShared\Helpers\TitleLogicHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Brand;
use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;
use BBC\ProgrammesPagesService\Domain\Enumeration\MediaTypeEnum;
use BBC\ProgrammesPagesService\Domain\ValueObject\Nid;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tests\App\BaseTemplateTestCase;
use Tests\App\TwigEnvironmentProvider;

class CoreEntityTitlePresenterTest extends BaseTemplateTestCase
{
    private $mockRouter;

    private $mockTitleLogicHelper;

    private $streamUrlHelper;

    public function setUp()
    {
        $this->mockRouter = $this->createMock(UrlGeneratorInterface::class);
        $this->mockTitleLogicHelper = $this->createMock(TitleLogicHelper::class);
        $this->streamUrlHelper = $this->createMock(StreamableHelper::class);
    }

    public function testGetTitleLinkUrl()
    {
        $this->mockRouter->expects($this->once())
            ->method('generate')
            ->with('find_by_pid', ['pid' => 'b006m86d'])
            ->willReturn('/programmes/b006m86d');

        $programme = $this->createMock(Brand::class);
        $programme->expects($this->once())->method('getPid')->willReturn(new Pid('b006m86d'));
        $programmeBodyPresenter = new CoreEntityTitlePresenter(
            $this->mockRouter,
            $this->mockTitleLogicHelper,
            $programme,
            $this->streamUrlHelper
        );
        $this->assertEquals('/programmes/b006m86d', $programmeBodyPresenter->getTitleLinkUrl());
    }

    public function testGetTitleLinkUrlForClip()
    {
        $router = TwigEnvironmentProvider::getSymfonyRouter();
        $network = NetworkBuilder::any()->with(['nid' => new Nid('bbc_radio_three')])->build();

        // Audio Clip whitelisted to go to playspace
        $masterBrand = MasterBrandBuilder::any()->with(['network' => $network, 'streamableInPlayspace' => true])->build();
        $playspaceAudioClip = ClipBuilder::any()->with([
            'mediaType' => MediaTypeEnum::AUDIO,
            'masterBrand' => $masterBrand,
            'pid' => new Pid('b09z67gw'),
        ])->build();

        $programmeBodyPresenter = new CoreEntityTitlePresenter(
            $router,
            $this->mockTitleLogicHelper,
            $playspaceAudioClip,
            new StreamableHelper()
        );

        // Asserts
        $this->assertEquals('http://localhost/radio/play/b09z67gw', $programmeBodyPresenter->getTitleLinkUrl());
    }

    public function testSubtitleIsNosDisplayedForAudioProgrammes()
    {
        $clip = ClipBuilder::any()->with([
            'title' => 'clip title',
            'mediaType' => MediaTypeEnum::AUDIO,
            'parent' => EpisodeBuilder::any()->with([
                'title' => 'episode title',
            ])->build(),
        ])->build();

        $crawler = $this->renderTitlePresenterForClip($clip);

        $this->thenTitleIs('episode title', $crawler);
        $this->assertSame(0, $crawler->filter('.programme__subtitle')->count());
    }

    public function testSubtitleIsDisplayedForAnyOtherCase()
    {
        $clip = EpisodeBuilder::any()->with([
            'title' => 'episode child title',
            'mediaType' => MediaTypeEnum::AUDIO,
            'parent' => EpisodeBuilder::any()->with([
                'title' => 'episode parent title',
            ])->build(),
        ])->build();

        $crawler = $this->renderTitlePresenterForClip($clip);

        $this->thenTitleIs('episode parent title', $crawler);
        $this->thenSubtitleIs('episode child title', $crawler);
    }

    public function testTitleCanBeOverriden()
    {
        $clip = EpisodeBuilder::any()->with([
            'title' => 'episode child title',
            'mediaType' => MediaTypeEnum::AUDIO,
            'parent' => EpisodeBuilder::any()->with([
                'title' => 'episode parent title',
            ])->build(),
        ])->build();

        $crawler = $this->renderTitlePresenterForClip($clip, ['override_title' => 'new title']);

        $this->thenTitleIs('new title', $crawler);
        $this->thenSubtitleIs('episode child title', $crawler);
    }

    private function renderTitlePresenterForClip(CoreEntity $programme, array $options = []): Crawler
    {
        $stubRouter = $this->createConfiguredMock(UrlGeneratorInterface::class, ['generate' => 'whatever']);
        $presenter = new CoreEntityTitlePresenter(
            $stubRouter,
            new TitleLogicHelper(),
            $programme,
            $this->streamUrlHelper,
            $options
        );

        return new Crawler($this->presenterHtml($presenter));
    }

    private function thenTitleIs($expectedText, Crawler $crawler)
    {
        $this->assertEquals($expectedText, $crawler->filter('.programme__title')->text());
    }

    private function thenSubtitleIs($expectedText, Crawler $crawler)
    {
        $this->assertEquals($expectedText, $crawler->filter('.programme__subtitle')->text());
    }
}
