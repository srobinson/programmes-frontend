<?php
declare(strict_types = 1);

namespace Tests\App\ExternalApi\Isite\Domain\ContentBlock\Clip;

use App\Builders\ClipBuilder;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\ClipStream;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\StreamItem;
use PHPUnit\Framework\TestCase;

/**
 * @group isite_clips
 */
class ClipStreamTest extends TestCase
{
    public function testBasicBehaviour()
    {
        $clipCarousel = new ClipStream(
            'title 1',
            $items = [
                new StreamItem('caption 1', ClipBuilder::any()->build()),
                new StreamItem('caption 1', ClipBuilder::any()->build()),
            ]
        );

        $this->assertContainsOnlyInstancesOf(StreamItem::class, $clipCarousel->getStreamItems(), 'Should return an array with the list of all the items');
        $this->assertEquals($items, $clipCarousel->getStreamItems());
    }
}
