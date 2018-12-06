<?php
declare(strict_types = 1);

namespace Tests\App\Ds2013\Presenters\Domain\ContentBlock\Clip;

use App\Builders\ClipBuilder;
use App\Ds2013\Presenters\Domain\ContentBlock\Clip\ClipStream\ClipStreamPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\ClipStream;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\StreamItem;
use PHPUnit\Framework\TestCase;

/**
 * @group isite_clips
 */
class ClipStreamPresenterTest extends TestCase
{
    public function testBasicBehaviour()
    {
        $carousel = new ClipStream(
            'title 1',
            $items = [
                new StreamItem('caption 1', ClipBuilder::any()->build()),
                new StreamItem('caption 1', ClipBuilder::any()->build()),
            ]
        );

        $presenter = new ClipStreamPresenter($carousel, true, true);

        $this->assertContainsOnlyInstancesOf(StreamItem::class, $presenter->getStreamItems());
        $this->assertEquals('title 1', $presenter->getTitle());
    }

    public function testCanProvideTheFeaturedClip()
    {
        $carousel = new ClipStream(
            'title 1',
            [
                $featured = new StreamItem('caption 1', ClipBuilder::any()->build()),
                new StreamItem('caption 1', ClipBuilder::any()->build()),
            ]
        );

        $presenter = new ClipStreamPresenter($carousel, true, true);

        $this->assertEquals($featured, $presenter->getFeaturedStreamItem(), "The featured clip should be the first item in the stream");
    }
}
