<?php
declare(strict_types = 1);
namespace App\Ds2013\Presenters\Domain\CoreEntity\Programme;

use App\Ds2013\Presenters\Domain\CoreEntity\Programme\BroadcastSubPresenters\BroadcastProgrammeBodyPresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\Programme\BroadcastSubPresenters\BroadcastProgrammeTitlePresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters\ProgrammeBodyPresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\SharedSubPresenters\CoreEntityTitlePresenter;
use App\DsShared\Helpers\HelperFactory;
use App\DsShared\Helpers\StreamableHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Broadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This is a small override of the programme presenter that handles
 * whether or not something is a repeat. Yeah. That's it. All this boilerplate for that.
 */
class BroadcastProgrammePresenter extends ProgrammePresenter
{
    /** @var Broadcast */
    private $broadcast;

    private $additionalOptions = [];

    const TEMPLATE_PATH_CLASS_OVERRIDE = ProgrammePresenter::class;

    /**
     * Define options needed by all broadcast sub-presenters too
     *
     * @var array
     */
    private $additionalSharedOptions = [];

    public function __construct(
        UrlGeneratorInterface $router,
        HelperFactory $helperFactory,
        Broadcast $broadcast,
        Programme $programme,
        array $options = []
    ) {
        $options = array_merge($this->additionalOptions, $options);
        $this->sharedOptions = array_merge($this->sharedOptions, $this->additionalSharedOptions);
        parent::__construct($router, $helperFactory, $programme, $options);
        $this->broadcast = $broadcast;
    }

    public function getProgrammeTitlePresenter(): CoreEntityTitlePresenter
    {
        return new BroadcastProgrammeTitlePresenter(
            $this->router,
            $this->helperFactory->getTitleLogicHelper(),
            $this->broadcast,
            $this->programme,
            $this->helperFactory->getStreamUrlHelper(),
            $this->subPresenterOptions('title_options')
        );
    }

    public function getProgrammeBodyPresenter(): ProgrammeBodyPresenter
    {
        return new BroadcastProgrammeBodyPresenter(
            $this->router,
            $this->helperFactory->getPlayTranslationsHelper(),
            $this->helperFactory->getLocalisedDaysAndMonthsHelper(),
            $this->broadcast,
            $this->programme,
            $this->subPresenterOptions('body_options')
        );
    }
}
