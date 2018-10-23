<?php
declare(strict_types = 1);

namespace App\Twig;

use Twig_Extension;
use Twig_Function;

class StreamClipsExtension extends Twig_Extension
{
    private $streams = [];

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new Twig_Function('add_stream', [$this, 'addStream']),
            new Twig_Function('get_streams', [$this, 'getStreams']),
        ];
    }

    public function addStream(int $idStream)
    {
        $this->streams[] = $idStream;
    }

    /**
     * @return int[]
     */
    public function getStreams(): array
    {
        return $this->streams;
    }
}
