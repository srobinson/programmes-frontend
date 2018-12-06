<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\ContentBlock\Touchcast;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\Touchcast;

class TouchcastPresenter extends ContentBlockPresenter
{
    public function __construct(Touchcast $touchcastBlock, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($touchcastBlock, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }
}
