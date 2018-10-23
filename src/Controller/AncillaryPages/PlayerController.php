<?php
declare(strict_types = 1);

namespace App\Controller\AncillaryPages;

use App\Controller\BaseController;
use App\DsShared\Helpers\TitleLogicHelper;
use App\ValueObject\MetaContext;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Service\SegmentEventsService;
use BBC\ProgrammesPagesService\Service\VersionsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PlayerController extends BaseController
{
    public function __invoke(
        Request $request,
        UrlGeneratorInterface $router,
        SegmentEventsService $segmentEventsService,
        TitleLogicHelper $titleLogicHelper,
        VersionsService $versionsService,
        Clip $clip
    ) {
        // This frame needs to display off the BBC site
        $this->response()->headers->remove('X-Frame-Options');
        $suffix = ' - BBC';
        if ($clip->getTleo()->getNetwork()) {
            $suffix = ' - ' . $clip->getTleo()->getNetwork()->getName();
        }

        $longerTitleParts = [];
        foreach ($clip->getAncestry() as $ancestor) {
            $longerTitleParts[] = $ancestor->getTitle();
        }

        $twitterTitle = implode(', ', $longerTitleParts) . $suffix;

        $hasChrome = null === $request->get('chromeless');
        $subtitle = implode(', ', array_map(function (Programme $t) {
            return $t->getTitle();
        }, $titleLogicHelper->getOrderedProgrammesForTitle($clip, null, 'item::ancestry')[1]));

        $context = new MetaContext($clip, $router->generate('programme_player', ['pid' => $clip->getPid()]));

        $available = $clip->isExternallyEmbeddable() && $clip->isStreamable();
        $unavailableMessage = '';
        if (!$available) {
            if ($clip->hasFutureAvailability()) {
                $unavailableMessage = 'clip_availability_future';
            } else {
                $unavailableMessage = 'clip_availability_none';
            }
        }

        $linkedVersions = $versionsService->findLinkedVersionsForProgrammeItem($clip);

        $segmentEvents = [];
        if ($clip->getSegmentEventCount() > 0) {
            $segmentEvents = $segmentEventsService->findByProgrammeForCanonicalVersion($clip);
        }

        return $this->render(
            'ancillary_pages/player.html.twig',
            [
                'available' => $available,
                'context' => $context,
                'clip' => $clip,
                'has_chrome' => $hasChrome,
                'segment_events' => $segmentEvents,
                'streamable_version' => $linkedVersions['streamableVersion'],
                'subtitle' => $subtitle,
                'twitter_title' => $twitterTitle,
                'unavailable_message' => $unavailableMessage,
            ]
        );
    }
}
