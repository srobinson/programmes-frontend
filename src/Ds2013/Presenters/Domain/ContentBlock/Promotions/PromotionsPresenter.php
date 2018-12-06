<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\ContentBlock\Promotions;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\Promotions;

class PromotionsPresenter extends ContentBlockPresenter
{
    public function __construct(Promotions $promotionsBlock, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($promotionsBlock, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }

    public function getPositionType(): string
    {
        if ($this->inPrimaryColumn) {
            return 'page';
        }

        return 'subtle';
    }

    public function getCssClasses(): string
    {
        $cssClasses = '';
        if ($this->isPrimaryColumnFullWith()) {
            // If there are 3 or 6 items then show 3 items per row, otherwise show 4 items per row
            if (in_array(count($this->getBlock()->getPromotions()), [3, 6])) {
                $cssClasses = ' 1/3@bpw2 1/3@bpe';
            } else {
                $cssClasses = ' 1/3@bpw2 1/4@bpe';
            }
        } elseif ($this->isInPrimaryColumn()) {
            $cssClasses = ' 1/3@bpe';
        }
        return $cssClasses;
    }
}
