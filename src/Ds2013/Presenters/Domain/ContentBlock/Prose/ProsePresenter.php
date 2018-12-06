<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\ContentBlock\Prose;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\Prose;

class ProsePresenter extends ContentBlockPresenter
{
    /** @var Prose */
    protected $block;

    public function __construct(Prose $proseBlock, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($proseBlock, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }

    /**
     * Cleanup the content and split into paragraphs (Same as v2)
     * @return string[]
     */
    public function getParagraphs(): array
    {
        $paragraphs = [];
        $rawParagraphs = explode('</p>', $this->block->getProse());
        foreach ($rawParagraphs as $paragraph) {
            $paragraph = $this->cleanupText($paragraph);
            if (!empty($paragraph) && strlen($paragraph) > 5) { // paragraphs shorter than 5 characters are spacing characters
                $paragraph = $this->fixMarkup($paragraph);
                $paragraphs[] = $paragraph;
            }
        }
        return $paragraphs;
    }

    /**
     * Only If there is more than one paragraphs, display the first one as a header.
     * The function is used to wrap the media between getHeaderParagraph() and getFooterParagraphs() when there is more
     * than one paragraphs
     *
     * @return string
     */
    public function getHeaderParagraph():string
    {
        $paragraphs = $this->getParagraphs();
        if (1 === count($paragraphs)) {
            return '';
        }
        return $paragraphs[0];
    }

    /**
     * Display the remaining paragraphs
     *
     * @return array
     */
    public function getFooterParagraphs():array
    {
        $paragraphs = $this->getParagraphs();
        if (1 === count($paragraphs)) {
            return $paragraphs;
        }
        // of there is more than 1 paragraphs, remove the header paragraphs
        array_shift($paragraphs);
        return $paragraphs;
    }

    /**
     * Fix the markup so it's valid and semantic (Same as v2)
     *
     * @param string $paragraph
     * @return string
     */
    private function fixMarkup(string $paragraph): string
    {
        $paragraph = str_replace('shape="rect"', '', $paragraph); // for some reason iSite does this on links
        $paragraph = str_replace('<ul>', '</p><ul>', $paragraph);
        $paragraph = str_replace('<ol>', '</p><ol>', $paragraph);
        $paragraph = str_replace('</ul>', '</ul><p>', $paragraph);
        $paragraph = str_replace('</ol>', '</ol><p>', $paragraph);
        $paragraph = preg_replace("/^<\/p>/", '', $paragraph);
        $paragraph = preg_replace("/<p>$/", '', $paragraph);
        $paragraph = '<p>' . $paragraph . '</p>';
        $paragraph = preg_replace("/^<p><(ul|ol|h2|h3|h4|h5|h6|p)>/", "<$1>", $paragraph);
        $paragraph = preg_replace("/<(\/ul|\/ol|\/h2|\/h3|\/h4|\/h5|\/h6|\/p)><\/p>$/", '<$1>', $paragraph);
        $paragraph = trim($paragraph);
        return $paragraph;
    }

    private function cleanupText($content): string
    {
        $content = strip_tags($content, '<a><ul><ol><li><strong><em><h2><h3><h4><h5><h6><p><br>');
        $content = str_replace('&nbsp;', ' ', $content); // spaces shouldn't be none-breaking spaces
        $content = trim($content);
        return $content;
    }
}
