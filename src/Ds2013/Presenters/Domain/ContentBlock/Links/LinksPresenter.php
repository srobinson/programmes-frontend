<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\ContentBlock\Links;

use App\Ds2013\Presenters\Domain\ContentBlock\ContentBlockPresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\Links;

class LinksPresenter extends ContentBlockPresenter
{
    public function __construct(Links $linksBlock, bool $inPrimaryColumn, bool $isPrimaryColumnFullWith, array $options = [])
    {
        parent::__construct($linksBlock, $inPrimaryColumn, $isPrimaryColumnFullWith, $options);
    }

    public function extractExternalHost(string $url): ?string
    {
        preg_match('@^(?:http[s]*://)?([^/?]+)@i', $url, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        $host = $matches[1];
        $checkHostForBeeb = strpos($host, 'bbc.co.uk');
        if ($checkHostForBeeb === false) {
            return $host;
        }

        return null;
    }
}
