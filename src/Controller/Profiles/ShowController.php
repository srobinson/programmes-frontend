<?php
declare(strict_types = 1);

namespace App\Controller\Profiles;

use App\Controller\BaseController;
use App\Controller\Helpers\IsiteKeyHelper;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;
use App\ExternalApi\Isite\Domain\Profile;
use App\ExternalApi\Isite\IsiteResult;
use App\ExternalApi\Isite\Service\ProfileService;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use GuzzleHttp\Promise\FulfilledPromise;
use Symfony\Component\HttpFoundation\Request;

class ShowController extends BaseController
{
    private const MAX_LIST_DISPLAYED_ITEMS = 48;

    public function __invoke(
        string $key,
        string $slug,
        Request $request,
        ProfileService $isiteService,
        IsiteKeyHelper $isiteKeyHelper,
        CoreEntitiesService $coreEntitiesService
    ) {
        $preview = false;
        if ($request->query->has('preview') && $request->query->get('preview')) {
            $preview = true;
        }

        if ($isiteKeyHelper->isKeyAGuid($key)) {
            return $this->redirectWith($isiteKeyHelper->convertGuidToKey($key), $slug, $preview);
        }

        $guid = $isiteKeyHelper->convertKeyToGuid($key);

        /** @var IsiteResult $isiteResult */
        $isiteResult = $isiteService->getByContentId($guid, $preview)->wait(true);

        /** @var Profile $profile */
        $profiles = $isiteResult->getDomainModels();
        if (!$profiles) {
            throw $this->createNotFoundException('No profiles found for guid');
        }

        $profile = reset($profiles);

        if ($slug != $profile->getSlug()) {
            return $this->redirectWith($profile->getKey(), $profile->getSlug(), $preview);
        }

        $context = null;
        $parentPid = $profile->getParentPid();
        if ($parentPid instanceof Pid) {
            $context = $coreEntitiesService->findByPidFull($parentPid);

            if ($context && $profile->getProjectSpace() !== $context->getOption('project_space')) {
                throw $this->createNotFoundException('Project space Profile-Programme not matching');
            }
        }

        $this->setContext($context);

        if ('' !== $profile->getBrandingId()) {
            $this->setBrandingId($profile->getBrandingId());
        }

        // Calculate siblings display
        $maxSiblings = self::MAX_LIST_DISPLAYED_ITEMS;
        $siblingsPromise = new FulfilledPromise(null);
        if ($profile->getParent()) {
            $groupSize = $profile->getParent()->getGroupSize();
            if (!is_null($groupSize)) {
                // number of siblings displayed cannot be more than the maximum
                $maxSiblings = min(self::MAX_LIST_DISPLAYED_ITEMS, $groupSize);
            }
            // Get the siblings of the current profile
            $queryLimit = ($maxSiblings > 0 ? $maxSiblings : 1);
            $siblingsPromise = $isiteService
                ->setChildrenOn([$profile->getParent()], $profile->getProjectSpace(), 1, $queryLimit);
        }


        if ($profile->isIndividual()) {
            $this->resolvePromises(['siblings' => $siblingsPromise]);

            return $this->renderWithChrome('profiles/individual.html.twig', [
                'profile' => $profile,
                'programme' => $context,
                'maxSiblings' => $maxSiblings,
            ]);
        }

        // Get the children of the current profile synchronously, as we may need their children also
        $isiteService
            ->setChildrenOn([$profile], $profile->getProjectSpace(), $this->getPage())
            ->wait(true);

        // This will fetch the grandchildren of the current profile given the children fetched
        // in the above query
        $childProfilesThatAreGroups = [];
        foreach ($profile->getChildren() as $childProfile) {
            if ($childProfile->isGroup()) {
                $childProfilesThatAreGroups[] = $childProfile;
            }
        }
        $grandChildrenPromise = $isiteService->setChildrenOn($childProfilesThatAreGroups, $profile->getProjectSpace());
        $this->resolvePromises([$grandChildrenPromise, $siblingsPromise]);
        $paginator = $this->getPaginator($profile);

        return $this->renderWithChrome('profiles/group.html.twig', [
            'profile' => $profile,
            'paginatorPresenter' => $paginator,
            'programme' => $context,
            'maxSiblings' => $maxSiblings,
        ]);
    }

    private function redirectWith(string $key, string $slug, bool $preview)
    {
        $params = ['key' => $key, 'slug' => $slug];

        if ($preview) {
            $params['preview'] = 'true';
        }

        return $this->cachedRedirectToRoute('programme_profile', $params, 301);
    }

    private function getPaginator(Profile $profile): ?PaginatorPresenter
    {
        if ($profile->getChildCount() <= self::MAX_LIST_DISPLAYED_ITEMS) {
            return null;
        }

        return new PaginatorPresenter(
            $this->getPage(),
            self::MAX_LIST_DISPLAYED_ITEMS,
            $profile->getChildCount()
        );
    }
}
