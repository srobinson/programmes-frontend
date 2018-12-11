<?php

namespace App\ExternalApi\Isite\Domain;

use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use App\ExternalApi\Isite\DataNotFetchedException;

abstract class BaseIsiteObject
{
    /** @var string */
    protected $title;

    /** @var string */
    protected $fileId;

    /** @var string */
    protected $projectSpace;

    /** @var string */
    protected $parentPid;

    /** @var string */
    protected $brandingId;

    /** @var string */
    protected $image;

    /** @var BaseIsiteObject[] */
    protected $parents;

    /** @var int */
    protected $childCount;

    /** @var array */
    protected $children;

    /** @var string */
    protected $key;

    /** @var string|null */
    protected $shortSynopsis;

    /** @var string|null */
    private $bbcSite;

    public function __construct(
        string $title,
        string $fileId,
        string $projectSpace,
        string $parentPid,
        string $brandingId,
        string $image,
        array $parents,
        string $key,
        ?string $shortSynopsis,
        ?string $bbcSite
    ) {
        $this->title = $title;
        $this->fileId = $fileId;
        $this->projectSpace = $projectSpace;
        $this->parentPid = $parentPid;
        $this->brandingId = $brandingId;
        $this->image = $image;
        $this->parents = $parents;
        $this->key = $key;
        $this->shortSynopsis = $shortSynopsis;
        $this->bbcSite = $bbcSite;
    }

    public function getFileId(): string
    {
        return $this->fileId;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getParents(): array
    {
        return $this->parents;
    }

    public function getParent(): ?Profile
    {
        $parent = reset($this->parents);

        return $parent ?: null;
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

    /**
     * @throws DataNotFetchedException
     */
    public function getChildCount(): int
    {
        if ($this->childCount === null) {
            throw new DataNotFetchedException('Profile children have not been queried for yet.');
        }

        return $this->childCount;
    }

    public function setChildCount(int $childCount)
    {
        $this->childCount = $childCount;
    }

    /**
     * @throws DataNotFetchedException
     */
    public function getChildren(): array
    {
        if ($this->children === null) {
            throw new DataNotFetchedException('Article children have not been queried for yet.');
        }

        return $this->children;
    }

    public function getParentPid(): ?Pid
    {
        return empty($this->parentPid) ? null : new Pid($this->parentPid);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getShortSynopsis(): ?string
    {
        return $this->shortSynopsis;
    }

    public function setChildren(array $children)
    {
        $this->children = $children;
    }

    public function getBbcSite(): ?string
    {
        return $this->bbcSite;
    }
}
