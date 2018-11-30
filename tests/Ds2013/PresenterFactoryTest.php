<?php
declare(strict_types = 1);
namespace Tests\App\Ds2013;

use App\Builders\AdaBuilder;
use App\Builders\ClipBuilder;
use App\Builders\ContributionBuilder;
use App\Builders\ExternalApi\Recipes\RecipeBuilder;
use App\Builders\VersionBuilder;
use App\Ds2013\PresenterFactory;
use App\Ds2013\Presenters\Domain\Broadcast\BroadcastPresenter;
use App\Ds2013\Presenters\Domain\ContentBlock\Clip\ClipPlayable\ClipPlayablePresenter;
use App\Ds2013\Presenters\Domain\ContentBlock\Clip\ClipStandalone\ClipStandalonePresenter;
use App\Ds2013\Presenters\Domain\ContentBlock\Clip\ClipStream\ClipStreamPresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\Programme\ProgrammePresenter;
use App\Ds2013\Presenters\Domain\Recipe\RecipePresenter;
use App\Ds2013\Presenters\Section\Clip\Details\ClipDetailsPresenter;
use App\Ds2013\Presenters\Section\Episode\Map\EpisodeMapPresenter;
use App\Ds2013\Presenters\Section\RelatedTopics\RelatedTopicsPresenter;
use App\Ds2013\Presenters\Utilities\Calendar\CalendarPresenter;
use App\Ds2013\Presenters\Utilities\DateList\DateListPresenter;
use App\Ds2013\Presenters\Utilities\Download\DownloadPresenter;
use App\DsShared\Helpers\HelperFactory;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\ClipStandAlone;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\ClipStream;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\StreamItem;
use App\Translate\TranslateProvider;
use App\ValueObject\CosmosInfo;
use BBC\ProgrammesPagesService\Domain\Entity\Broadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Podcast;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use BBC\ProgrammesPagesService\Domain\Entity\Version;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use App\DsShared\PresenterFactory as DsSharedPresenterFactory;
use Cake\Chronos\Chronos;
use Cake\Chronos\Date;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use RMP\Translate\Translate;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @covers \App\Ds2013\PresenterFactory
 */
class PresenterFactoryTest extends TestCase
{
    /** @var Translate|PHPUnit_Framework_MockObject_MockObject. */
    private $translate;

    /** @var UrlGeneratorInterface|PHPUnit_Framework_MockObject_MockObject */
    private $router;

    /** @var HelperFactory|PHPUnit_Framework_MockObject_MockObject */
    private $helperFactory;

    /** @var PresenterFactory */
    private $factory;

    public function setUp()
    {
        $this->translate = $this->createMock(Translate::class);
        $translateProvider = $this->createMock(TranslateProvider::class);
        $translateProvider->method('getTranslate')->willReturn($this->translate);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->helperFactory = $this->createMock(HelperFactory::class);
        $dummyCosmosInfo = $this->createMock(CosmosInfo::class);

        $this->factory = new PresenterFactory(new DsSharedPresenterFactory(), $translateProvider, $this->router, $this->helperFactory, $dummyCosmosInfo);
    }

    public function tearDown()
    {
        Chronos::setTestNow();
    }

    public function testOrganismBroadcast()
    {
        $mockBroadcast = $this->createMock(Broadcast::class);

        $this->assertEquals(
            new BroadcastPresenter($mockBroadcast, null, ['opt' => 'foo']),
            $this->factory->broadcastPresenter($mockBroadcast, null, ['opt' => 'foo'])
        );
    }

    public function testOrganismProgramme()
    {
        $mockProgramme = $this->createMock(Programme::class);

        $this->assertEquals(
            new ProgrammePresenter($this->router, $this->helperFactory, $mockProgramme, ['opt' => 'foo']),
            $this->factory->programmePresenter($mockProgramme, ['opt' => 'foo'])
        );
    }

    public function testMoleculeCalendar()
    {
        $now = Date::now();
        $mockService = $this->createMock(Service::class);

        $this->assertEquals(
            new CalendarPresenter($now, $mockService),
            $this->factory->calendarPresenter($now, $mockService)
        );
    }


    public function testMoleculeDateList()
    {
        $now = Chronos::now();
        $mockService = $this->createMock(Service::class);

        Chronos::setTestNow($now);

        $this->assertEquals(
            new DateListPresenter($this->router, $now, $mockService),
            $this->factory->dateListPresenter($now, $mockService)
        );
    }

