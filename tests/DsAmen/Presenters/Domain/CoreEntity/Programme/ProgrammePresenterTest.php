<?php
declare(strict_types=1);

namespace Tests\App\DsAmen\Presenters\Domain\CoreEntity\Programme;

use App\Builders\EpisodeBuilder;
use App\DsAmen\Presenters\Domain\CoreEntity\Programme\ProgrammePresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Programme\SubPresenter\CtaPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\BodyPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\ImagePresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\StreamableCtaPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\TitlePresenter;
use App\DsShared\Helpers\HelperFactory;
use BBC\ProgrammesPagesService\Domain\Entity\Brand;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Enumeration\MediaTypeEnum;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Routing\Generator\UrlGenerator;

class ProgrammePresenterTest extends TestCase
{
    /** @var UrlGenerator|PHPUnit_Framework_MockObject_MockObject */
    private $mockRouter;

    /** @var HelperFactory|PHPUnit_Framework_MockObject_MockObject */
    private $mockHelperFactory;

    /** @var Clip|PHPUnit_Framework_MockObject_MockObject */
    private $mockClip;

    public function setUp()
    {
        $this->mockRouter = $this->createMock(UrlGenerator::class);
        $this->mockHelperFactory = $this->createMock(HelperFactory::class);
        $this->mockClip = $this->createMock(Clip::class);
    }

    /** @dataProvider getBrandingClassProvider */
    public function testGetBrandingClass(string $brandingName, string $expected): void
    {
        $programmePresenter = new ProgrammePresenter(
            $this->mockClip,
            $this->mockRouter,
            $this->mockHelperFactory,
            ['branding_name' => $brandingName]
        );

        $this->assertSame($expected, $programmePresenter->getBrandingClass());
    }

    public function getBrandingClassProvider(): array
    {
        return [
            'No branding name returns empty branding class' => ['', ''],
            'Secondary branding name return br-box-secondary' => ['secondary', 'br-box-secondary'],
        ];
    }

    /** @dataProvider getMediaDetailsClassProvider */
    public function testGetMediaDetailsClass(string $mediaDetailsClass, bool $showImage, string $expected): void
    {
        $programmePresenter = new ProgrammePresenter(
            $this->mockClip,
            $this->mockRouter,
            $this->mockHelperFactory,
            [
                'media_details_class' => $mediaDetailsClass,
                'show_image' => $showImage,
            ]
        );

        $this->assertSame($expected, $programmePresenter->getMediaDetailsClass());
    }

    public function getMediaDetailsClassProvider(): array
    {
        return [
            'media_details_class empty return empty string' => [
                '',
                true,
                '',
            ],
            'show_image false returns media_details_class with media_details--noimage variation' => [
                'details',
                false,
                'details media__details--noimage',
            ],
            'media_details_class set with show_image return media_details_class' => [
                'details',
                true,
                'details',
            ],
        ];
    }

    public function testGetCtaPresenterReturnsInstanceOfStreamableCtaPresenterWhenStreamable(): void
    {
        $programmePresenter = new ProgrammePresenter($this->createMockClip(true), $this->mockRouter, $this->mockHelperFactory);
        $this->assertInstanceOf(StreamableCtaPresenter::class, $programmePresenter->getCtaPresenter());
    }

    public function testGetCtaPresenterReturnsNullWhenNotStreamable(): void
    {
        $programmePresenter = new ProgrammePresenter($this->createMockClip(false), $this->mockRouter, $this->mockHelperFactory);
        $this->assertNull($programmePresenter->getCtaPresenter());
    }

    public function testGetBodyPresenterReturnsInstanceOfSharedBodyPresenter(): void
    {
        $programmePresenter = new ProgrammePresenter($this->createMockClip(), $this->mockRouter, $this->mockHelperFactory);
        $this->assertInstanceOf(BodyPresenter::class, $programmePresenter->getBodyPresenter());
    }

    public function testGetImagePresenterReturnsInstanceOfSharedImagePresenter(): void
    {
        $programmePresenter = new ProgrammePresenter($this->createMockClip(), $this->mockRouter, $this->mockHelperFactory);
        $this->assertInstanceOf(ImagePresenter::class, $programmePresenter->getImagePresenter());
    }

    public function testGetTitlePresenterReturnsInstanceOfSharedTitlePresenter(): void
    {
        $programmePresenter = new ProgrammePresenter($this->createMockClip(), $this->mockRouter, $this->mockHelperFactory);
        $this->assertInstanceOf(TitlePresenter::class, $programmePresenter->getTitlePresenter());
    }

    /** @dataProvider showStandaloneCtaProvider */
    public function testShowStandaloneCta(Programme $programme, bool $showImage, bool $expected): void
    {
        $programmePresenter = new ProgrammePresenter(
            $programme,
            $this->mockRouter,
            $this->mockHelperFactory,
            ['show_image' => $showImage]
        );

        $this->assertSame($expected, $programmePresenter->showStandaloneCta());
    }

