<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain;

use App\ExternalApi\Isite\DataNotFetchedException;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;

class Article
{
    /** @var Article[]|null */
    private $children;

    /** @var string */
    private $fileId;

    /** @var string */
    private $projectSpace;

    /** @var string */
    private $key;

    /** @var string */
    private $title;

    /** @var string */
    private $parentPid;

    /** @var string|null */
    private $shortSynopsis;

    /** @var string */
    private $brandingId;

    /** @var Article[] */
    private $parents;

    /** @var string */
    private $image;

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
        $this->title = $title;
        $this->key = $key;
        $this->fileId = $fileId;
        $this->projectSpace = $projectSpace;
        $this->parentPid = $parentPid;
        $this->shortSynopsis = $shortSynopsis;
        $this->brandingId = $brandingId;
        $this->image = $image;
        $this->parents = $parents;
        $this->rowGroups = $rowGroups;
    }

    /**
     * @return Article[]
     * @throws DataNotFetchedException
     */
    public function getChildren(): array
    {
        if ($this->children === null) {
            throw new DataNotFetchedException('Article children have not been queried for yet.');
        }

        return $this->children;
    }

    public function getFileId(): string
    {
        return $this->fileId;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getParents(): array
    {
        return $this->parents;
    }

    public function getParentPid(): ?Pid
    {
        return empty($this->parentPid) ? null : new Pid($this->parentPid);
    }

    public function getRowGroups(): array
    {
        return $this->rowGroups;
    }

    public function getShortSynopsis(): ?string
    {
        return $this->shortSynopsis;
    }

    public function getSlug()
    {
        $text = str_replace(['\'', '"'], '', $this->title);
        // string replace from http://stackoverflow.com/questions/2103797/url-friendly-username-in-php
        // will turn accented characters into plain english
        return strtolower(
            trim(
                preg_replace(
                    '~[^0-9a-z]+~i',
                    '-',
                    html_entity_decode(
                        preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($text, ENT_QUOTES, 'UTF-8')),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ),
                '-'
            )
        );
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getProjectSpace(): string
    {
        return $this->projectSpace;
    }

    public function getBrandingId(): string
    {
        return $this->brandingId;
    }

    public function setChildren(array $children)
    {
        $this->children = $children;
    }
}
