<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\ContentBlock\Image;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\Image;

class ImagePresenter extends ContentBlockPresenter
{
    public function __construct(Image $imageBlock, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($imageBlock, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }
}
