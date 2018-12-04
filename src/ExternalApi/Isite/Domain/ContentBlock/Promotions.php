<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain\ContentBlock;

class Promotions extends AbstractContentBlock
{
    /**
     * @var string[]
     */
    private $promotions;

    /**
     * @var string Can only be "list" or "grid"
     */
    private $layout;

    public function __construct(array $promotions, string $layout, ?string $title)
    {
        parent::__construct($title);
        $this->promotions = $promotions;
        $this->layout = $layout;
    }

    public function getPromotions(): array
    {
        return $this->promotions;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function getType(): string
    {
        return 'promotion';
    }
}
