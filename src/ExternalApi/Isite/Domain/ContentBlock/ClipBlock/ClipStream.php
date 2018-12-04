<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock;

use App\ExternalApi\Isite\Domain\ContentBlock\AbstractContentBlock;

class ClipStream extends AbstractContentBlock
{
    /** @var StreamItem[] */
    private $streamItems;

    public function __construct(string $title, array $streamItems = [])
    {
        parent::__construct($title);
        $this->streamItems = $streamItems;
    }

    /**
     * @return StreamItem[]
     */
    public function getStreamItems(): array
    {
        return $this->streamItems;
    }

    public function getType(): string
    {
        return 'clipStream';
    }
}
