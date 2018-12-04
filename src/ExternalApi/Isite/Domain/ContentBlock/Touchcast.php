<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain\ContentBlock;

class Touchcast extends AbstractContentBlock
{
    /** @var string */
    private $touchcastId;

    public function __construct(?string $title, string $touchcastId)
    {
        parent::__construct($title);
        $this->touchcastId = $touchcastId;
    }

    public function getTouchcastId(): string
    {
        return $this->touchcastId;
    }

    public function getType(): string
    {
        return 'touchcast';
    }
}
