<?php
declare(strict_types = 1);
namespace Tests\App\ExternalApi\Isite\Mapper;

use App\Controller\Helpers\IsiteKeyHelper;
use App\ExternalApi\IdtQuiz\IdtQuizService;
use App\ExternalApi\Isite\Mapper\ContentBlockMapper;
use App\ExternalApi\Isite\Mapper\MapperFactory;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use BBC\ProgrammesPagesService\Service\VersionsService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MapperFactoryTest extends TestCase
{
    public function testCanCreateBlockMapper()
    {
        $factory = new MapperFactory(
            $this->createMock(IsiteKeyHelper::class),
            $this->createMock(CoreEntitiesService::class),
            $this->createMock(IdtQuizService::class),
            $this->createMock(ProgrammesService::class),
            $this->createMock(VersionsService::class),
            $this->createMock(LoggerInterface::class)
        );
        $mapper = $factory->createContentBlockMapper();

        $this->assertInstanceOf(ContentBlockMapper::class, $mapper);
    }
}
