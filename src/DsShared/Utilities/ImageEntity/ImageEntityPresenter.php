<?php
declare(strict_types = 1);

namespace App\DsShared\Utilities\ImageEntity;

use App\DsShared\Utilities\Image\ImagePresenter;
use BBC\ProgrammesPagesService\Domain\Entity\Image;

class ImageEntityPresenter extends ImagePresenter
{
    /** @var Image */
    private $image;

    const TEMPLATE_PATH_CLASS_OVERRIDE = ImagePresenter::class;

    /**
     * ImageEntityPresenter constructor.
     * @param Image $image
     * @param int $defaultWidth
     *        Used to build the src attribute for browsers that don't support srcset/sizes
     * @param array|string $sizes
     *        Used to build the sizes attribute
     * @param array $options
     */
    public function __construct(
        Image $image,
        int $defaultWidth,
        $sizes,
        array $options = []
    ) {
        $this->image = $image;

        parent::__construct((string) $image->getPid(), $defaultWidth, $sizes, $options);
    }

    protected function buildSrcUrl(?int $width): string
    {
        // Bounded images force a specific ratio image by adding borders to
        // the shorter dimension, rather than cutting off part of the image.
        // Bounded imagechef recipes are only defined for recipes with an
        // explicit ratio. For instance 320x320_b and 320x180_b are valid, but
        // 320xn is not. Which makes sense as images without an explicit
        // height do not get forced to a particular ratio at all.

        $ratio = $this->getOption('ratio');
        if ($ratio && $ratio !== 'auto') {
            $height = (string) round($width / $ratio, 0);
            if ($this->getOption('is_bounded')) {
                $height .= '_b';
            }

            return $this->image->getUrl((string) $width, $height);
        }

        return $this->image->getUrl((string) $width);
    }
}
