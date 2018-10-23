<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Domain\CoreEntity\Programme\BroadcastSubPresenters;

use App\Ds2013\Presenters\Domain\CoreEntity\SharedSubPresenters\CoreEntityTitlePresenter;
use App\DsShared\Helpers\StreamableHelper;
use App\DsShared\Helpers\TitleLogicHelper;
use BBC\ProgrammesPagesService\Domain\ApplicationTime;
use BBC\ProgrammesPagesService\Domain\Entity\BroadcastInfoInterface;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Sub-presenter for ProgrammePresenter
 */
class BroadcastProgrammeTitlePresenter extends CoreEntityTitlePresenter
{
    /** @var BroadcastInfoInterface */
    private $broadcast;

    public function __construct(
        UrlGeneratorInterface $router,
        TitleLogicHelper $titleHelper,
        BroadcastInfoInterface $broadcast,
        Programme $programme,
        StreamableHelper $streamUrlHelper,
        array $options = []
    ) {
        parent::__construct($router, $titleHelper, $programme, $streamUrlHelper, $options);
        $this->broadcast = $broadcast;
    }

    public function getAriaTitle(): string
    {
        $timezone = ApplicationTime::getLocalTimeZone();
        $title = $this->broadcast->getStartAt()->setTimezone($timezone)->format('j M H:i');
        if ($timezone->getName() === 'UTC') {
            $title .= ' GMT';
        }
        $title .= ': ' . $this->getMainTitle();
        if ($this->getOption('show_subtitle') && $this->getSubTitlesProgrammes()) {
            foreach ($this->getSubTitlesProgrammes() as $subTitle) {
                $title .= ', ' . $subTitle->getTitle();
            }
        }
        return $title;
    }

    public function getTemplateVariableName(): string
    {
        return 'core_entity_title';
    }
}
