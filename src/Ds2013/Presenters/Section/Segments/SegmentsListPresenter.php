<?php
declare(strict_types=1);

namespace App\Ds2013\Presenters\Section\Segments;

use App\Ds2013\Presenter;
use App\Ds2013\Presenters\Section\Segments\SegmentItem\AbstractMusicSegmentItemPresenter;
use App\Ds2013\Presenters\Section\Segments\SegmentItem\AbstractSegmentItemPresenter;
use App\Ds2013\Presenters\Section\Segments\SegmentItem\ClassicalMusicPresenter;
use App\Ds2013\Presenters\Section\Segments\SegmentItem\GroupPresenter;
use App\Ds2013\Presenters\Section\Segments\SegmentItem\PopularMusicPresenter;
use App\Ds2013\Presenters\Section\Segments\SegmentItem\SpeechPresenter;
use App\DsShared\Helpers\LiveBroadcastHelper;
use App\DsShared\Helpers\PlayTranslationsHelper;
use App\Exception\InvalidOptionException;
use BBC\ProgrammesPagesService\Domain\ApplicationTime;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Contribution;
use BBC\ProgrammesPagesService\Domain\Entity\MusicSegment;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use BBC\ProgrammesPagesService\Domain\Entity\Segment;
use BBC\ProgrammesPagesService\Domain\Entity\SegmentEvent;

class SegmentsListPresenter extends Presenter
{
    /** @var SegmentEvent[] */
    private $segmentEvents;

    /** @var LiveBroadcastHelper */
    private $liveBroadcastHelper;

    /** @var PlayTranslationsHelper */
    private $playTranslationsHelper;

    /** @var ProgrammeItem */
    private $context;

    /** @var CollapsedBroadcast|null */
    private $firstBroadcast;

    /** @var bool */
    private $hasMusicSegmentItems = false;

    /** @var bool */
    private $hasChapterSegments = false;

    /** @var AbstractSegmentItemPresenter[]|null  */
    protected $segmentItemsPresenters;

    /**
     * This is set to true when there are Music segments with offsets and the
     * programme is live
     *
     * @var bool
     */
    private $isReversed = false;

    protected $options = [
        'h_tag_level' => 2,
    ];

    /**
     * @param LiveBroadcastHelper $liveBroadcastHelper
     * @param PlayTranslationsHelper $playTranslationsHelper
     * @param ProgrammeItem $context
     * @param SegmentEvent[] $segmentEvents
     * @param CollapsedBroadcast|null $firstBroadcast
     * @param array $options
     */
    public function __construct(
        LiveBroadcastHelper $liveBroadcastHelper,
        PlayTranslationsHelper $playTranslationsHelper,
        ProgrammeItem $context,
        array $segmentEvents,
        ?CollapsedBroadcast $firstBroadcast,
        array $options = []
    ) {
        parent::__construct($options);
        $this->liveBroadcastHelper = $liveBroadcastHelper;
        $this->playTranslationsHelper = $playTranslationsHelper;
        $this->context = $context;
        $this->firstBroadcast = $firstBroadcast;
        $this->segmentEvents = $segmentEvents;
        $this->segmentItemsPresenters = $this->getSegmentItemsPresenters();
    }

    public function getTitle(): string
    {
        // hasMusicSegmentItems and hasChapterSegments
        // were precalculated in getSegmentItemsPresenters
        if ($this->hasMusicSegmentItems) {
            if ($this->hasChapterSegments) {
                return 'music_and_featured';
            }

            return 'music_played';
        }

        if ($this->hasChapterSegments) {
            return 'chapters';
        }

        return 'featured';
    }

    public function getSegmentEvents(): array
    {
        return $this->segmentEvents;
    }

    public function getMorelessClass(): string
    {
        return $this->hasMoreless() ? 'ml@bpb1' : '';
    }

    public function getHeadingTag(): string
    {
        return 'h' . $this->getOption('h_tag_level');
    }

    public function hasMoreless(): bool
    {
        return count($this->segmentEvents) >= 6;
    }

    public function hasTimingIntro(): bool
    {
        return !($this->context instanceof Clip) && $this->context->getOption('show_tracklist_timings');
    }

    public function hasMusicSegmentItems(): bool
    {
        return $this->hasMusicSegmentItems;
    }

    public function getTimingIntroTranslationString(): string
    {
        if ($this->firstBroadcast && $this->firstBroadcast->getStartAt()->isFuture()) {
            return 'timings_start_of_day';
        }

        return 'timings_start_of_programme';
    }

    /** @return AbstractSegmentItemPresenter[] */
    public function getSegmentItemsPresenters(): array
    {
        if (!is_null($this->segmentItemsPresenters)) {
            return $this->segmentItemsPresenters;
        }

        $this->segmentEvents = $this->filterSegmentEvents();
        if (!$this->segmentEvents) {
            return [];
        }

        $presenters = [];

        $segmentItems = [];
        $previousTitle = reset($this->segmentEvents)->getTitle();
        $start = 0;

        // On groups:
        //
        // This is a VERY naughty abuse of the data model.
        //
        // Take a look https://www.bbc.co.uk/programmes/p005xm30
        // See how the segments are neatly split into different sections, with a 'MUSIC PLAYED'
        // title before each section? That's done by using Segment Event titles.
        //
        // Usually, Segment Events do not have titles, because we don't care about them.
        // We care about the Segments titles, which contain the segment information rather than the timing
        // information within the programme, which is what the Segment Event object does.
        //
        // So, as Segment Event titles are not something we use for anything else
        // it's used as markers for the start and end of a group, as well as their titles
        //
        // Therefore, a group is defined as a series of consecutive Segment Events that share
        // the same non-empty title. Note that this series can contain a single element. As
        // long the Segment Event has a non-empty title, it belongs to a group

        // We use the index and call it 'relative offset' here because we could
        // have reversed the array in case of music segments for live programme debuts.
        foreach ($this->segmentEvents as $relativeOffset => $segmentEvent) {
            // null, empty or different titles mean new group
            if (empty($previousTitle) || $segmentEvent->getTitle() != $previousTitle) {
                if ($segmentItems) {
                    $presenters[] = $this->createSegmentItem($segmentItems, $start);
                }

                $start = $relativeOffset;
                $segmentItems = [];
            }

            $segmentItems[] = $segmentEvent;
            $previousTitle = $segmentEvent->getTitle();
        }

        // add the last group that didn't get added in the loop (if it's not empty)
        if ($segmentItems) {
            $presenters[] = $this->createSegmentItem($segmentItems, $start);
        }

        return $presenters;
    }

