<?php
declare(strict_types = 1);

namespace Tests\App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters;

use App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters\ProgrammeBodyPresenter;
use App\DsShared\Helpers\LocalisedDaysAndMonthsHelper;
use App\DsShared\Helpers\PlayTranslationsHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Brand;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProgrammeBodyPresenterTest extends TestCase
{
    private $mockRouter;

    private $mockTranslationsHelper;

    private $mockLocalisedDaysAndMonthsHelper;

    public function setUp()
    {
        $this->mockRouter = $this->createMock(UrlGeneratorInterface::class);
        $this->mockTranslationsHelper = $this->createMock(PlayTranslationsHelper::class);
        $this->mockLocalisedDaysAndMonthsHelper = $this->createMock(LocalisedDaysAndMonthsHelper::class);
    }

    public function testHasDefinedPositionUnderParentProgramme()
    {
        $programme = $this->createMock(Episode::class);
        $parentProgramme = $this->createMock(Brand::class);
        $programme->method('getParent')->willReturn($parentProgramme);
        $programme->method('getPosition')->willReturn(3);
        $parentProgramme->method('getExpectedChildCount')->willReturn(7);

        $programmePresenter = new ProgrammeBodyPresenter(
            $this->mockRouter,
            $this->mockTranslationsHelper,
            $this->mockLocalisedDaysAndMonthsHelper,
            $programme
        );
        $this->assertTrue($programmePresenter->hasDefinedPositionUnderParentProgramme());
    }

    public function testDoesNotHaveDefinedPositionUnderParentProgramme()
    {
        $programme = $this->createMock(Episode::class);
        $parentProgramme = $this->createMock(Brand::class);
        $programme->method('getParent')->willReturn($parentProgramme);
        $programme->method('getPosition')->willReturn(3);
        $parentProgramme->method('getExpectedChildCount')->willReturn(null);

        $programmePresenter = new ProgrammeBodyPresenter(
            $this->mockRouter,
            $this->mockTranslationsHelper,
            $this->mockLocalisedDaysAndMonthsHelper,
            $programme
        );
        $this->assertFalse($programmePresenter->hasDefinedPositionUnderParentProgramme());
    }

    public function testShouldShowDuration()
    {
        $programme = $this->createMock(Episode::class);
        $programme->expects($this->once())->method('getDuration')->willReturn(3600);

        $programmePresenter = new ProgrammeBodyPresenter(
            $this->mockRouter,
            $this->mockTranslationsHelper,
            $this->mockLocalisedDaysAndMonthsHelper,
            $programme,
            ['show_duration' => true]
        );
        $this->assertTrue($programmePresenter->shouldShowDuration());
    }

    public function testShouldNotShowDuration()
    {
        $programme = $this->createMock(Episode::class);

        $programmePresenter = new ProgrammeBodyPresenter(
            $this->mockRouter,
            $this->mockTranslationsHelper,
            $this->mockLocalisedDaysAndMonthsHelper,
            $programme,
            ['show_duration' => false]
        );
        $this->assertFalse($programmePresenter->shouldShowDuration());
    }
}