    public function testCanCreateEpisodeMapPresenter()
    {
        $presenter = $this->anyEpisodeMapPresenter();

        $this->assertInstanceOf(EpisodeMapPresenter::class, $presenter);
        $this->assertEquals('episode_map', $presenter->getTemplateVariableName());
        $this->assertContains('episode_map.html.twig', $presenter->getTemplatePath());
    }

    public function testRelatedTopicPresenterCanBeCreated()
    {
        $ada = AdaBuilder::any()->build();
        $clipMock = $this->getMockBuilder(Clip::class)->disableOriginalConstructor()->getMock();
        $presenter = $this->factory->relatedTopicsPresenter([$ada], $clipMock);

        $this->assertInstanceOf(RelatedTopicsPresenter::class, $presenter);
    }

    /**
     * @group recipes
     */
    public function testCanCreateRecipesApiResultsPresenter()
    {
        $recipe = RecipeBuilder::any()->build();
        $options = ['key1' => 'value1'];

        $presenter = $this->factory->recipePresenter($recipe, $options);

        $this->assertInstanceOf(RecipePresenter::class, $presenter);
        $this->assertEquals('@Ds2013/Presenters/Domain/Recipe/recipe.html.twig', $presenter->getTemplatePath());
        $this->assertSame('value1', $presenter->getOption('key1'));
    }

    /**
     * @group MapClip
     */
    public function testCanCreateClipDetailsPresenter()
    {
        $options = ['key1' => 'value1'];
        $clip = ClipBuilder::any()->build();
        $contributions = [ContributionBuilder::any()->build()];
        $version = VersionBuilder::any()->build();
        $podcast = new Podcast($clip, 'weekly', -1, true, false);

        $presenter = $this->factory->clipDetailsPresenter($clip, $contributions, $version, $podcast, $options);

        $this->assertInstanceOf(ClipDetailsPresenter::class, $presenter);
        $this->assertEquals('@Ds2013/Presenters/Section/Clip/Details/clip_details.html.twig', $presenter->getTemplatePath());
        $this->assertEquals('clip_details', $presenter->getTemplateVariableName());
        $this->assertSame('value1', $presenter->getOption('key1'));
    }

    public function testCanCreateDownloadPresenter()
    {
        $clip = ClipBuilder::any()->build();
        $version = VersionBuilder::any()->build();
        $podcast = new Podcast($clip, 'weekly', -1, true, false);

        $presenter = $this->factory->downloadPresenter($clip, $version, $podcast, []);

        $this->assertInstanceOf(DownloadPresenter::class, $presenter);
    }

    /**
     * @group isite_clips
     */
    public function testItRepresentsAnStream()
    {
        $givenStream = new ClipStream(
            'title 1',
            [
                new StreamItem('caption 1', ClipBuilder::any()->build()),
                new StreamItem('caption 2', ClipBuilder::any()->build()),
            ]
        );

        $presenter = $this->factory->contentBlockPresenter($givenStream);

        $this->assertInstanceOf(ClipStreamPresenter::class, $presenter);
    }

    /**
     * @group isite_clips
     */
    public function testItRepresentAnStandAloneClipDifferentToAnStream()
    {
        $givenStandAloneClip = new ClipStandAlone(
            'title 1',
            'caption 1',
            ClipBuilder::any()->build(),
            VersionBuilder::any()->build()
        );

        $presenter = $this->factory->contentBlockPresenter($givenStandAloneClip);

        $this->assertInstanceOf(ClipStandalonePresenter::class, $presenter);
    }


    /**
     * helpers
     */
    private function anyEpisodeMapPresenter(bool $hasPodcast = false): EpisodeMapPresenter
    {
        $dummyEp = $this->createMock(Episode::class);
        $podcast = new Podcast($dummyEp, 'weekly', -1, true, false);
        $dummyCB = $this->createMock(CollapsedBroadcast::class);
        $dummyVersion = $this->createMock(Version::class);
        $dummyNull = null;
        $dummyArray = [];

        return $this->factory->episodeMapPresenter(
            $dummyEp,
            $dummyVersion,
            $dummyArray,
            $dummyCB,
            $dummyNull,
            $dummyNull,
            $dummyNull,
            $hasPodcast ? $podcast : null
        );
    }
}
