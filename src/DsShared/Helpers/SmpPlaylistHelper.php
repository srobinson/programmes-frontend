<?php
declare(strict_types = 1);
namespace App\DsShared\Helpers;

use BBC\ProgrammesPagesService\Domain\Entity\MusicSegment;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use BBC\ProgrammesPagesService\Domain\Entity\Segment;
use BBC\ProgrammesPagesService\Domain\Entity\SegmentEvent;
use BBC\ProgrammesPagesService\Domain\Entity\Version;
use BBC\ProgrammesPagesService\Domain\Entity\VersionType;

class SmpPlaylistHelper
{
    /** @var GuidanceWarningHelper */
    private $guidanceWarningHelper;

    public function __construct(GuidanceWarningHelper $guidanceWarningHelper)
    {
        $this->guidanceWarningHelper = $guidanceWarningHelper;
    }

    /**
     * Most masterbrands have a linked competition warning version PID in PIPs
     * This is a short clip that plays before the main programmeItem telling
     * people not to enter a competition in a non-live broadcast.
     * This is a default for masterbrands that lack suck a linked version.
     */
    private const DEFAULT_WARNING_VPID = 'p025x55x';

    /**
     * This method produces the legacy playlist.json still used by V2 and blogs
     *
     * @param ProgrammeItem $programmeItem
     * @param Version|null $streamableVersion
     * @param SegmentEvent[] $segmentEvents
     * @param Version[] $allStreamableVersions
     * @return array
     */
    public function getLegacyJsonPlaylist(
        ProgrammeItem $programmeItem,
        ?Version $streamableVersion,
        array $segmentEvents = [],
        array $allStreamableVersions = []
    ): array {
        $feed = [
            'info' => [
                'readme' => 'For the use of Radio, Music and Programmes only',
            ],
            'statsObject' => [
                'parentPID'     => (string) $programmeItem->getPid(),
                'parentPIDType' => $programmeItem->getType(),
            ],
            'defaultAvailableVersion' => null,
            'allAvailableVersions' => [],
            'holdingImage' => $this->makeUrlProtocolRelative($programmeItem->getImage()->getUrl(976, 549)),
        ];

        if ($programmeItem->isStreamable() && $streamableVersion) {
            $streamableVersionFeed = $this->getVersionFeed($programmeItem, $streamableVersion);
            $feed['defaultAvailableVersion'] = $streamableVersionFeed;
            if (!empty($segmentEvents)) {
                $feed['defaultAvailableVersion']['markers'] = $this->getMarkers($segmentEvents, $programmeItem);
            }
            if (empty($allStreamableVersions)) {
                // Supplying all versions is not usually necessary, and only done in the SmpPlaylistController
                // for legacy support reasons
                $feed['allAvailableVersions'] = [ $streamableVersionFeed ];
            } else {
                foreach ($allStreamableVersions as $version) {
                    $feed['allAvailableVersions'][] = $this->getVersionFeed($programmeItem, $version);
                }
            }
        }

        return $feed;
    }

    /**
     * This method returns the SMP config we actually use
     *
     * @param ProgrammeItem $programmeItem
     * @param Version $version
     * @return array
     */
    public function getSmpPlaylist(
        ProgrammeItem $programmeItem,
        Version $version
    ) : array {
        $feed = [
            'title' => $this->getFullTitle($programmeItem),
            'summary' => $programmeItem->getSynopses()->getShortestSynopsis(),
            'masterBrandName' => ($programmeItem->getMasterBrand() ? $programmeItem->getMasterBrand()->getName() : ''),
            'items' => [],
            'holdingImageURL' => $this->makeUrlProtocolRelative($programmeItem->getImage()->getRecipeUrl()),
            'guidance' => $this->getGuidanceWarnings($version),
            'embedRights' => $programmeItem->isExternallyEmbeddable() ? 'allowed' : 'blocked',
        ];
        if ($version->hasCompetitionWarning()) {
            $feed['items'][] = $this->getCompetitionWarning($version);
        }

        $feed['items'][] = [
            'vpid' => (string) $version->getPid(),
            'kind' => $programmeItem->isAudio() ? 'radioProgramme' : 'programme',
            'duration' => $version->getDuration(),
        ];
        return $feed;
    }

