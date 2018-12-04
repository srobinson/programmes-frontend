<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain;

class Article extends BaseIsiteObject
{
    /** @var RowGroup[] */
    private $rowGroups;

    public function __construct(
        string $title,
        string $key,
        string $fileId,
        string $projectSpace,
        string $parentPid,
        ?string $shortSynopsis,
        string $brandingId,
        string $image,
        array $parents,
        array $rowGroups
    ) {
        parent::__construct($title, $fileId, $projectSpace, $parentPid, $brandingId, $image, $parents, $key, $shortSynopsis);
        $this->rowGroups = $rowGroups;
    }

    public function getRowGroups(): array
    {
        return $this->rowGroups;
    }
}
