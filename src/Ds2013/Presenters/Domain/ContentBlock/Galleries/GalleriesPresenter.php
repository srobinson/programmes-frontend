<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\ContentBlock\Galleries;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\Galleries;

class GalleriesPresenter extends ContentBlockPresenter
{
    public function __construct(Galleries $galleriesBlock, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($galleriesBlock, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }
}
