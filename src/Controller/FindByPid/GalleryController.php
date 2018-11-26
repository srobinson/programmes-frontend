<?php
declare(strict_types = 1);
namespace App\Controller\FindByPid;

use App\Controller\BaseController;
use BBC\ProgrammesPagesService\Domain\Entity\Gallery;
use BBC\ProgrammesPagesService\Domain\Entity\Image;
use BBC\ProgrammesPagesService\Service\ImagesService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GalleryController extends BaseController
{
    public function __invoke(
        Gallery $gallery,
        ImagesService $imagesService,
        ?string $imagePid
    ) {
        $this->setIstatsProgsPageType('galleries_show');
        $this->setContextAndPreloadBranding($gallery);

        $images = $imagesService->findByGroup($gallery);
        $image = $this->getFirstImage($imagePid, $images);
        $showFirstImage = false;
        if($imagePid){
            $showFirstImage = true;
        }
        return $this->renderWithChrome('find_by_pid/gallery.html.twig', [
            'gallery' => $gallery,
            'image' => $image,
            'images' => $images,
            'showFirstImage' => $showFirstImage,

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