    /**
     * @param SegmentEvent[] $segmentEvents
     * @param ProgrammeItem $programmeItem
     * @return array
     */
    public function getMarkers(array $segmentEvents, ProgrammeItem $programmeItem): array
    {
        $markers = [];
        foreach ($segmentEvents as $segmentEvent) {
            $segment = $segmentEvent->getSegment();
            $offset = $segmentEvent->getOffset();
            $showTimings = $programmeItem->getOption('show_tracklist_timings');
            if ($segmentEvent->isChapter() && !is_null($offset) && !is_null($segment->getDuration())) {
                // Normal marker for chapter segment
                $markers[] = $this->getChapterMarker($segmentEvent, $segment);
            } elseif (!is_null($offset) && ($segment instanceof MusicSegment) && $showTimings) {
                // Special music marker when show_tracklist_timings is set in iSite
                $markers[] = $this->getMusicMarker($segmentEvent, $segment);
            }
        }
        return $markers;
    }

    private function getVersionFeed(ProgrammeItem $programmeItem, Version $version): array
    {
        $versionTypes = array_map(function (VersionType $versionType) {
            return $versionType->getType();
        }, $version->getVersionTypes());

        $feed = [
            'pid' => (string) $version->getPid(),
            'types' => $versionTypes,
            'smpConfig' => $this->getSmpPlaylist($programmeItem, $version),
            'markers' => [], // Added later and not to all versions for legacy compatibility reasons
        ];
        return $feed;
    }

    private function getFullTitle(ProgrammeItem $programmeItem): string
    {
        $programmes = $programmeItem->getAncestry();
        $programmes = array_reverse($programmes);
        $programmeTitles = array_map(function (Programme $programme) {
            return $programme->getTitle();
        }, $programmes);
        return implode(', ', $programmeTitles);
    }

    private function getGuidanceWarnings(Version $version): ?string
    {
        $codes = $version->getGuidanceWarningCodes();

        if (null === $codes) {
            return null;
        }

        return $this->guidanceWarningHelper->getText($codes);
    }

    private function getCompetitionWarning(Version $version): array
    {
        $warningPid = self::DEFAULT_WARNING_VPID;
        $masterBrand = $version->getProgrammeItem()->getMasterBrand();
        if ($masterBrand && $masterBrand->getCompetitionWarning()) {
            $warningPid = (string) $masterBrand->getCompetitionWarning()->getPid();
        }
        return [
            'vpid' => $warningPid,
            'kind' => 'warning',
        ];
    }

    private function getChapterMarker(SegmentEvent $segmentEvent, Segment $segment): array
    {
        $description = $segment->getTitle();
        if ($segment->getSynopses()->getShortSynopsis()) {
            $description .= ': ' . $segment->getSynopses()->getShortSynopsis();
        }
        return  [
            'id' => (string) $segment->getPid(),
            'type' => 'chapter',
            'start' => $segmentEvent->getOffset(),
            'end' => ($segmentEvent->getOffset() + $segment->getDuration()),
            'text' => (string) $segment->getTitle(),
            'description' => $description,
        ];
    }

    private function getMusicMarker(SegmentEvent $segmentEvent, Segment $segment): array
    {
        return [
            'id'    => (string) $segment->getPid(),
            'type'  => 'key',
            'start' => $segmentEvent->getOffset(),
            'text'  => (string) $segment->getTitle(),
        ];
    }

    private function makeUrlProtocolRelative(string $url)
    {
        // PROGRAMMES-5041: SMP currently can't handle https iChef URLs when
        // loaded from a http page. Work around this by making iChef URLs
        // protocol-relative by stripping off the protocol.
        return preg_replace('/^https?:(?=\/\/ichef.bbci.co.uk\/)/', '', $url);
    }
}
