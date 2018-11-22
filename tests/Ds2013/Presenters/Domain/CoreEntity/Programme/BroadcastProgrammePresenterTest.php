<?php
declare(strict_types = 1);

namespace Tests\App\Ds2013\Presenters\Domain\CoreEntity\Programme;

use App\Ds2013\Presenters\Domain\CoreEntity\Programme\CollapsedBroadcastProgrammePresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\Programme\CollapsedBroadcastSubPresenters\CollapsedBroadcastProgrammeBodyPresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\Programme\CollapsedBroadcastSubPresenters\CollapsedBroadcastProgrammeOverlayPresenter;
use App\DsShared\Helpers\HelperFactory;
use App\DsShared\Helpers\LiveBroadcastHelper;
use App\DsShared\Helpers\LocalisedDaysAndMonthsHelper;
use App\DsShared\Helpers\PlayTranslationsHelper;
use App\DsShared\Helpers\TitleLogicHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Brand;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BroadcastProgrammePresenterTest extends TestCase
{
    /** @var UrlGeneratorInterface|PHPUnit_Framework_MockObject_MockObject */
    private $mockRouter;

    /** @var HelperFactory|PHPUnit_Framework_MockObject_MockObject */
    private $mockHelperFactory;

    private $mockLocalisedDaysAndMonthsHelper;

    public function setUp()
    {
        $this->mockRouter = $this->createMock(UrlGeneratorInterface::class);
        $this->mockHelperFactory = $this->createMock(HelperFactory::class);
        $playTranslationHelper = $this->createMock(PlayTranslationsHelper::class);
        $this->mockHelperFactory->method('getPlayTranslationsHelper')->willReturn($playTranslationHelper);
        $titleLogicHelper = $this->createMock(TitleLogicHelper::class);
        $this->mockHelperFactory->method('getTitleLogicHelper')->willReturn($titleLogicHelper);
        $liveBroadcastHelper = $this->createMock(LiveBroadcastHelper::class);
        $this->mockHelperFactory->method('getLiveBroadcastHelper')->willReturn($liveBroadcastHelper);
        $this->mockLocalisedDaysAndMonthsHelper = $this->createMock(LocalisedDaysAndMonthsHelper::class);
    }

    public function testGetBroadcastProgrammeOverlayPresenterAndOptions()
    {
        $options = [
            'branding_context' => 'subtle',
            'context_programme' => $this->createMock(Brand::class),
            'advanced_live' => true,
            'context_service' => $this->createMock(Service::class),
            'truncation_length' => 50,
            'image_options' => [
                'is_lazy_loaded' => false,
                'sizes' => [
                    0 => '0vw',
                    320 => 1 / 6,
                    480 => 1 / 2,
                    600 => 1,
                ],
            ],
        ];
        $programme = $this->createMock(Brand::class);
        $collapsedBroadcast = $this->createMock(CollapsedBroadcast::class);

        $presenter = new CollapsedBroadcastProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $collapsedBroadcast,
            $programme,
            $options
        );

        $broadcastProgrammeOverlayPresenter = $presenter->getProgrammeOverlayPresenter();
        $this->assertInstanceOf(CollapsedBroadcastProgrammeOverlayPresenter::class, $broadcastProgrammeOverlayPresenter);
        $expectedOptions = array_merge($options, $options['image_options']);
        unset($expectedOptions['image_options']);
        foreach ($expectedOptions as $key => $value) {
            $this->assertEquals($value, $broadcastProgrammeOverlayPresenter->getOption($key), $key);
        }
    }

    public function testGetBroadcastProgrammeBodyPresenterAndOptions()
    {
        $options = [
            'branding_context' => 'page',
            'context_programme' => $this->createMock(Brand::class),
            'advanced_live' => true,
            'context_service' => $this->createMock(Service::class),
            'truncation_length' => null,
            'body_options' => [
                'show_synopsis' => false,
                'show_duration' => true,
            ],
        ];
        $programme = $this->createMock(Brand::class);
        $collapsedBroadcast = $this->createMock(CollapsedBroadcast::class);

        $presenter = new CollapsedBroadcastProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $collapsedBroadcast,
            $programme,
            $options
        );

        $broadcastProgrammeBodyPresenter = $presenter->getProgrammeBodyPresenter();
        $this->assertInstanceOf(CollapsedBroadcastProgrammeBodyPresenter::class, $broadcastProgrammeBodyPresenter);
        $expectedOptions = array_merge($options, $options['body_options']);
        unset($expectedOptions['body_options']);
        foreach ($expectedOptions as $key => $value) {
            $this->assertEquals($value, $broadcastProgrammeBodyPresenter->getOption($key), $key);
        }
    }

    /**
     * @expectedException \App\Exception\InvalidOptionException
     */
    public function testInvalidContextService()
    {
        $options = [
            'context_service' => 'Queen Cleopatra\'s favourite brand of tinned soup',
        ];
        $programme = $this->createMock(Clip::class);
        $broadcast = $this->createMock(CollapsedBroadcast::class);
        $programmePresenter = new CollapsedBroadcastProgrammePresenter(
            $this->mockRouter,
            $this->mockHelperFactory,
            $broadcast,
            $programme,
            $options
        );
    }
}
