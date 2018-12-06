<?php
declare(strict_types = 1);

namespace App\Controller\Articles;

use App\Controller\BaseController;
use App\Controller\Helpers\IsiteKeyHelper;
use App\Controller\IsiteBaseController;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;
use App\ExternalApi\Isite\Domain\Article;
use App\ExternalApi\Isite\IsiteResult;
use App\ExternalApi\Isite\Service\ArticleService;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use App\Exception\HasContactFormException;
use Symfony\Component\HttpFoundation\Request;

class ShowController extends IsiteBaseController
{
    private const REDIRECT_ROUTE_NAME = 'programme_article';

    public function __invoke(
        string $key,
        string $slug,
        Request $request,
        ArticleService $isiteService,
        IsiteKeyHelper $isiteKeyHelper,
        CoreEntitiesService $coreEntitiesService
    ) {
        $this->isiteKeyHelper = $isiteKeyHelper;
        $this->coreEntitiesService = $coreEntitiesService;
        $this->isiteService = $isiteService;

        $preview = $this->getPreview($request);
        if ($this->isiteKeyHelper->isKeyAGuid($key)) {
            return $this->redirectWith(
                $this->isiteKeyHelper->convertGuidToKey($key),
                $slug,
                $preview,
                self::REDIRECT_ROUTE_NAME
            );
        }

        $guid = $isiteKeyHelper->convertKeyToGuid($key);
        try {
            $article = $this->getIsiteObject($guid, $preview);
        } catch (HasContactFormException $e) {
            return $this->cachedRedirectToRoute(
                'article_with_contact_form',
                [
                    'key' => $key,
                    'slug' => $slug,
                ],
                302,
                3600
            );
        }

        if ($slug !== $article->getSlug()) {
            return $this->redirectWith(
                $article->getKey(),
                $article->getSlug(),
                $preview,
                self::REDIRECT_ROUTE_NAME
            );
        }

        $this->initContext($article, $coreEntitiesService);
        $this->initBranding($article);

        $parents = $article->getParents();
        $siblingPromise = $isiteService->setChildrenOn($parents, $article->getProjectSpace()); //if more than 48, extras are removed
        $childPromise = $isiteService->setChildrenOn([$article], $article->getProjectSpace(), $this->getPage());
        $response = $this->resolvePromises(['children' => $childPromise, 'siblings' => $siblingPromise]);
        $paginator = $this->getPaginator($article);
//        $paginator = $this->getPaginator(reset($response['children']));

        return $this->renderWithChrome(
            'articles/show.html.twig',
            [
                'guid' => $guid,
                'projectSpace' => $this->projectSpace,
                'article' => $article,
                'paginatorPresenter' => $paginator,
                'programme' => $this->context,
            ]
        );
    }
}
