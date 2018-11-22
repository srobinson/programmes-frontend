<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\CoreEntity\Programme\BroadcastSubPresenters;

use App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters\ProgrammeBodyPresenter;
use App\DsShared\Helpers\LocalisedDaysAndMonthsHelper;
use App\DsShared\Helpers\PlayTranslationsHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Broadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BroadcastProgrammeBodyPresenter extends ProgrammeBodyPresenter
{
    /** @var Broadcast */
    private $broadcast;

    public function __construct(
        UrlGeneratorInterface $router,
        PlayTranslationsHelper $playTranslationsHelper,
        LocalisedDaysAndMonthsHelper $localisedDaysAndMonthsHelper,
        Broadcast $broadcast,
        Programme $programme,
        array $options = []
    ) {
        parent::__construct($router, $playTranslationsHelper, $localisedDaysAndMonthsHelper, $programme, $options);
        $this->broadcast = $broadcast;
    }

    public function isRepeat(): bool
    {
        return $this->broadcast->isRepeat();
    }

    public function getTemplateVariableName(): string
    {
        return 'programme_body';
    }
}
