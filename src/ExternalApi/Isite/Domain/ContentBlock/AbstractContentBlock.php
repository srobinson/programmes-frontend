<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain\ContentBlock;

abstract class AbstractContentBlock
{
    /** @var string|null */
    private $title;

    public function __construct(?string $title)
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    abstract public function getType(): string;
}
