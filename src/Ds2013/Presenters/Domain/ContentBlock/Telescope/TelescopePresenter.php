<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\ContentBlock\Telescope;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\Telescope;

class TelescopePresenter extends ContentBlockPresenter
{
    public function __construct(Telescope $telescopeBlock, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($telescopeBlock, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }
}
