<?php
declare(strict_types = 1);
namespace App\Ds2013\Presenters\Domain\ContentBlock\Clip\ClipStream;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\ClipStream;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\StreamItem;

class ClipStreamPresenter extends ContentBlockPresenter
{
    /** @var ClipStream */
    protected $block;

    public function __construct(ClipStream $stream, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($stream, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }

    /**
     * @return StreamItem[]
     */
    public function getStreamItems(): array
    {
        return $this->block->getStreamItems();
    }

    public function getFeaturedStreamItem(): StreamItem
    {
        $items = $this->block->getStreamItems();
        return reset($items);
    }

    public function getTitle(): string
    {
        return $this->block->getTitle();
    }
}
