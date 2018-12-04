<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain\ContentBlock;

class Links extends AbstractContentBlock
{
    /**
     * Key => Value array
     * Title => Url
     *
     * @var string[]
     */
    private $links;

    public function __construct(?string $title, array $links)
    {
        parent::__construct($title);

        $this->links = $links;
    }

    public function getLinks(): array
    {
        return $this->links;
    }

    public function getType(): string
    {
        return 'links';
    }
}
