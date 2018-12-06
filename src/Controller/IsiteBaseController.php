<?php

namespace App\Controller;

use App\Controller\Helpers\IsiteKeyHelper;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;
use App\ExternalApi\Isite\Domain\BaseIsiteObject;
use App\ExternalApi\Isite\IsiteResult;
use App\ExternalApi\Isite\Service\ArticleService;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use Symfony\Component\HttpFoundation\Request;

class IsiteBaseController extends BaseController
{
    protected const MAX_LIST_DISPLAYED_ITEMS = 48;

    /** @var string */
    protected $key;

    /** @var string */
    protected $slug;

    /** @var IsiteKeyHelper */
    protected $isiteKeyHelper;

    /** @var CoreEntitiesService */
    protected $coreEntitiesService;

    /** @var string */
    protected $projectSpace;

    /** @var string */
    protected $guid;

    /** @var ArticleService */
    protected $isiteService;

    /** @var bool */
    protected $preview;

    protected function redirectToGuidUrl($routeName)
    {
        return $this->redirectWith(
            $this->isiteKeyHelper->convertGuidToKey($this->key),
            $this->slug,
            $this->preview,
            $routeName
        );
    }

    protected function getIsiteObject()
    {
        /** @var IsiteResult $isiteResult */
        $isiteResult = $this->isiteService->getByContentId($this->guid, $this->preview)->wait(true);
        $isiteObjects = $isiteResult->getDomainModels();
        if (!$isiteObjects) {
            throw $this->createNotFoundException('No resource found for guid');
        }

        return reset($isiteObjects);
    }

    protected function getPreview(Request $request)
    {
        if ($request->query->has('preview') && $request->query->get('preview')) {
            return true;
        }

        return false;
    }

    protected function redirectWith(string $key, string $slug, bool $preview, string $routeName)
    {
        $params = ['key' => $key, 'slug' => $slug];

        if ($preview) {
            $params['preview'] = 'true';
        }

        return $this->cachedRedirectToRoute($routeName, $params, 301);
    }

    protected function getPaginator($isiteObject): ?PaginatorPresenter
    {
        if ($isiteObject->getChildCount() <= self::MAX_LIST_DISPLAYED_ITEMS) {
            return null;
        }

        return new PaginatorPresenter(
            $this->getPage(),
            self::MAX_LIST_DISPLAYED_ITEMS,
            $isiteObject->getChildCount()
        );
    }

    protected function initContext(BaseIsiteObject $isiteObject, CoreEntitiesService $coreEntitiesService)
    {
        $context = null;
        $parentPid = $isiteObject->getParentPid();
        if ($parentPid instanceof Pid) {
            $context = $coreEntitiesService->findByPidFull($parentPid);
            if ($context && $isiteObject->getProjectSpace() !== $context->getOption('project_space')) {
                throw $this->createNotFoundException('Project space Profile|Article-Programme not matching');
            }
            $this->projectSpace = $context->getOption('project_space');
        } else {
            $this->projectSpace = $isiteObject->getProjectSpace();
        }
        $this->setContext($context);
    }
}
