<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain\ContentBlock;

class Table extends AbstractContentBlock
{
    /** @var string[] */
    private $headings;

    /** @var string[] */
    private $rows;

    public function __construct(string $title, array $headings, array $rows)
    {
        parent::__construct($title);
        $this->headings = $headings;
        $this->rows = $rows;
    }

    public function getHeadings(): array
    {
        return $this->headings;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function getType(): string
    {
        return 'table';
    }
}
