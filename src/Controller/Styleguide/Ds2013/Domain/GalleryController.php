<?php
declare(strict_types=1);

namespace App\Controller\Styleguide\Ds2013\Domain;

use App\Builders\GalleryBuilder;
use App\Builders\ImageBuilder;
use App\Controller\BaseController;
use App\Ds2013\PresenterFactory;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use BBC\ProgrammesPagesService\Service\ServicesService;
use Symfony\Component\HttpFoundation\Request;

class GalleryController extends BaseController
{
    public function __invoke(
        CollapsedBroadcastsService $collapsedBroadcastService,
        CoreEntitiesService $coreEntitiesService,
        Request $request,
        ServicesService $servicesService,
        PresenterFactory $presenterFactory
    ) {

        if ($request->query->has('branding_context')) {
            $coreEntity = $coreEntitiesService->findByPidFull(new Pid($request->query->get('branding_context')));
            $this->setContextAndPreloadBranding($coreEntity);
        }
        if ($request->query->has('service')) {
            $service = $servicesService->findByPidFull(new Pid($request->query->get('service')));
            $this->setContextAndPreloadBranding($service);
        }

        $gallery = $this->createGallery();
        $this->setAtiContentId((string) $gallery->getPid(), 'pips');

        return $this->renderWithChrome('styleguide/ds2013/domain/gallery.html.twig', [
            'gallery' => $gallery,
        ]);
    }

    public function createGallery()
    {
        $image = ImageBuilder::anyWithPid('p01bz8pr')->build();
        return GalleryBuilder::any()->with([
            'pid' => new Pid('b006q2x0'),
            'image' => $image,
        ])->build();
    }
}
