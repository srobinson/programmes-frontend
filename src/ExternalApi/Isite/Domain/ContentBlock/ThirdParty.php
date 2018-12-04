<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain\ContentBlock;

class ThirdParty extends AbstractContentBlock
{
    /** @var string URL of 3rd party page */
    private $url;

    /** @var string */
    private $altText;

    /** @var string */
    private $name;

    public function __construct(
        ?string $title,
        string $url,
        string $altText,
        ?string $name
    ) {
        parent::__construct($title);

        $this->name = $name;
        $this->url = $url;
        $this->altText = $altText;
    }

    public function getURL(): string
    {
        return $this->url;
    }

    public function getAltText(): string
    {
        return $this->altText;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return 'thirdParty';
    }
}
