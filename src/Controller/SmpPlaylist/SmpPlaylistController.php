<?php
declare(strict_types = 1);

namespace App\Controller\SmpPlaylist;

use App\Controller\BaseController;
use App\Controller\Helpers\SmpPlaylistHelper;
use App\DsShared\Helpers\HelperFactory;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use BBC\ProgrammesPagesService\Service\SegmentEventsService;
use BBC\ProgrammesPagesService\Service\VersionsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SmpPlaylistController extends BaseController
{
    private const CACHE_SECONDS = 120;
    private const JSONP_CALLBACK_KEY = 'callback';

    public function __invoke(
        string $pid,
        HelperFactory $helperFactory,
        ProgrammesService $programmesService,
        VersionsService $versionsService,
        SegmentEventsService $segmentEventsService,
        Request $request
    ) {

        // Yes there is a reason for not using the ArgumentResolver, we want to avoid the redirect that it does
        // on programme options. It doesn't apply here.
        /** @var ProgrammeItem */
        $programmeItem = $programmesService->findByPidFull(new Pid($pid), 'ProgrammeItem');
        if (!$programmeItem) {
            throw new NotFoundHttpException(sprintf(
                'The item of type "%s" with PID "%s" was not found',
                'ProgrammeItem',
                $pid
            ));
        }
        $allStreamableVersions = $versionsService->findAllStreamableByProgrammeItem($programmeItem);
        $streamableVersion = empty($allStreamableVersions) ? null : $allStreamableVersions[0];

        $segmentEvents = [];
        if ($programmeItem->getSegmentEventCount()) {
            $segmentEvents = $segmentEventsService->findByProgrammeForCanonicalVersion($programmeItem);
        }
        $smpPlaylistHelper = $helperFactory->getSmpPlaylistHelper();
        $playlistFeed = $smpPlaylistHelper->getLegacyJsonPlaylist($programmeItem, $streamableVersion, $segmentEvents, $allStreamableVersions);

        $jsonResponse = new JsonResponse($playlistFeed);
        $jsonResponse->setPublic()->setMaxAge(self::CACHE_SECONDS);
        $jsonpCallback = $request->query->get(self::JSONP_CALLBACK_KEY);
        if (!is_null($jsonpCallback)) {
            $jsonResponse->setCallback($jsonpCallback);
        }

        return $jsonResponse;
    }
}
