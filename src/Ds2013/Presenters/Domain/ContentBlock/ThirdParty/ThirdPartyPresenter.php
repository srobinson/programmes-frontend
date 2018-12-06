<?php

namespace App\Ds2013\Presenters\Domain\ContentBlock\ThirdParty;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\ThirdParty;

class ThirdPartyPresenter extends ContentBlockPresenter
{
    public function __construct(ThirdParty $thirdPartyBlock, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($thirdPartyBlock, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }
}
