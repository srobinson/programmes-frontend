<?php
declare(strict_types = 1);
namespace App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock;

use App\ExternalApi\Isite\Domain\ContentBlock\AbstractContentBlock;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Version;

class ClipStandAlone extends AbstractContentBlock
{
    /** @var string */
    private $caption;

    /** @var Clip */
    private $clip;

    /** @var Version */
    private $streamableVersion;

    public function __construct(string $title, string $caption, Clip $clip, ?Version $streamableVersion)
    {
        parent::__construct($title);
        $this->caption = $caption;
        $this->clip = $clip;
        $this->streamableVersion = $streamableVersion;
    }

    public function getClip(): Clip
    {
        return $this->clip;
    }

    public function getCaption(): string
    {
        return $this->caption;
    }

    public function getStreamableVersion(): ?Version
    {
        return $this->streamableVersion;
    }

    public function getType(): string
    {
        return 'clipStandAlone';
    }
}
