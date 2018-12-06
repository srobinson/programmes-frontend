<?php

namespace App\Controller;

use App\Controller\Helpers\IsiteKeyHelper;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;
use App\ExternalApi\Isite\Domain\BaseIsiteObject;
use App\ExternalApi\Isite\IsiteResult;
use App\ExternalApi\Isite\Service\ArticleService;
use App\ExternalApi\Isite\Service\ProfileService;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class IsiteBaseController extends BaseController
{
    protected const MAX_LIST_DISPLAYED_ITEMS = 48;

    /** @var IsiteKeyHelper */
    protected $isiteKeyHelper;

    /** @var CoreEntitiesService */
    protected $coreEntitiesService;

    /** @var string */
    protected $projectSpace;

    /** @var ArticleService|ProfileService */
    protected $isiteService;

    protected function initBranding(BaseIsiteObject $isiteObject): void
    {
        if ('' !== $isiteObject->getBrandingId()) {
            $this->setBrandingId($isiteObject->getBrandingId());
        }
    }

    protected function getIsiteObject(string $guid, bool $preview): BaseIsiteObject
    {
        /** @var IsiteResult $isiteResult */
        $isiteResult = $this->isiteService->getByContentId($guid, $preview)->wait(true);
        $isiteObjects = $isiteResult->getDomainModels();
        if (!$isiteObjects) {
            throw $this->createNotFoundException('No resource found for guid');
        }

        return reset($isiteObjects);
    }

    protected function getPreview(Request $request): bool
    {
        if ($request->query->has('preview') && $request->query->get('preview')) {
            return true;
        }

        return false;
    }

    protected function redirectWith(
        string $key,
        string $slug,
        bool $preview,
        string $routeName
    ): RedirectResponse {
        $params = ['key' => $key, 'slug' => $slug];

        if ($preview) {
            $params['preview'] = 'true';
        }

        return $this->cachedRedirectToRoute($routeName, $params, 301);
    }

    protected function getPaginator(BaseIsiteObject $isiteObject): ?PaginatorPresenter
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

    protected function initContext(BaseIsiteObject $isiteObject, CoreEntitiesService $coreEntitiesService): void
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
