<?php
declare(strict_types=1);

namespace App\Controller\Styleguide\Ds2013\Domain;

use App\Builders\ContributionBuilder;
use App\Builders\ContributorBuilder;
use App\Builders\EpisodeBuilder;
use App\Builders\MusicSegmentBuilder;
use App\Builders\SegmentEventBuilder;
use App\Controller\BaseController;
use App\Ds2013\Presenters\Section\Segments\SegmentsListPresenter;
use App\DsShared\Helpers\LiveBroadcastHelper;
use App\DsShared\Helpers\PlayTranslationsHelper;

class MusicSegmentsController extends BaseController
{
    public function __invoke(LiveBroadcastHelper $liveBroadcastHelper, PlayTranslationsHelper $playTranslationsHelper)
    {
        return $this->renderWithChrome('styleguide/ds2013/domain/music_segments.html.twig', [
            'popular_music_segments' => $this->buildPopularMusicSegmentsListPresenter($liveBroadcastHelper, $playTranslationsHelper),
        ]);
    }

    public function buildPopularMusicSegmentsListPresenter(
        LiveBroadcastHelper $liveBroadcastHelper,
        PlayTranslationsHelper $playTranslationsHelper
    ): SegmentsListPresenter {
        $contributor = ContributorBuilder::any()
            ->with(['musicBrainzId' => 'd8354b38-e942-4c89-ba93-29323432abc3'])
            ->build();

        $djSegment = MusicSegmentBuilder::any()->with([
            'contributions' => [
                ContributionBuilder::any()->with([
                    'contributor' => $contributor,
                    'creditRole' => 'DJ',
                ])->build(),
            ],
            'title' => 'Segment with DJ',
        ])->build();

        $vsSegment = MusicSegmentBuilder::any()->with([
            'contributions' => [
                ContributionBuilder::any()->with(['creditRole' => 'Performer'])->build(),
                ContributionBuilder::any()->with([
                    'creditRole' => 'VS Artist',
                    'contributor' => $contributor,
                ])->build(),
            ],
            'title' => 'Segment with VS artist',
        ])->build();

        $otherSegment = MusicSegmentBuilder::any()->with([
            'contributions' => [
                ContributionBuilder::any()->with(['creditRole' => 'Performer'])->build(),
                ContributionBuilder::any()->with(['creditRole' => 'Boom Operator'])->build(),
                ContributionBuilder::any()->with(['creditRole' => 'Mic Holder'])->build(),
            ],
            'title' => 'Segment With Other Contributions',
        ])->build();

        $featSegment = MusicSegmentBuilder::any()->with([
            'contributions' => [
                ContributionBuilder::any()->with(['creditRole' => 'Performer'])->build(),
                ContributionBuilder::any()->with(['creditRole' => 'Featured Artist'])->build(),
            ],
            'title' => 'Segment With Featured Artist',
        ])->build();


        $segmentEvents = [
            SegmentEventBuilder::any()->with(['segment' => $djSegment])->build(),
            SegmentEventBuilder::any()->with(['segment' => $vsSegment])->build(),
            SegmentEventBuilder::any()->with(['segment' => $otherSegment])->build(),
            SegmentEventBuilder::any()->with(['segment' => $featSegment])->build(),
        ];

        return new SegmentsListPresenter(
            $liveBroadcastHelper,
            $playTranslationsHelper,
            EpisodeBuilder::any()->build(),
            $segmentEvents,
            null,
            []
        );
    }
}
