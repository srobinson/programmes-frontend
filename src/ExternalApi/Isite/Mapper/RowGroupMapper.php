<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Mapper;

use App\ExternalApi\Isite\Domain\RowGroup;
use Exception;
use SimpleXMLElement;

class RowGroupMapper extends Mapper
{
    public function getDomainModels(SimpleXMLElement $rowsIteration): array
    {
        $contentBlocksList = [];
        foreach ($rowsIteration as $row) {
            // Article pages have 2 columns
            $contentBlocksList = array_merge($contentBlocksList, $this->extractBlocks($row, 'primary'));
            $contentBlocksList = array_merge($contentBlocksList, $this->extractBlocks($row, 'secondary'));
        }
        $this->mapperFactory->createContentBlockMapper()->preloadData($contentBlocksList);
        $rows = [];
        foreach ($rowsIteration as $row) {
            $rows[] = $this->getDomainModel($row);
        }
        return $rows;
    }

    public function getDomainModel(SimpleXMLElement $isiteObject): RowGroup
    {
        $contentBlocksMapper = $this->mapperFactory->createContentBlockMapper();
        $primaryRows = [];
        foreach ($this->extractBlocks($isiteObject, 'primary') as $row) {
            $primaryRows[] = $contentBlocksMapper->getDomainModel($row->result);
        }
        $secondaryRows = [];
        foreach ($this->extractBlocks($isiteObject, 'secondary') as $row) {
            $secondaryRows[] = $contentBlocksMapper->getDomainModel($row->result);
        }
        return new RowGroup(
            $primaryRows,
            $secondaryRows
        );
    }

    private function extractBlocks(SimpleXMLElement $isiteObject, string $type): array
    {
        //check if module is in the data
        if (empty($isiteObject->{$type})) {
            return [];
        }

        $blocks = $isiteObject->{$type};
        $name = $type . '-blocks';

        if (empty($blocks[0]->{$name})) {
            return [];
        }

        if (empty($blocks[0]->{$name}->result)) {
            throw new Exception('Blocks have not been fetched');
        }
        $contentBlocksList = [];
        foreach ($blocks as $block) {
            if ($this->isPublished($block->{$name})) { // Must be published
                $contentBlocksList[] = $block->{$name};
            }
        }

        return $contentBlocksList;
    }
}
