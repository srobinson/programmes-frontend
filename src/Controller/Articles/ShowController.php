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
        $this->key = $key;
        $this->slug = $slug;
        $this->isiteKeyHelper = $isiteKeyHelper;
        $this->coreEntitiesService = $coreEntitiesService;
        $this->isiteService = $isiteService;
        $this->preview = $this->getPreview($request);

        if ($this->isiteKeyHelper->isKeyAGuid($this->key)) {
            return $this->redirectToGuidUrl(self::REDIRECT_ROUTE_NAME);
        }
        $this->guid = $isiteKeyHelper->convertKeyToGuid($key);

        try {
            $article = $this->getIsiteObject();
        } catch (HasContactFormException $e) {
            return $this->cachedRedirectToRoute(
                'article_with_contact_form',
                [
                    'key' => $this->key,
                    'slug' => $this->slug,
                ],
                302,
                3600
            );
        }

        if ($this->slug !== $article->getSlug()) {
            return $this->redirectWith(
                $article->getKey(),
                $article->getSlug(),
                $this->preview,
                self::REDIRECT_ROUTE_NAME
            );
        }

        $this->initContext($article, $coreEntitiesService);

        if ('' !== $article->getBrandingId()) {
            $this->setBrandingId($article->getBrandingId());
        }

        $parents = $article->getParents();
        $siblingPromise = $isiteService->setChildrenOn($parents, $article->getProjectSpace()); //if more than 48, extras are removed
        $childPromise = $isiteService->setChildrenOn([$article], $article->getProjectSpace(), $this->getPage());
        $response = $this->resolvePromises(['children' => $childPromise, 'siblings' => $siblingPromise]);
        $paginator = $this->getPaginator($article);
//        $paginator = $this->getPaginator(reset($response['children']));

        return $this->renderWithChrome(
            'articles/show.html.twig',
            [
                'guid' => $this->guid,
                'projectSpace' => $this->projectSpace,
                'article' => $article,
                'paginatorPresenter' => $paginator,
                'programme' => $this->context,
            ]
        );
    }
}
