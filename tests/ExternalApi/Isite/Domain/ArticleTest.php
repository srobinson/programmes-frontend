<?php
declare(strict_types = 1);

namespace Tests\App\ExternalApi\Isite\Domain;

use App\Builders\ArticleBuilder;
use PHPUnit\Framework\TestCase;

class ArticleTest extends TestCase
{
    /**
     * @dataProvider titlesAndSlugsProvider
     */
    public function testSlugify($withTitle, $expectSlug)
    {
        $article = ArticleBuilder::any()->with(['title' => $withTitle])->build();
        $this->assertSame($expectSlug, $article->getSlug());
    }

    public function titlesAndSlugsProvider()
    {
        return [
            'Alpha:' => ['title', 'title'],
            'Special-chars:' => ['Title-of an    article!@£$%^&*()', 'title-of-an-article'],
            'Quotes:' => ['A title~with "quotes" that should/ strip', 'a-title-with-quotes-that-should-strip'],
            'Apostrophes:' => ['A title~with apostrophe\'s that should/ strip', 'a-title-with-apostrophes-that-should-strip'],
            'Accents:' => ['A cööl titlé wîth accènts', 'a-cool-title-with-accents'],
        ];
    }

    public function testBBCSite()
    {
        $article = ArticleBuilder::any()->with(['bbc_site' => 'aboutthebbc'])->build();
        $this->assertEquals('aboutthebbc', $article->getBbcSite());
    }
}
