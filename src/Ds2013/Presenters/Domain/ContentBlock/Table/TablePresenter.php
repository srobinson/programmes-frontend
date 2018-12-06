<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\ContentBlock\Table;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\Table;

class TablePresenter extends ContentBlockPresenter
{
    public function __construct(Table $tableBlock, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($tableBlock, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }

    public function getCellClasses(): string
    {
        if ($this->inPrimaryColumn) {
            return 'br-box-subtle br-page-bg-onborder';
        }

        return 'br-box-page br-subtle-bg-onborder';
    }

    public function getHeaderClasses(): string
    {
        if ($this->inPrimaryColumn) {
            return 'br-box-highlight br-page-bg-onborder';
        }

        return 'br-box-highlight br-subtle-bg-onborder';
    }
}
