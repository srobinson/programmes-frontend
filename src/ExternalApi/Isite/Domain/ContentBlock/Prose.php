<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain\ContentBlock;

use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Version;
use InvalidArgumentException;

class Prose extends AbstractContentBlock
{
    /** @var string */
    private $prose;

    /** @var string|null */
    private $image;

    /** @var string|null */
    private $imageCaption;

    /** @var string|null */
    private $quote;

    /** @var string|null */
    private $quoteAttribution;

    /** @var Clip|null */
    private $clip;

    /** @var string|null */
    private $mediaPosition;

    /**  @var Version|null */
    private $streamableVersion;


    public function __construct(
        ?string $title,
        string $prose,
        ?string $image,
        ?string $imageCaption,
        ?string $quote,
        ?string $quoteAttribution,
        ?Clip $clip,
        string $mediaPosition,
        ?Version $streamableVersion
    ) {
        parent::__construct($title);
        $this->prose = $prose;
        $this->image = $image;
        $this->imageCaption = $imageCaption;
        $this->quote = $quote;
        $this->quoteAttribution = $quoteAttribution;
        $this->clip = $clip;
        $this->mediaPosition = $mediaPosition;
        $this->streamableVersion = $streamableVersion;
    }

    public function getProse(): string
    {
        return $this->prose;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getImageCaption(): ?string
    {
        return $this->imageCaption;
    }

    public function getQuote(): ?string
    {
        return $this->quote;
    }

    public function getQuoteAttribution(): ?string
    {
        return $this->quoteAttribution;
    }

    public function getClip(): ?Clip
    {
        return $this->clip;
    }

    public function getMediaPosition(): ?string
    {
        return $this->mediaPosition;
    }

    public function getStreamableVersion(): ?Version
    {
        return $this->streamableVersion;
    }

    public function getType(): string
    {
        return 'prose';
    }
}
