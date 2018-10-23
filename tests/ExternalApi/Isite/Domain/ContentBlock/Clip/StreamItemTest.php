<?php
declare(strict_types = 1);
namespace Tests\App\ExternalApi\Isite\Domain\ContentBlock\Clip;

use App\Builders\ClipBuilder;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\StreamItem;
use PHPUnit\Framework\TestCase;

class StreamItemTest extends TestCase
{
    public function testCaptionHasPriorityOverProgrammeTitle()
    {
        $streamItem = new StreamItem(
            'caption 1',
            ClipBuilder::any()->with(['title' => 'programme title'])->build()
        );

        $this->assertEquals('caption 1', $streamItem->getTitle());
    }

    public function testTitleProgrammeIsUsedIfCaptionIsEmpty()
    {
        $streamItem = new StreamItem(
            '',
            ClipBuilder::any()->with(['title' => 'programme title'])->build()
        );

        $this->assertEquals('programme title', $streamItem->getTitle());
    }
}
