<?php

namespace App\ExternalApi\Isite\Domain\ContentBlock;

class Telescope extends AbstractContentBlock
{
    /** @var string */
    private $voteId;

    /** @var string */
    private $name;

    public function __construct(
        ?string $title,
        string $voteId,
        string $name
    ) {
        parent::__construct($title);

        $this->voteId = $voteId;
        $this->name = $name;
    }

    public function getVoteId(): string
    {
        return $this->voteId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return 'telescope';
    }
}
