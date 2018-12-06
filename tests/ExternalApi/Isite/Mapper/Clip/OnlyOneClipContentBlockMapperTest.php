<?php
declare(strict_types = 1);
namespace Tests\App\ExternalApi\Isite\Mapper\Clip;

use App\Builders\ClipBuilder;
use App\Builders\VersionBuilder;
use App\Controller\Helpers\IsiteKeyHelper;
use App\ExternalApi\IdtQuiz\IdtQuizService;
use App\ExternalApi\Isite\Domain\ContentBlock\ClipBlock\ClipStandAlone;
use App\ExternalApi\Isite\Mapper\ContentBlockMapper;
use App\ExternalApi\Isite\Mapper\MapperFactory;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
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

class OnlyOneClipContentBlockMapperTest extends TestCase
{
    private $givenClipsIndicatedByIsite;
    private $givenStreamableVersions;

    public function testMapperCanCreateAnStandAloneIfIsiteContainsOnlyOneClip()
    {
        $this->givenClipsIndicatedByIsite = [
            'p01f10w1' => $clip1 = ClipBuilder::any()->with(['pid' => new Pid('p01f10w1')])->build(),
        ];
        $this->givenStreamableVersions = [
            'p01f10w1' => VersionBuilder::any()->with(['programmeItem' => $clip1])->build(),
        ];

        $isiteResponse = new SimpleXMLElement(file_get_contents(__DIR__ . '/one_clip.xml'));
        $mapper = $this->mapper();
        $mapper->preloadData([$isiteResponse]);
        /** @var ClipStandAlone $block */
        $block = $mapper->getDomainModel($isiteResponse->result);

        $this->assertInstanceOf(ClipStandAlone::class, $block);
        $this->assertInstanceOf(Clip::class, $block->getClip());
    }

    public function testMapperCanCreateAnStandAloneForNonStreamableClip()
    {
        $this->givenClipsIndicatedByIsite = [
            'p01f10w1' => $clip1 = ClipBuilder::any()->with(['pid' => new Pid('p01f10w1')])->build(),
        ];
        $this->givenStreamableVersions = [];

        $isiteResponse = new SimpleXMLElement(file_get_contents(__DIR__ . '/one_clip.xml'));
        $mapper = $this->mapper();
        $mapper->preloadData([$isiteResponse]);
        /** @var ClipStandAlone $block */
        $block = $mapper->getDomainModel($isiteResponse->result);

        $this->assertInstanceOf(ClipStandAlone::class, $block);
        $this->assertInstanceOf(Clip::class, $block->getClip());
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
