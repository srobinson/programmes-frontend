<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain\ContentBlock;

class Image extends AbstractContentBlock
{
    /** @var string|null */
    private $caption;

    /** @var string */
    private $imageUrl;

    public function __construct(string $imageUrl, ?string $title, ?string $caption)
    {
        parent::__construct($title);

        $this->imageUrl = $imageUrl;
        $this->caption = $caption;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function getType(): string
    {
        return 'image';
    }
}
