<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain\ContentBlock;

class Faq extends AbstractContentBlock
{
    /** @var string */
    private $intro;

    /** @var string[] */
    private $questions;

    public function __construct(?string $title, string $intro, array $questions)
    {
        parent::__construct($title);
        $this->intro = $intro;
        $this->questions = $questions;
    }

    public function getIntro(): ?string
    {
        return $this->intro;
    }

    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function getType(): string
    {
        return 'faq';
    }
}
