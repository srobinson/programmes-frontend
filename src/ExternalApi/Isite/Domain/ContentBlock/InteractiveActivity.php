<?php
declare(strict_types=1);

namespace App\ExternalApi\Isite\Domain\ContentBlock;

class InteractiveActivity extends AbstractContentBlock
{
    /** @var string */
    private $name;

    /** @var string */
    private $loaderUrl;

    /** @var string */
    private $path;

    /** @var string */
    private $width;

    /** @var string */
    private $height;

    public function __construct(
        ?string $title,
        string $name,
        string $loaderUrl,
        string $path,
        string $width,
        string $height
    ) {
        parent::__construct($title);

        $this->name = $name;
        $this->loaderUrl = $loaderUrl;
        $this->path = $path;
        $this->width = $width;
        $this->height = $height;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLoaderUrl(): string
    {
        return $this->loaderUrl;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getWidth(): string
    {
        return $this->width;
    }

    public function getHeight(): string
    {
        return $this->height;
    }

    public function getType(): string
    {
        return 'interactiveActivity';
    }
}
