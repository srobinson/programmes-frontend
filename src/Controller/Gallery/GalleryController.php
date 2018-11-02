<?php
declare(strict_types=1);

namespace App\Controller\Gallery;

use App\Controller\BaseController;
use App\Ds2013\PresenterFactory;
use BBC\ProgrammesPagesService\Domain\Entity\Collection;
use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\PodcastsService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use BBC\ProgrammesPagesService\Service\PromotionsService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Podcast full page. Future implementation.
 *
 * When a user click in podcast panel, it takes you to this full page.
 */
class GalleryController extends BaseController
{
    public function __invoke(
        CoreEntity $coreEntity
    ) {


        $this->setContextAndPreloadBranding($coreEntity);

        return $this->renderWithChrome('gallery/gallery.html.twig', [

        ]);
    }
}
