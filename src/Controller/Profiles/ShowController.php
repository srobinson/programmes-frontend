<?php
declare(strict_types = 1);

namespace App\Controller\Profiles;

use App\Controller\BaseController;
use App\Controller\Helpers\IsiteKeyHelper;
use App\Controller\IsiteBaseController;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;
use App\ExternalApi\Isite\Domain\Profile;
use App\ExternalApi\Isite\IsiteResult;
use App\ExternalApi\Isite\Service\ProfileService;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Service\CoreEntitiesService;
use GuzzleHttp\Promise\FulfilledPromise;
use Symfony\Component\HttpFoundation\Request;

class ShowController extends IsiteBaseController
{
    private const REDIRECT_ROUTE_NAME = 'programme_profile';

    public function __invoke(
        string $key,
        string $slug,
        Request $request,
        ProfileService $isiteService,
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
        $profile = $this->getIsiteObject();

        if ($this->slug != $profile->getSlug()) {
            return $this->redirectWith($profile->getKey(), $profile->getSlug(), $this->preview, self::REDIRECT_ROUTE_NAME);
        }

        $this->initContext($profile, $coreEntitiesService);

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
                'programme' => $this->context,
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
            'guid' => $this->guid,
            'projectSpace' => $this->projectSpace,
            'profile' => $profile,
            'paginatorPresenter' => $paginator,
            'programme' => $this->context,
            'maxSiblings' => $maxSiblings,
        ]);
    }
}
