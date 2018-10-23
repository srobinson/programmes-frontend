<?php
declare(strict_types = 1);
namespace App\Ds2013\Presenters\Domain\CoreEntity\SharedSubPresenters;

use App\Ds2013\Presenter;
use App\DsShared\Helpers\StreamableHelper;
use App\DsShared\Helpers\TitleLogicHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Sub-presenter for ProgrammePresenter
 */
class CoreEntityTitlePresenter extends Presenter
{
    /** @var CoreEntity */
    protected $coreEntity;

    /** @var UrlGeneratorInterface */
    protected $router;

    /** @var CoreEntity */
    protected $mainTitleProgramme;

    /** @var CoreEntity[] */
    protected $subTitlesProgrammes;

    /** @var StreamableHelper */
    protected $streamUrlHelper;

    /** @var array */
    protected $options = [
        'context_programme' => null,
        'title_format' => 'tleo::ancestry:item',
        'title_tag' => 'h4',
        'title_classes' => '',
        'show_subtitle' => true,
        'truncation_length' => null,
        'link_location_track' => 'programmeobjectlink=title',
        'link_to' => null,
        'override_title' => null,
    ];

    /** @var TitleLogicHelper */
    private $titleHelper;

    public function __construct(
        UrlGeneratorInterface $router,
        TitleLogicHelper $titleHelper,
        CoreEntity $coreEntity,
        StreamableHelper $streamUrlHelper,
        array $options = []
    ) {
        parent::__construct($options);
        $this->router = $router;
        $this->titleHelper = $titleHelper;
        $this->coreEntity = $coreEntity;
        $this->streamUrlHelper = $streamUrlHelper;

        if ($this->coreEntity instanceof Clip && $this->coreEntity->isAudio()) {
            // don't show the subtitle for Audio clips
            $this->options['show_subtitle'] = false;
        }
    }

    public function getTitleLinkUrl(): string
    {
        if ($this->options['link_to'] === 'podcast') {
            return $this->router->generate('programme_podcast_episodes_download', ['pid' => $this->coreEntity->getPid()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        if ($this->coreEntity instanceof Clip) {
            $route = $this->streamUrlHelper->getRouteForProgrammeItem($this->coreEntity);
            return $this->router->generate($route, ['pid' => $this->coreEntity->getPid()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $this->router->generate('find_by_pid', ['pid' => $this->coreEntity->getPid()], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function getMainTitle(): string
    {
        if (!is_null($this->getOption('override_title'))) {
            return $this->getOption('override_title');
        }

        if (!isset($this->mainTitleProgramme)) {
            $this->setTitleProgrammes();
        }

        return $this->mainTitleProgramme->getTitle();
    }

    /**
     * @return CoreEntity[]
     */
    public function getSubTitlesProgrammes(): array
    {
        if (!isset($this->subTitlesProgrammes)) {
            $this->setTitleProgrammes();
        }
        return $this->subTitlesProgrammes;
    }

    private function setTitleProgrammes(): void
    {
        list($this->mainTitleProgramme, $this->subTitlesProgrammes) = $this->titleHelper->getOrderedProgrammesForTitle(
            $this->coreEntity,
            $this->options['context_programme'],
            $this->options['title_format']
        );
    }
}