    protected function validateOptions(array $options): void
    {
        if (!is_int($options['h_tag_level']) || $options['h_tag_level'] < 1) {
            throw new InvalidOptionException('h_tag_level has to be a positive integer');
        }
    }

    private function filterSegmentEvents(): array
    {
        if ($this->context->getOption('show_tracklist_inadvance') ||
            !$this->firstBroadcast ||
            $this->context instanceof Clip ||
            $this->firstBroadcast->getEndAt()->isPast() // Episode last broadcast in the past
        ) {
            // In all these cases we want to show the full track list in chronological order
            return $this->segmentEvents;
        }
        if (!$this->liveBroadcastHelper->isOnNowIsh($this->firstBroadcast, true)) {
            // If none of the above conditions are fulfilled, and the broadcast is not on now. We do not
            // render a list of segment events. (basically the broadcast hasn't happened yet)
            return [];
        }

        // If we are here, ** this episode is currently being broadcast for the first time. **
        // Filter to only the segment events that have already started
        $filteredSegmentEvents = [];
        $currentOffset = $this->firstBroadcast->getStartAt()->diffInSeconds(ApplicationTime::getTime(), false);

        foreach ($this->segmentEvents as $segmentEvent) {
            // music segments that have offsets get reversed
            // check if we're already reversing to avoid checking all conditions again
            if (!$this->isReversed && $segmentEvent->getSegment() instanceof MusicSegment && !is_null($segmentEvent->getOffset())) {
                $this->isReversed = true;
            }

            // things within the offset range, or that don't have offsets, get displayed
            if (!$segmentEvent->getOffset() || $segmentEvent->getOffset() <= $currentOffset) {
                $filteredSegmentEvents[] = $segmentEvent;
            }
        }

        if ($this->isReversed) {
            $filteredSegmentEvents = array_reverse($filteredSegmentEvents);
        }

        return $filteredSegmentEvents;
    }

    private function createSegmentItem(array $segmentEvents, int $start): AbstractSegmentItemPresenter
    {
        $current = $start;
        $presenters = [];

        // If the segment event title is non-empty, we're dealing with a group
        $isGroup = !empty(reset($segmentEvents)->getTitle());

        // If a segment item is part of a group, the h tag has to be one level deeper
        $hTag = 'h' . ($this->getOption('h_tag_level') + ($isGroup ? 2 : 1));

        /** @var SegmentEvent $segmentEvent */
        foreach ($segmentEvents as $segmentEvent) {
            $options = ['h_tag' => $hTag];
            // If there are more than 6 segment events and we're past the fourth one, we start hiding things
            if ($this->hasMoreless() && $current > 3) {
                $options['moreless_class'] = 'ml__hidden';
            }

            if ($segmentEvent->getSegment() instanceof MusicSegment) {
                $this->hasMusicSegmentItems = true;
                $options['context_pid'] = (string) $this->context->getPid();

                if ($this->isClassicalMusicSegment($segmentEvent->getSegment())) {
                    $presenters[] = new ClassicalMusicPresenter($this->context, $segmentEvent, $this->getTimingType(), $this->firstBroadcast, $options);
                } else {
                    $presenters[] = new PopularMusicPresenter($this->context, $segmentEvent, $this->getTimingType(), $this->firstBroadcast, $options);
                }
            } else {
                if ($segmentEvent->getSegment()->getType() === 'chapter') {
                    $this->hasChapterSegments = true;
                }

                $presenters[] = new SpeechPresenter($this->playTranslationsHelper, $segmentEvent, $options);
            }

            $current += 1;
        }

        if ($isGroup) {
            $options = ['h_tag' => 'h' . ($this->getOption('h_tag_level') + 1)];
            if ($this->hasMoreless() && $start > 3) {
                $options['moreless_class'] = 'ml__hidden';
            }

            return new GroupPresenter(reset($segmentEvents), $presenters, $options);
        }

        return reset($presenters);
    }

    private function isClassicalMusicSegment(Segment $segment): bool
    {
        if (strtolower($segment->getType()) == 'classical') {
            return true;
        }

        /** @var Contribution $contribution */
        foreach ($segment->getContributions() as $contribution) {
            if (strtolower($contribution->getCreditRole()) === 'composer') {
                return true;
            }
        }

        return false;
    }

    private function getTimingType(): string
    {
        // A reversed list of segment items presenters mean a live music broadcast
        if ($this->isReversed) {
            return AbstractMusicSegmentItemPresenter::TIMING_DURING;
        }

        if ($this->context instanceof Clip || !$this->context->getOption('show_tracklist_timings')) {
            return AbstractMusicSegmentItemPresenter::TIMING_OFF;
        }

        if ($this->firstBroadcast && $this->firstBroadcast->getStartAt()->isFuture()) {
            return AbstractMusicSegmentItemPresenter::TIMING_PRE;
        }

        return AbstractMusicSegmentItemPresenter::TIMING_POST;
    }
}