    public function showStandaloneCtaProvider(): array
    {
        $brand = $this->createMockBrand();

        return [
            'Streamable Programme Item without image returns true' => [$this->createMockClip(true), false, true],
            'Non streamable Programme Item returns false' => [$this->createMockClip(false), true, false],
            'Brand return false' => [$brand, true, false],
            'Show image true return false' => [$this->createMockClip(true), true, false],
        ];
    }

    /** @dataProvider validateOptionsProvider
     *  @expectedException App\Exception\InvalidOptionException
     */
    public function testValidateOptionsThrowsException(array $options): void
    {
        $clip = $this->createMockClip();

        new ProgrammePresenter(
            $clip,
            $this->mockRouter,
            $this->mockHelperFactory,
            $options
        );
    }

    public function validateOptionsProvider(): array
    {
        return [
            'Non-Programme context_programme' => [['context_programme' => new Pid('br000001')]],
            'Non-boolean show image' => [['show_image' => new Pid('br0000001')]],
            'Non-array image_options' => [['image_options' => true]],
            'Non-array title_options' => [['title_options' => true]],
            'Non-array body_options' => [['body_options' => true]],
        ];
    }

    /**
     * [ CTA stand alone ]. When streamable episode it depends on option "show_image". Doesnt matter type of programme
     *
     * @dataProvider showImageOptionProvider
     */
    public function testWhenNoDisplayedAndImageIsAlwaysStandAlone(bool $givenShowImageOption)
    {
        $presenter = new ProgrammePresenter(
            EpisodeBuilder::any()->with(['isStreamable' => true, 'mediaType' => MediaTypeEnum::VIDEO])->build(),
            $this->mockRouter,
            $this->mockHelperFactory,
            ['show_image' => $givenShowImageOption]
        );

        $this->assertEquals(!$givenShowImageOption, $presenter->showStandaloneCta());
    }

    public function showImageOptionProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * [ CTA duration ]. ProgrammePresenter configure properly CtaPresenter when option show_image=false
     *
     * @dataProvider episodesNotShowingImageProvider
     */
    public function testWeDontDisplayDurationWhenRadioAndStandAloneCTA($givenEpisode, $thenShowDuration)
    {
        $ctaSubPresenter = (new ProgrammePresenter(
            $givenEpisode,
            $this->mockRouter,
            $this->mockHelperFactory,
            ['show_image' => false]
        ))->getCtaPresenter();

        $this->assertEquals($thenShowDuration, $ctaSubPresenter->getOption('show_duration'));
    }

    public function episodesNotShowingImageProvider()
    {
        $thenShowDuration = true;
        $givenRadioEpisode = EpisodeBuilder::anyRadioEpisode()->with(['isStreamable' => true, 'mediaType' => MediaTypeEnum::AUDIO])->build();
        $givenTvEpisode = EpisodeBuilder::anyTVEpisode()->with(['isStreamable' => true, 'mediaType' => MediaTypeEnum::VIDEO])->build();

        return [
            'GIVEN TV EPISODE, THEN SHOW DURATION=TRUE' =>  [$givenTvEpisode, $thenShowDuration],
            'GIVEN RADIO EPISODE, THEN SHOW DURATION=FALSE' => [$givenRadioEpisode, !$thenShowDuration],
        ];
    }

    /**
     * [ CTA duration ]. ProgrammePresenter configure properly CtaPresenter when option show_image=true
     *
     * @dataProvider episodesShowingImageProvider
     */
    public function testCtaPresenterDisplayAlwaysDurationWhenShowImageOptionIsTrue($givenEpisode)
    {
        $ctaSubPresenter = (new ProgrammePresenter(
            $givenEpisode,
            $this->mockRouter,
            $this->mockHelperFactory,
            ['show_image' => true]
        ))->getCtaPresenter();

        $this->assertTrue($ctaSubPresenter->getOption('show_duration'));
    }

    public function episodesShowingImageProvider()
    {
        $givenRadioEpisode = EpisodeBuilder::anyRadioEpisode()->with(['isStreamable' => true, 'mediaType' => MediaTypeEnum::AUDIO])->build();
        $givenTvEpisode = EpisodeBuilder::anyTVEpisode()->with(['isStreamable' => true, 'mediaType' => MediaTypeEnum::VIDEO])->build();

        return [
            'GIVEN TV EPISODE' =>  [$givenTvEpisode],
            'GIVEN RADIO EPISODE' => [$givenRadioEpisode],
        ];
    }
    
    private function createMockClip(bool $hasPlayableDestination = false)
    {
        $mockClip = $this->createMock(Clip::class);
        $mockClip->method('getTitle')->willReturn('Clip 1');
        $mockClip->method('getPid')->willReturn(new Pid('p0000001'));
        $mockClip->method('getDuration')->willReturn(10);
        $mockClip->method('hasPlayableDestination')->willReturn($hasPlayableDestination);

        return $mockClip;
    }

    private function createMockBrand()
    {
        $mockBrand = $this->createMock(Brand::class);
        $mockBrand->method('getTitle')->willReturn('Brand 1');
        $mockBrand->method('getPid')->willReturn(new Pid('br000001'));

        return $mockBrand;
    }
}
