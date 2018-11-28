<?php
declare(strict_types = 1);

namespace App\DsShared;

use App\DsShared\Utilities\EntityContext\EntityContextPresenter;
use App\DsShared\Utilities\Image\ImagePresenter;
use App\DsShared\Utilities\ImageEntity\ImageEntityPresenter;
use App\DsShared\Utilities\Synopsis\SynopsisPresenter;
use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;
use BBC\ProgrammesPagesService\Domain\Entity\Image;
use BBC\ProgrammesPagesService\Domain\ValueObject\Synopses;

/**
 * DsShared Factory Class for creating presenters.
 */
class PresenterFactory
{
    public function entityContextPresenter(
        CoreEntity $context,
        array $options = []
    ): EntityContextPresenter {
        return new EntityContextPresenter($context, $options);
    }


    public function imageEntityPresenter(
        Image $image,
        int $defaultWidth,
        $sizes,
        array $options = []
    ): ImageEntityPresenter {
        return new ImageEntityPresenter(
            $image,
            $defaultWidth,
            $sizes,
            $options
        );
    }

    public function imagePresenter(
        string $imagePid,
        int $defaultWidth,
        $sizes,
        array $options = []
    ): ImagePresenter {
        return new ImagePresenter(
            $imagePid,
            $defaultWidth,
            $sizes,
            $options
        );
    }

    public function synopsisPresenter(Synopses $synopses, int $maxLength): SynopsisPresenter
    {
        return new SynopsisPresenter($synopses, $maxLength);
    }
}
