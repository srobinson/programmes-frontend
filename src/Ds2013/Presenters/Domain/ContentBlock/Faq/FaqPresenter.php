<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\ContentBlock\Faq;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\Faq;

class FaqPresenter extends ContentBlockPresenter
{
    public function __construct(Faq $faqBlock, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($faqBlock, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }
}
