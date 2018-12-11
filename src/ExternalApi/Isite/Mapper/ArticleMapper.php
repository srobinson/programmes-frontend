<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Mapper;

use App\ExternalApi\Isite\Domain\Article;
use SimpleXMLElement;

class ArticleMapper extends Mapper
{
    public function getDomainModel(SimpleXMLElement $isiteObject): Article
    {
        $form = $this->getForm($isiteObject);
        $formMetaData = $this->getFormMetaData($isiteObject);
        $projectSpace = $this->getProjectSpace($formMetaData);
        $key = $this->isiteKeyHelper->convertGuidToKey($this->getString($this->getMetaData($isiteObject)->guid));
        $title = $this->getString($formMetaData->title);
        $fileId = $this->getString($this->getMetaData($isiteObject)->fileId); // NOTE: This is the metadata fileId, not the form data file_id
        $image = $this->getString($formMetaData->image);
        // @codingStandardsIgnoreStart
        // Ignored PHPCS cause of snake variable fields included in the xml
        $shortSynopsis = $this->getString($formMetaData->short_synopsis);
        $parentPid = $this->getString($formMetaData->parent_pid);
        $brandingId = $this->getString($formMetaData->branding_id);
        $bbcSite = $this->getString($formMetaData->bbc_site) ?: null;

        $parents = [];
        if (!empty($formMetaData->parents->parent->result)) {
            foreach ($formMetaData->parents as $parent) {
                if ($this->isPublished($parent->parent)) {
                    $parents[] = $this->mapperFactory->createArticleMapper()->getDomainModel($parent->parent->result);
                }
            }
        }

        $rowGroups = [];
        if (!empty($form->rows->{'rows-iteration'}->primary[0]->{'primary-blocks'}->result) || !empty($form->rows->{'rows-iteration'}->secondary[0]->{'secondary-blocks'}->result)) {
            $rowGroups = $this->mapperFactory->createRowGroupMapper()->getDomainModels($form->rows->{'rows-iteration'});
        }
        // @codingStandardsIgnoreEnd

        return new Article($title, $key, $fileId, $projectSpace, $parentPid, $shortSynopsis, $brandingId, $image, $parents, $rowGroups, $bbcSite);
    }
}
