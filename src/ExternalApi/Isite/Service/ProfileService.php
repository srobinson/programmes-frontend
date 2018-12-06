<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Service;

use App\ExternalApi\Isite\Domain\Profile;
use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use BBC\ProgrammesCachingLibrary\CacheInterface;

class ProfileService extends IsiteService
{
    protected $isiteKey = 'profile';

    /**
     * Queries for children of a list of profiles. Only queries those profiles that are groups.
     * The GroupSize field in the profile is respected and used to set the number of children to query.
     *
     * @param Profile[] $profiles
     * @param int $defaultLimit
     * @return PromiseInterface
     */
    public function setGroupChildrenOn(
        array $profiles,
        int $defaultLimit = 48
    ) {
        $cacheKeys = [];
        $urls = [];
        foreach ($profiles as $profile) {
            if (!$profile->isGroup()) {
                continue;
            }
            $maxSiblings = $defaultLimit;
            $groupSize = $profile->getGroupSize();
            if (!is_null($groupSize)) {
                // number of siblings displayed cannot be more than the maximum
                $maxSiblings = min($defaultLimit, $groupSize);
            }
            // Get the siblings of the current profile
            $queryLimit = ($maxSiblings > 0 ? $maxSiblings : 1);

            $query = $this->getBaseQuery($profile->getProjectSpace(), 1, $queryLimit);
            $query->setQuery([$this->isiteKey . ':parent', '=', 'urn:isite:' . $profile->getProjectSpace() . ':' . $profile->getFileId()]);

            $urls[] = $this->baseUrl . $query->getPath();
            $cacheKeys[] = $profile->getFileId();
        }

        if (empty($urls)) {
            return new FulfilledPromise([]);
        }

        $cacheKey = $this->clientFactory->keyHelper(__CLASS__, __FUNCTION__, implode(',', $cacheKeys), $defaultLimit);

        $client = $this->clientFactory->getHttpApiMultiClient(
            $cacheKey,
            $urls,
            Closure::fromCallable([$this, 'parseResponses']),
            [$profiles],
            [],
            CacheInterface::NORMAL,
            CacheInterface::NONE,
            [
                'timeout' => 10,
            ]
        );

        $promise = $client->makeCachedPromise();
        return $this->chainHydrationPromise($profiles, $promise);
    }
}
