<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain\ContentBlock;

use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;

class Galleries extends AbstractContentBlock
{
    /** @var Pid[] */
    private $galleries;

    public function __construct(?string $title, array $galleries)
    {
        parent::__construct($title);
        $this->galleries = $galleries;
    }

    public function getGalleries(): array
    {
        return $this->galleries;
    }

    public function getType(): string
    {
        return 'galleries';
    }
}
