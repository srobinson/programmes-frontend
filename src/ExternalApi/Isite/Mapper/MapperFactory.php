<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Mapper;

use App\Controller\Helpers\IsiteKeyHelper;
use App\ExternalApi\IdtQuiz\IdtQuizService;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use BBC\ProgrammesPagesService\Service\VersionsService;
use Psr\Log\LoggerInterface;

class MapperFactory
{
    protected $instances = [];

    /** @var LoggerInterface */
    private $logger;

    /** @var CoreEntitiesService */
    private $coreEntitiesService;

    /** @var ProgrammesService */
    private $programmesService;

    /** @var VersionsService */
    private $versionsService;

    private $isiteKeyHelper;

    /** @var IdtQuizService */
    private $idtQuizService;

    public function __construct(
        IsiteKeyHelper $isiteKeyHelper,
        CoreEntitiesService $coreEntitiesService,
        IdtQuizService $idtQuizService,
        ProgrammesService $programmesService,
        VersionsService $versionsService,
        LoggerInterface $logger
    ) {
        $this->isiteKeyHelper = $isiteKeyHelper;
        $this->coreEntitiesService = $coreEntitiesService;
        $this->idtQuizService = $idtQuizService;
        $this->programmesService = $programmesService;
        $this->versionsService = $versionsService;
        $this->logger = $logger;
    }

    public function createArticleMapper(): ArticleMapper
    {
        return $this->findMapper(ArticleMapper::class);
    }

    public function createContentBlockMapper(): ContentBlockMapper
    {
        if (!isset($this->instances[ContentBlockMapper::class])) {
            $this->instances[ContentBlockMapper::class] = new ContentBlockMapper(
                $this,
                $this->isiteKeyHelper,
                $this->coreEntitiesService,
                $this->idtQuizService,
                $this->programmesService,
                $this->versionsService,
                $this->logger
            );
        }
        return $this->instances[ContentBlockMapper::class];
    }

    public function createKeyFactMapper(): KeyFactMapper
    {
        return $this->findMapper(KeyFactMapper::class);
    }

    public function createProfileMapper(): ProfileMapper
    {
        return $this->findMapper(ProfileMapper::class);
    }

    public function createRowGroupMapper(): RowGroupMapper
    {
        return $this->findMapper(RowGroupMapper::class);
    }

    private function findMapper(string $mapperType)
    {
        if (!isset($this->instances[$mapperType])) {
            $this->instances[$mapperType] = new $mapperType($this, $this->isiteKeyHelper);
        }
        return $this->instances[$mapperType];
    }
}
