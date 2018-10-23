<?php
declare(strict_types = 1);
namespace App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock;

use BBC\ProgrammesPagesService\Domain\Entity\Clip;

class StreamItem
{
    private $clip;
    private $caption;

    public function __construct(string $caption, Clip $clip)
    {
        $this->caption = $caption;
        $this->clip = $clip;
    }

    public function getClip(): Clip
    {
        return $this->clip;
    }

    public function getTitle(): string
    {
        if (empty($this->caption)) {
            return $this->clip->getTitle();
        }

        return $this->caption;
    }
}
