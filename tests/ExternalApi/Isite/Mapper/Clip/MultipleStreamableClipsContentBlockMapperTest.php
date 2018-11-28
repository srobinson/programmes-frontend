<?php
declare(strict_types = 1);
namespace Tests\App\ExternalApi\Isite\Mapper\Clip;

use App\Builders\ClipBuilder;
use App\Builders\VersionBuilder;
use App\Controller\Helpers\IsiteKeyHelper;
use App\ExternalApi\IdtQuiz\IdtQuizService;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\ClipStandAlone;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\ClipStream;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\StreamItem;
use App\ExternalApi\Isite\Mapper\ContentBlockMapper;
use App\ExternalApi\Isite\Mapper\MapperFactory;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use BBC\ProgrammesPagesService\Service\VersionsService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

/**
 * @group isite_clips
 */
class MultipleStreamableClipsContentBlockMapperTest extends TestCase
{
    private $givenClipsIndicatedByIsite;
    private $givenStreamableVersions;

    public function testWhenExistVersionsForAllClipsWeHaveAnStream()
    {
        $this->givenClipsIndicatedByIsite = [
            'p01f10w1' => $clip1 = ClipBuilder::any()->with(['pid' => new Pid('p01f10w1')])->build(),
            'p02f20w2' => $clip2 = ClipBuilder::any()->with(['pid' => new Pid('p02f20w2')])->build(),
            'p03f30w3' => $clip3 = ClipBuilder::any()->with(['pid' => new Pid('p03f30w3')])->build(),
        ];

        $this->givenStreamableVersions = [
            'p01f10w1' => VersionBuilder::any()->with(['programmeItem' => $clip1])->build(),
            'p02f20w2' => VersionBuilder::any()->with(['programmeItem' => $clip2])->build(),
            'p03f30w3' => VersionBuilder::any()->with(['programmeItem' => $clip3])->build(),
        ];

        $isiteResponse = new SimpleXMLElement(file_get_contents(__DIR__ . '/three_clips.xml'));
        $mapper = $this->mapper();
        $mapper->preloadData([$isiteResponse]);
        /** @var ClipStream $block */
        $block = $mapper->getDomainModel($isiteResponse->result);

        $this->assertInstanceOf(ClipStream::class, $block);
        $this->assertCount(3, $block->getStreamItems());
        $this->assertContainsOnlyInstancesOf(StreamItem::class, $block->getStreamItems());
    }

    public function testNullIsReturnedIfThereIsNoneVersionForStreClips()
    {
        $this->givenClipsIndicatedByIsite = [
            'p01f10w1' => $clip1 = ClipBuilder::any()->with(['pid' => new Pid('p01f10w1')])->build(),
            'p02f20w2' => $clip2 = ClipBuilder::any()->with(['pid' => new Pid('p02f20w2')])->build(),
            'p03f30w3' => $clip3 = ClipBuilder::any()->with(['pid' => new Pid('p03f30w3')])->build(),
        ];
        $this->givenStreamableVersions = [];


        $isiteResponse = new SimpleXMLElement(file_get_contents(__DIR__ . '/three_clips.xml'));
        $mapper = $this->mapper();
        $mapper->preloadData([$isiteResponse]);
        $block = $mapper->getDomainModel($isiteResponse->result);

        $this->assertNull($block);
    }

    /**
     * Edge case
     *
     * Isite contains three clips, however we have streamable versions for only two of them. Then, the one
     * without streamable version is not included in the stream.
     */
    public function testDontExistVersionsForAllClipsWeHaveAnStream()
    {
        $this->givenClipsIndicatedByIsite = [
            'p01f10w1' => $clip1 = ClipBuilder::any()->with(['pid' => new Pid('p01f10w1')])->build(),
            'p02f20w2' => $clip2 = ClipBuilder::any()->with(['pid' => new Pid('p02f20w2')])->build(),
            'p03f30w3' => $clip3 = ClipBuilder::any()->with(['pid' => new Pid('p03f30w3')])->build(),
        ];

        $this->givenStreamableVersions = [
            'p02f20w2' => VersionBuilder::any()->with(['programmeItem' => $clip2])->build(),
            'p03f30w3' => VersionBuilder::any()->with(['programmeItem' => $clip3])->build(),
        ];

        $isiteResponse = new SimpleXMLElement(file_get_contents(__DIR__ . '/three_clips.xml'));
        $mapper = $this->mapper();
        $mapper->preloadData([$isiteResponse]);
        /** @var ClipStream $block */
        $block = $mapper->getDomainModel($isiteResponse->result);

        $this->assertInstanceOf(ClipStream::class, $block);
        $this->assertCount(2, $block->getStreamItems());
        $this->assertEquals('p02f20w2', (string) $block->getStreamItems()[0]->getClip()->getPid());
        $this->assertEquals('p03f30w3', (string) $block->getStreamItems()[1]->getClip()->getPid());
    }

    /**
     * Edge case
     *
     * Isite contains three clips, however we have only an streamable version for only one of it. So the result
     * is not anymore an stream but an standalone clip.
     */
    public function testAnStreamOfClipsCanConformAsStandAloneIfThereIsOnlyOneVersionForOneClip()
    {
        $this->givenClipsIndicatedByIsite = [
            'p01f10w1' => $clip1 = ClipBuilder::any()->with(['pid' => new Pid('p01f10w1')])->build(),
            'p02f20w2' => $clip2 = ClipBuilder::any()->with(['pid' => new Pid('p02f20w2')])->build(),
            'p03f30w3' => $clip3 = ClipBuilder::any()->with(['pid' => new Pid('p03f30w3')])->build(),
        ];

        $this->givenStreamableVersions = [
            'p02f20w2' => VersionBuilder::any()->with(['programmeItem' => $clip1])->build(),
        ];

        $isiteResponse = new SimpleXMLElement(file_get_contents(__DIR__ . '/three_clips.xml'));
        $mapper = $this->mapper();
        $mapper->preloadData([$isiteResponse]);
        /** @var ClipStandAlone $block */
        $block = $mapper->getDomainModel($isiteResponse->result);

        $this->assertInstanceOf(ClipStandAlone::class, $block);
        $this->assertEquals('p02f20w2', (string) $block->getClip()->getPid());
    }

    public function mapper(): ContentBlockMapper
    {
        return new ContentBlockMapper(
            $this->createMock(MapperFactory::class),
            $this->createMock(IsiteKeyHelper::class),
            $this->createConfiguredMock(CoreEntitiesService::class, [
                'findByPids' => $this->givenClipsIndicatedByIsite,
            ]),
            $this->createMock(IdtQuizService::class),
            $this->createMock(ProgrammesService::class),
            $this->createConfiguredMock(VersionsService::class, [
                'findStreamableVersionForProgrammeItems' => $this->givenStreamableVersions,
            ]),
            $this->createMock(LoggerInterface::class)
        );
    }
}
