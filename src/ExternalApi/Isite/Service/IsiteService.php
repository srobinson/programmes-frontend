<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Service;

use App\ExternalApi\Client\HttpApiClientFactory;
use App\ExternalApi\Isite\Domain\Article;
use App\ExternalApi\Isite\Domain\Profile;
use App\ExternalApi\Isite\GuidQuery;
use App\ExternalApi\Isite\IsiteFeedResponseHandler;
use App\ExternalApi\Isite\IsiteResult;
use App\ExternalApi\Isite\SearchQuery;
use BBC\ProgrammesCachingLibrary\CacheInterface;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use Closure;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;

abstract class IsiteService
{
    /** @var string */
    protected $isiteKey = '';

    /** @var string */
    protected $baseUrl;

    /** @var HttpApiClientFactory */
    protected $clientFactory;

    /** @var IsiteFeedResponseHandler */
    protected $responseHandler;

    public function __construct(
        string $baseUrl,
        HttpApiClientFactory $clientFactory,
        IsiteFeedResponseHandler $responseHandler
    ) {
        $this->baseUrl = $baseUrl;
        $this->clientFactory = $clientFactory;
        $this->responseHandler = $responseHandler;
    }

    public function getByContentId(string $guid, bool $preview = false): PromiseInterface
    {
        $guidQuery = new GuidQuery();
        $guidQuery
            ->setContentId($guid)
            ->setDepth(1);

        if ($preview) {
            $guidQuery
                ->setPreview(true)
                ->setAllowNonLive(true);
        }

        $cacheKey = $this->clientFactory->keyHelper(__CLASS__, __FUNCTION__, $guid, $preview);

        $client = $this->clientFactory->getHttpApiMultiClient(
            $cacheKey,
            [$this->baseUrl . $guidQuery->getPath()],
            Closure::fromCallable([$this, 'parseResponse']),
            [],
            new IsiteResult(1, 1, 0, []),
            CacheInterface::NORMAL,
            CacheInterface::NONE,
            [
                'timeout' => 10,
            ],
            true
        );

        return $client->makeCachedPromise();
    }

    public function getByProgramme(
        Programme $programme,
        int $page = 1,
        int $limit = 48
    ): PromiseInterface {
        /** @var string $project */
        $project = $programme->getOption('project_space');

        if (!$project) {
            return new FulfilledPromise(new IsiteResult($page, $limit, 0, []));
        }

        $query = $this->getBaseQuery($project, $page, $limit);
        $query->setQuery([
            'and' => [
                [$this->isiteKey . ':parent_pid', '=', (string) $programme->getPid()],
                [
                    'not' => [
                        [$this->isiteKey . ':parent', 'contains', 'urn:isite'],
                    ],
                ],
            ],
        ]);

        $url = $this->baseUrl . $query->getPath();

        $cacheKey = $this->clientFactory->keyHelper(__CLASS__, __FUNCTION__, $programme->getPid(), $page, $limit);

        $client = $this->clientFactory->getHttpApiMultiClient(
            $cacheKey,
            [$url],
            Closure::fromCallable([$this, 'parseResponse']),
            [],
            new IsiteResult($page, $limit, 0, []),
            CacheInterface::NORMAL,
            CacheInterface::NONE,
            [
                'timeout' => 10,
            ],
            true
        );

        return $client->makeCachedPromise();
    }

    /**
     * @param Response[] $responses
     * @return IsiteResult[]
     */
    public function parseResponses(array $responses): array
    {
        $results = [];
        foreach ($responses as $key => $response) {
            $results[] = $this->responseHandler->getIsiteResult($response);
        }
        return $results;
    }

    /**
     * @param Response[] $responses
     * @return IsiteResult
     */
    public function parseResponse(array $responses): IsiteResult
    {
        return $this->responseHandler->getIsiteResult($responses[0]);
    }

    /**
     * @param Article[]|Profile[] $objects
     * @param string $project
     * @param int $page
     * @param int $limit
     * @return PromiseInterface
     */
    public function setChildrenOn(
        array $objects,
        string $project,
        int $page = 1,
        int $limit = 48
    ): PromiseInterface {
        if (empty($objects)) {
            return new FulfilledPromise([]);
        }

        $cacheKeys = [];
        $urls = [];
        foreach ($objects as $object) {
            $query = $this->getBaseQuery($project, $page, $limit);
            $query->setQuery([$this->isiteKey . ':parent', '=', 'urn:isite:' . $project . ':' . $object->getFileId()]);

            $urls[] = $this->baseUrl . $query->getPath();
            $cacheKeys[] = $object->getFileId();
        }

        $cacheKey = $this->clientFactory->keyHelper(__CLASS__, __FUNCTION__, implode(',', $cacheKeys), $page, $limit);

        $client = $this->clientFactory->getHttpApiMultiClient(
            $cacheKey,
            $urls,
            Closure::fromCallable([$this, 'parseResponses']),
            [$objects],
            [],
            CacheInterface::NORMAL,
            CacheInterface::NONE,
            [
                'timeout' => 10,
            ]
        );

        $promise = $client->makeCachedPromise();
        return $this->chainHydrationPromise($objects, $promise);
    }

    /**
     * @param Article[]|Profile[] $objects
     * @param PromiseInterface $promise
     * @return PromiseInterface
     */
    protected function chainHydrationPromise(array $objects, PromiseInterface $promise): PromiseInterface
    {
        return $promise->then(
            function ($responses) use ($objects) {
                // Success callback
                $this->hydrateObjects($objects, $responses);
                return $responses;
            },
            function ($error) use ($objects) {
                // Error callback
                $this->hydrateObjects($objects, []);
                return [];
            }
        );
    }

    protected function getBaseQuery(string $project, int $page, int $limit): SearchQuery
    {
        $query = new SearchQuery();
        $query->setNamespace($this->isiteKey, $project)
            ->setProject($project)
            ->setDepth(0)// no depth as this is an aggregation - no need to fetch parents or content blocks
            ->setSort($this->getDefaultSort())
            ->setPage($page)
            ->setPageSize($limit);

        return $query;
    }

    private function getDefaultSort(): array
    {
        return [
            [
                'elementPath' => '/' . $this->isiteKey . ':form/' . $this->isiteKey . ':metadata/' . $this->isiteKey . ':position',
                'type' => 'numeric',
                'direction' => 'asc',
            ],
            [
                'elementPath' => '/' . $this->isiteKey . ':form/' . $this->isiteKey . ':metadata/' . $this->isiteKey . ':title',
                'direction' => 'asc',
            ],
        ];
    }

    private function hydrateObjects(array $objects, array $responses): void
    {
        foreach ($objects as $key => $object) {
            $childObjects = (isset($responses[$key]) ? $responses[$key]->getDomainModels() : []);
            $childCount = (isset($responses[$key]) ? $responses[$key]->getTotal() : 0);
            $object->setChildren($childObjects);
            $object->setChildCount($childCount);
        }
    }
}
