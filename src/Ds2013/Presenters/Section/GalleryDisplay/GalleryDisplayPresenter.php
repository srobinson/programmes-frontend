<?php


namespace App\Ds2013\Presenters\Section\GalleryDisplay;

use App\Ds2013\Presenter;
use App\DsShared\PresenterFactory;
use BBC\ProgrammesPagesService\Domain\Entity\Gallery;
use BBC\ProgrammesPagesService\Domain\Entity\Image;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\DsShared\Utilities\ImageEntity\ImageEntityPresenter;

class GalleryDisplayPresenter extends Presenter
{
    /** @var Gallery  */
    private $gallery;

    /** @var array */
    private $images;

    /** @var Image|null */
    private $primaryImage;

    /** @var bool */
    private $fullImagePageView;

    /** @var array */
    private $imagePresenters = [];

    /** @var int */
    private $activeImagePosition;

    /** @var UrlGeneratorInterface */
    private $router;

    /** @var array */
    protected $options = [
        'image_sizes' => [1 => 1/1, 1008 => '976px'],
        'image_srcsets' => [320, 560, 976],
        'thumbnail_width' => 224,
        'default_width' => 320,
    ];

    public function __construct(
        PresenterFactory $presenterFactory,
        Gallery $gallery,
        ?Image $primaryImage,
        array $images,
        bool $fullImagePageView,
        UrlGeneratorInterface $router,
        array $options
    ) {
        $this->gallery = $gallery;
        $this->images = $images;
        $this->primaryImage = $primaryImage;
        $this->fullImagePageView = $fullImagePageView;
        $this->router = $router;
        parent::__construct($options);
        foreach ($images as $position => $image) {
            $this->imagePresenters[$position] = $presenterFactory->imageEntityPresenter(
                $image,
                $this->options['default_width'],
                $this->options['image_sizes'],
                [
                    'srcsets' => $this->options['image_srcsets'],
                    'is_bounded' => true,
                    'is_lazy_loaded' => false,
                ]
            );
            if ($image->getPid() == $primaryImage->getPid()) {
                $this->activeImagePosition = $position;
            }
        }
    }

    public function isFullImagePageView(): bool
    {
        return $this->fullImagePageView;
    }

    public function renderSrc(int $position): string
    {
        return $this->imagePresenters[$position]->getSrc();
    }

    public function renderSrcSets(int $position): string
    {
        return $this->imagePresenters[$position]->getSrcSets();
    }

    public function renderSizes(int $position): string
    {
        return $this->imagePresenters[$position]->getSizes();
    }

    public function getPrimaryImage(): Image
    {
        return $this->primaryImage;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function getGallery(): gallery
    {
        return $this->gallery;
    }

    public function getActiveImagePosition(): int
    {
        return $this->activeImagePosition;
    }

    public function getPreviousUrl(): string
    {
        $imagesCount = count($this->images);
        $previousImagePos = $this->getActiveImagePosition() > 0 ? ($this->getActiveImagePosition() - 1) : $imagesCount - 1;
        if (!isset($this->images[$previousImagePos])) {
            return '#';
        }
        return $this->pageUrl($this->images[$previousImagePos]);
    }

    public function getNextUrl(): string
    {
        $imagesCount = count($this->images);
        $previousImagePos  = $this->getActiveImagePosition() < ($imagesCount - 1) ? ($this->getActiveImagePosition() + 1) : 0;
        if (!isset($this->images[$previousImagePos])) {
            return '#';
        }
        return $this->pageUrl($this->images[$previousImagePos]);
    }

    public function pageUrl($image): string
    {
        return $this->router->generate('programme_gallery', [
            'pid' =>  $this->getGallery()->getPid(),
            'imagePid' => $image->getPid(),
        ]);
    }

    public function getImagePresenter(int $position): ImageEntityPresenter
    {
        return $this->imagePresenters[$position];
    }
}
