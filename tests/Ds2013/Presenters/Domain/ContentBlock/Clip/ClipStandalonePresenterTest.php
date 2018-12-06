<?php
declare(strict_types = 1);
namespace Tests\App\Ds2013\Presenters\Domain\ContentBlock\Clip;

use App\Builders\ClipBuilder;
use App\Builders\VersionBuilder;
use App\Ds2013\Presenters\Domain\ContentBlock\Clip\ClipStandalone\ClipStandalonePresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\ClipStandAlone;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Version;
use PHPUnit\Framework\TestCase;

/**
 * @group isite_clips
 */
class ClipStandalonePresenterTest extends TestCase
{
    public function testBasicBehaviour()
    {
        $presenter = new ClipStandalonePresenter(
            $this->clipStandAlone(),
            true,
            true
        );

        $this->assertInstanceOf(Clip::class, $presenter->getClip());
        $this->assertInstanceOf(Version::class, $presenter->getStreamableVersion());
        $this->assertEquals('caption 1', $presenter->getCaption());
    }

    private function clipStandAlone(): ClipStandAlone
    {
        return new ClipStandAlone(
            'title 1',
            'caption 1',
            ClipBuilder::any()->build(),
            VersionBuilder::any()->build()
        );
    }
}
