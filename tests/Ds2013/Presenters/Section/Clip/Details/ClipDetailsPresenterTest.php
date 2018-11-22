<?php
declare(strict_types = 1);

namespace Tests\App\Ds2013\Presenters\Section\Clip\Details;

use App\Builders\ClipBuilder;
use App\Builders\EpisodeBuilder;
use App\Ds2013\Presenters\Section\Clip\Details\ClipDetailsPresenter;
use App\DsShared\Helpers\PlayTranslationsHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\ValueObject\PartialDate;
use BBC\ProgrammesPagesService\Domain\ValueObject\Synopses;
use Cake\Chronos\Chronos;
use Symfony\Component\DomCrawler\Crawler;
use Tests\App\BaseTemplateTestCase;

/**
 * @group MapClip
 */
class ClipDetailsPresenterTest extends BaseTemplateTestCase
{
    /**
     * Presenter -- behaviour
     */
    public function testDataProvidedByPresenter()
    {
        $clip = ClipBuilder::any()->with([
            'releaseDate' => new PartialDate(2018, 10, 14),
            'streamableUntil' => Chronos::parse('+5 years'),

        ])->build();

        $clipDetailsPresenter = $this->presenter($clip);

        $this->assertInstanceOf(PartialDate::class, $clipDetailsPresenter->getReleaseDate());
        $this->assertTrue($clipDetailsPresenter->isAvailableIndefinitely(), 'When is streamable for more than 1 year is considered inifinite');
    }

    /**
     * Presenter HTML -- description
     */
    public function testHtmlCreatedWithoutReleaseDate()
    {
        $clip = ClipBuilder::any()->with([
            'title' => 'any title clip',
            'synopses' => new Synopses('short one', 'medium one', 'long one'),
            'releaseDate' => null,
            'streamableFrom' => new Chronos('2017-06-01 20:00:00'),
            'duration' => 1200,
            'parent' => EpisodeBuilder::any()->build(),
            'streamableUntil' => Chronos::parse('+2 months'),
            'isStreamable' => true,
        ])->build();

        $crawler = $this->renderDetailsPresenter($clip);

        $this->assertEquals('any title clip', $crawler->filter('.details__title')->text());
        $this->assertEquals('long one', trim($crawler->filter('.details__description')->text()));
        $this->assertEquals('01 June 2017', $crawler->filter('.details__streamablefrom')->text());
        $this->assertEquals('6 months left to watch', trim($crawler->filter('.details__streamableuntil')->text()));
        $this->assertEquals('2 minutes', trim($crawler->filter('.details__duration')->text()));
        $this->assertEquals(1, $crawler->filter('.clip-panel__clip-is-from .programme--episode')->count());
    }

    /**
     * HELPERS
     */
    private function renderDetailsPresenter(Clip $clip): Crawler
    {
        $clipDetailsPresenter = $this->presenter($clip);
        $html = $this->presenterHtml($clipDetailsPresenter);
        return new Crawler($html);
    }

    private function presenter(Clip $clip): ClipDetailsPresenter
    {
        $stubPlayTrans = $this->createConfiguredMock(PlayTranslationsHelper::class, [
            'secondsToWords' => '2 minutes',
            'translateAvailableUntilToWords' => '6 months left to watch',
        ]);

        return new ClipDetailsPresenter($stubPlayTrans, $clip, [], null, null);
    }
}
