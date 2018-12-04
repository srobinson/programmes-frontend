<?php
declare(strict_types=1);

namespace App\ExternalApi\Isite\Domain\ContentBlock;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;

class Quiz extends AbstractContentBlock
{
    /** @var string */
    private $name;

    /** @var string */
    private $quizId;

    /** @var string */
    private $htmlContent;

    public function __construct(
        ?string $title,
        ?string $name,
        string $quizId,
        string $htmlContent
    ) {
        parent::__construct($title);

        $this->name = $name;
        $this->quizId = $quizId;
        $this->htmlContent = $htmlContent;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getQuizId(): string
    {
        return $this->quizId;
    }

    public function getHtmlContent(): string
    {
        return $this->htmlContent;
    }

    public function getType(): string
    {
        return 'quiz';
    }
}
