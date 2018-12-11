<?php
declare(strict_types = 1);

namespace App\Controller\Articles;

use App\Controller\BaseController;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;
use App\ExternalApi\Isite\Service\ArticleService;
use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;

class IndexController extends BaseController
{
    const RESULTS_PER_PAGE = 48;

    public function __invoke(CoreEntity $coreEntity, ArticleService $isiteService)
    {
        $this->setIstatsProgsPageType('article_listpid');
        $this->setContextAndPreloadBranding($coreEntity);

        $articles = [];
        $parameters = ['coreEntity' => $coreEntity, 'articles' => $articles, 'paginatorPresenter' => null];
        if ($coreEntity instanceof Programme) {
            $parameters['programme'] = $coreEntity; //so the the base 2013 template sets the footer

            $articlesResult = $isiteService->getByProgramme($coreEntity, $this->getPage())->wait();
            $articles = $articlesResult->getDomainModels();

            if ($articlesResult->getTotal() > self::RESULTS_PER_PAGE) {
                $parameters['paginatorPresenter'] = new PaginatorPresenter($this->getPage(), self::RESULTS_PER_PAGE, $articlesResult->getTotal());
            }
        }

        if (empty($articles)) {
            $this->response()->setStatusCode(404);
        } else {
            $parameters['articles'] = $articles;
        }

        $this->overridenDescription = 'Articles about ' . $coreEntity->getTitle();

        return $this->renderWithChrome('articles/index.html.twig', $parameters);
    }
}
