<?php
declare(strict_types = 1);
namespace App\Controller\FindByPid;

use App\Controller\BaseController;
use App\Controller\Gallery\GalleryView;
use BBC\ProgrammesPagesService\Domain\Entity\Gallery;
use BBC\ProgrammesPagesService\Domain\Entity\Image;
use BBC\ProgrammesPagesService\Service\ImagesService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GalleryController extends BaseController
{
    public function __invoke(
        Gallery $gallery,
        ImagesService $imagesService,
        ?string $imagePid,
        ProgrammesAggregationService $programmesAggregationService
    ) {
        $this->setIstatsProgsPageType('galleries_show');
        $this->setContextAndPreloadBranding($gallery);
        $siblingLimit = 4;
        $images = $imagesService->findByGroup($gallery);
        $image = $this->getFirstImage($imagePid, $images);
        $showFirstImage = false;
        if ($imagePid) {
            $showFirstImage = true;
        }
        $programme = $gallery->getParent();
        $brand = $programme->getTleo();
        $network = $programme->getMasterBrand()->getNetwork()->getName();
        $galleries = $programmesAggregationService->findDescendantGalleries($brand, $siblingLimit);

        return $this->renderWithChrome('find_by_pid/gallery.html.twig', [
            'gallery' => $gallery,
            'programme' => $programme,
            'image' => $image,
            'images' => $images,
            'showFirstImage' => $showFirstImage,
            'network' => $network,
            'galleries' => $galleries,
            'brand' => $brand,
        ]);
    }

    public function getFirstImage(?string $imagePid, array $images): ?Image
    {
        if (empty($images)) {
            return null;
        }
        if (!$imagePid) {
            return reset($images);
        }
        $image = null;
        foreach ($images as $eachImage) {
            if (((string) $eachImage->getPid()) === $imagePid) {
                $image = $eachImage;
            }
        }
        if (!$image) {
            throw new NotFoundHttpException('Image not found.');
        }
        return $image;
    }
}
