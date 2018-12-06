<?php
declare(strict_types=1);

namespace App\Ds2013\Presenters\Domain\ContentBlock\InteractiveActivity;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\InteractiveActivity;

class InteractiveActivityPresenter extends ContentBlockPresenter
{
    public function __construct(InteractiveActivity $block, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($block, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }

    public function getBBCDownloadsUrl(): string
    {
        return '//downloads.bbc.co.uk/';
    }

    public function getParams(): array
    {
        /** @var InteractiveActivity $block */
        $block = $this->getBlock();
        $params = [
            'width' => $block->getWidth() . 'px',
            'height' => $block->getHeight() . 'px',
        ];

        $path = $block->getPath();
        if (!empty($path)) {
            $params['path'] = $this->getBBCDownloadsUrl() . $path;
        }

        return $params;
    }
}
