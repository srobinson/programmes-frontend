<?php
declare(strict_types = 1);
namespace Tests\App\Twig;

use App\Twig\StreamClipsExtension;
use PHPUnit\Framework\TestCase;

class StreamClipsExtensionTest extends TestCase
{
    public function testCanAddStreams()
    {
        $extension = new StreamClipsExtension();
        $extension->addStream(3213213);
        $extension->addStream(12122234324);

        $streams = $extension->getStreams();

        $this->assertEquals([3213213, 12122234324], $streams);
    }
}
