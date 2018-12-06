<?php
declare(strict_types = 1);

namespace App\DsShared\Utilities\Image;

use App\DsShared\Presenter;
use App\Exception\InvalidOptionException;
use InvalidArgumentException;

class ImagePresenter extends Presenter
{
    /** @var int */
    protected $defaultWidth;

    /** @var string */
    protected $imagePid;

    /** @inheritdoc */
    protected $options = [
        'is_lazy_loaded' => true,
        'srcsets' => [80, 160, 320, 480, 640, 768, 896, 1008],
        'ratio' => (16 / 9), // use "auto" for auto height
        'is_bounded' => false,
        'alt' => '',
        'image_classes' => '',
    ];

    /** @var string */
    protected $sizes;

    /**
     * ImagePresenter constructor.
     * @param string $imagePid
     * @param int $defaultWidth
     *        Used to build the src attribute for browsers that don't support srcset/sizes
     * @param array|string $sizes
     *        Used to build the sizes attribute
     * @param array $options
     */
    public function __construct(
        string $imagePid,
        int $defaultWidth,
        $sizes,
        array $options = []
    ) {
        parent::__construct($options);

        if ((!is_string($sizes) && !is_array($sizes)) ||
            (is_array($sizes) && (!empty($sizes) && array_values($sizes) === $sizes))
        ) {
            throw new InvalidArgumentException("Argument 'sizes' must be either an empty or associative array, or a string");
        }

        $this->imagePid = $imagePid;
        $this->defaultWidth = $defaultWidth;
        $this->sizes = $this->buildSizes($sizes);
    }

    public function getSizes(): string
    {
        return $this->sizes;
    }

    public function getSrc(): string
    {
        return $this->buildSrcUrl($this->defaultWidth);
    }

    public function getSrcsets(): string
    {
        $srcsets = [];

        foreach ($this->getOption('srcsets') as $srcset) {
            $srcsets[] = $this->buildSrcUrl($srcset) . ' ' . $srcset . 'w';
        }

        return implode(', ', $srcsets);
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

            $recipe = $width . 'x' . $height;
            return 'https://ichef.bbci.co.uk/images/ic/' . $recipe . '/' . $this->imagePid . '.jpg';
        }

        return 'https://ichef.bbci.co.uk/images/ic/' . $width . 'xn/' . $this->imagePid . '.jpg';
    }

    protected function validateOptions(array $options): void
    {
        parent::validateOptions($options);

        if (!is_bool($options['is_lazy_loaded'])) {
            throw new InvalidOptionException("Option 'is_lazy_loaded' must be a boolean");
        }

        if (!is_array($options['srcsets'])) {
            throw new InvalidOptionException("Option 'srcsets' must be an array");
        }

        foreach ($options['srcsets'] as $srcset) {
            if (!is_numeric($srcset)) {
                throw new InvalidOptionException("Every 'srcsets' element must be numeric");
            }
        }

        if (!is_numeric($options['ratio']) && $options['ratio'] !== 'auto') {
            throw new InvalidOptionException("Option 'ratio' must be numeric or auto");
        }

        if (!is_string($options['alt'])) {
            throw new InvalidOptionException("Option 'alt' must be a string");
        }
    }

    /**
     * @param array|string $sizes
     * @return string
     */
    private function buildSizes($sizes): string
    {
        if (is_string($sizes)) {
            return $sizes;
        }

        // Sizes must be ordered by largest first as we use min-width
        krsort($sizes, SORT_NUMERIC);

        $parts = [];

        foreach ($sizes as $width => $fraction) {
            $width = ($width / 16);

            if (!is_string($fraction)) {
                // Convert to percentage and append the 'vw'
                $fraction = ($fraction * 100);
                $fraction .= 'vw';
            }

            $parts[] = '(min-width: ' . $width . 'em) ' . $fraction;
        }

        // add the final 100vw in case nothing matched
        $parts[] = '100vw';

        return implode(', ', $parts);
    }
}
