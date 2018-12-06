<?php
declare(strict_types = 1);
namespace App\Ds2013\Presenters\Domain\ContentBlock\Clip\ClipStandalone;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\ClipStandAlone;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Version;

class ClipStandalonePresenter extends ContentBlockPresenter
{
    /** @var ClipStandAlone */
    protected $block;

    public function __construct(ClipStandAlone $block, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($block, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }

    public function getClip(): Clip
    {
        return $this->block->getClip();
    }

    public function getCaption(): string
    {
        return $this->block->getCaption();
    }

    public function getStreamableVersion(): ?Version
    {
        return $this->block->getStreamableVersion();
    }
}
