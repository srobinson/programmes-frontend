<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Mapper;

use App\ExternalApi\Isite\Domain\Profile;
use SimpleXMLElement;

class ProfileMapper extends Mapper
{
    public function getDomainModel(SimpleXMLElement $isiteObject): Profile
    {
        $form = $this->getForm($isiteObject);
        $formMetaData = $this->getFormMetaData($isiteObject);
        $projectSpace = $this->getProjectSpace($formMetaData);
        $key = $this->isiteKeyHelper->convertGuidToKey($this->getString($this->getMetaData($isiteObject)->guid));
        $title = $this->getString($formMetaData->title);
        $type = $this->getString($formMetaData->type);
        $fileId = $this->getString($this->getMetaData($isiteObject)->fileId); // NOTE: This is the metadata fileId, not the form data file_id
        $image = $this->getString($formMetaData->image);
        // @codingStandardsIgnoreStart
        // Ignored PHPCS cause of snake variable fields included in the xml
        $shortSynopsis = $this->getString($formMetaData->short_synopsis);
        $longSynopsis = $this->getString($formMetaData->long_synopsis);
        $parentPid = $this->getString($formMetaData->parent_pid);
        $brandingId = $this->getString($formMetaData->branding_id);
        $imagePortrait = $this->getString($form->profile->image_portrait);
        $tagline = $this->getString($formMetaData->tagline);

        $keyFacts = [];
        if (!empty($form->key_facts)) {
            $facts = $form->key_facts->key_fact;
            foreach ($facts as $fact) {
                $keyFacts[] = $this->mapperFactory->createKeyFactMapper()->getDomainModel($fact);
            }
        }

        $parents = [];
        if (!empty($formMetaData->parents->parent->result)) {
            foreach ($formMetaData->parents as $parent) {
                if ($this->isPublished($parent->parent)) {
                    $parents[] = $this->mapperFactory->createProfileMapper()->getDomainModel($parent->parent->result);
                }
            }
        }

        $contentBlocks = [];
        //check if module is in the data
        if (!empty($form->profile->content_blocks)) {
            $blocks = $form->profile->content_blocks;
            if (empty($blocks[0]->content_block->result)) {
                $contentBlocks = null; // Content blocks have not been fetched
            } else {
                $contentBlocksList = [];
                foreach ($blocks as $block) {
                    if ($this->isPublished($block->content_block)) { // Must be published
                        $contentBlocksList[] = $block->content_block;
                    }
                }
                $contentBlocks = $this->mapperFactory->createContentBlockMapper()->getDomainModels(
                    $contentBlocksList
                );
            }
        }

        $onwardJourneyBlock = null;
        if (!empty($form->profile->onward_journeys)) {
            if ($this->isPublished($form->profile->onward_journeys)) { // Must be published
                $onwardJourneyBlock = $this->mapperFactory->createContentBlockMapper()->getDomainModel(
                    $form->profile->onward_journeys->result
                );
            }
        }
        // @codingStandardsIgnoreEnd

        return new Profile($title, $key, $fileId, $type, $projectSpace, $parentPid, $shortSynopsis, $longSynopsis, $brandingId, $contentBlocks, $keyFacts, $image, $imagePortrait, $onwardJourneyBlock, $tagline, $parents);
    }
}
