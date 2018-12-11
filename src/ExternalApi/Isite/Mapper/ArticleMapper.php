<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Mapper;

use App\ExternalApi\Isite\Domain\Article;
use InvalidArgumentException;
use SimpleXMLElement;

class ArticleMapper extends Mapper
{
    public function getDomainModel(SimpleXMLElement $isiteObject): Article
    {
        $form = $this->getForm($isiteObject);
        $formMetaData = $this->getFormMetaData($isiteObject);
        $projectSpace = $this->getProjectSpace($formMetaData);
        $resultMetaData = $this->getMetaData($isiteObject);
        $guid = $this->getString($resultMetaData->guid);
        if (!$this->isArticle($resultMetaData)) {
            throw new InvalidArgumentException(
                sprintf(
                    "iSite form with guid %s attempted to be mapped as article, but is not a article, is a %s",
                    $guid,
                    (string) $resultMetaData->type
                )
            );
        }
        $key = $this->isiteKeyHelper->convertGuidToKey($guid);
        $title = $this->getString($formMetaData->title);
        $fileId = $this->getString($resultMetaData->fileId); // NOTE: This is the metadata fileId, not the form data file_id
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
                    if ($this->isArticle($this->getMetaData($parent->parent->result))) {
                        /**
                         * iSite does not prevent you from adding other things than articles (e.g. profiles)
                         * as parents of your article. Because of course it doesn't.
                         * This filters those out
                         */
                        $parents[] = $this->mapperFactory->createArticleMapper()->getDomainModel($parent->parent->result);
                    }
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

    private function isArticle(SimpleXMLElement $resultMetaData)
    {
        return (isset($resultMetaData->type) && (string) $resultMetaData->type === 'programmes-article');
    }
}
