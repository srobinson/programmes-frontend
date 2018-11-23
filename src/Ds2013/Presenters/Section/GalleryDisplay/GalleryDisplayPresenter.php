<?php


namespace App\Ds2013\Presenters\Section\GalleryDisplay;

use App\Ds2013\Presenter;
use App\DsShared\PresenterFactory;
use BBC\ProgrammesPagesService\Domain\Entity\Gallery;
use BBC\ProgrammesPagesService\Domain\Entity\Image;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GalleryDisplayPresenter extends Presenter
{

    private $gallery;

    private $images;

    private $primaryImage;

    private $fullImagePageView;

    private $imagePresenters =[];

    private $activeImagePosition;

    private $router;

    protected $options = [
        'image_sizes' => [1 => 1/1, 1008 => '976px'],
        'image_srcsets' => [320, 560, 976],
        'thumbnail_width' => 224,
        'default_width' => 320,

    ];

    /**
     * GalleryDisplayPresenter constructor.
     * @param Gallery $gallery
     * @param array $images
     * @param array $options
     */
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

        foreach( $images as $position => $image){
            $this->imagePresenters[$position] = $presenterFactory->imageEntityPresenter(
                $image,
                $this->options['default_width'],
                $this->options['image_sizes'],
                ['srcsets'=> $this->options[
                    'image_srcsets'],
                    'is_bounded'=> true]
            );
            if($image->getPid() == $primaryImage->getPid()){
                $this->activeImagePosition = $position;
            }
        }


    }

    function isFullImagePageView(): bool{
        return $this->fullImagePageView;
    }

    function renderSrc(int $position ){
        return $this->imagePresenters[$position]->getSrc();
    }

    function renderSrcSets(int $position ){
        return $this->imagePresenters[$position]->getSrcSets();
    }

    function renderSizes(int $position ){
        return $this->imagePresenters[$position]->getSizes();
    }

    function getPrimaryImage(){
        return $this->primaryImage;
    }

    function getImages(){
        return $this->images;
    }

    function getGallery(){
        return $this->gallery;
    }

    public function getActiveImagePosition()
    {
        return $this->activeImagePosition;
    }


    public function getPreviousUrl()
    {
        $images_count = count($this->images);
        $previous_image_pos = $this->getActiveImagePosition() > 0 ? ($this->getActiveImagePosition() - 1) : $images_count - 1;
        if (!isset($this->images[$previous_image_pos])) {
            return '#';
        }
        return $this->pageUrl($this->images[$previous_image_pos]);
    }

    public function getNextUrl()
    {
        $images_count = count($this->images);
        $next_image_pos = $this->getActiveImagePosition() < ($images_count - 1) ? ($this->getActiveImagePosition() + 1) : 0;
        if (!isset($this->images[$next_image_pos])) {
            return '#';
        }
        return $this->pageUrl($this->images[$next_image_pos]);
    }


    public function pageUrl($image)
    {
        return $this->router->generate( 'programme_gallery', ['pid' =>  $this->getGallery()->getPid(), 'imagePid'=> $image->getPid() ] );
    }

    public function getRenderedimage(int $position){
        return $this->imagePresenters[$position];
    }
}
