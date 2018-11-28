<?php
declare(strict_types=1);
namespace App\Controller\Styleguide\Ds2013\Utilities;

use App\Controller\BaseController;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;

class PaginationController extends BaseController
{
    public function __invoke()
    {
        return $this->renderWithChrome('styleguide/ds2013/utilities/pagination.html.twig');
    }
}
