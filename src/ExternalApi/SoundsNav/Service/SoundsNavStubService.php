<?php
declare(strict_types = 1);

namespace App\ExternalApi\SoundsNav\Service;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;

class SoundsNavStubService extends SoundsNavService
{
    public function getContent(): PromiseInterface
    {
        return new FulfilledPromise(null);
    }
}
