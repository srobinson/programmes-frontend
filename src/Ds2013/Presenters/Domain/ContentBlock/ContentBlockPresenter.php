<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\ContentBlock;

use App\Ds2013\Presenter;
use App\ExternalApi\Isite\Domain\ContentBlock\AbstractContentBlock;

abstract class ContentBlockPresenter extends Presenter
{
    /** @var AbstractContentBlock */
    protected $block;
    /** @var bool */
    protected $inPrimaryColumn;
    /** @var bool */
    protected $isPrimaryColumnFullWith;

    public function __construct(AbstractContentBlock $block, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($options);
        $this->block = $block;
        $this->inPrimaryColumn = $inPrimaryColumn;
        $this->isPrimaryColumnFullWith = $isPrimaryColumnFullWith;
    }

    public function getBlock(): AbstractContentBlock
    {
        return $this->block;
    }

    public function getTemplateVariableName(): string
    {
        return 'content_block';
    }

    public function isInPrimaryColumn(): bool
    {
        return $this->inPrimaryColumn;
    }

    public function isPrimaryColumnFullWith(): bool
    {
        return $this->isPrimaryColumnFullWith;
    }
}
