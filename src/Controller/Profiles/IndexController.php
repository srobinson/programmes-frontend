<?php
declare(strict_types = 1);

namespace App\Controller\Profiles;

use App\Controller\BaseController;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;
use App\ExternalApi\Isite\Domain\Profile;
use App\ExternalApi\Isite\Service\ProfileService;
use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;

class IndexController extends BaseController
{
    public function __invoke(CoreEntity $coreEntity, ProfileService $isiteService)
    {
        $this->setIstatsProgsPageType('profiles_list');
        $this->setContextAndPreloadBranding($coreEntity);

        /** @var Profile[] $profiles */
        $profiles = [];
        $parameters = ['coreEntity' => $coreEntity, 'profiles' => $profiles, 'paginatorPresenter' => null];
        if ($coreEntity instanceof Programme) {
            $parameters['programme'] = $coreEntity; //so the the base 2013 template sets the footer

            $profilesResult = $isiteService->getByProgramme($coreEntity, $this->getPage())->wait();
            $profiles = $profilesResult->getDomainModels();

            if ($profilesResult->getTotal() > 48) {
                $parameters['paginatorPresenter'] = new PaginatorPresenter($this->getPage(), 48, $profilesResult->getTotal());
            }
        }

        if (empty($profiles)) {
            $this->response()->setStatusCode(404);
        } else {
            $groupProfiles = [];
            /** @var string $project */
            $project = $coreEntity->getOption('project_space');
            foreach ($profiles as $profile) {
                if ($profile->getType() === 'group') {
                    $groupProfiles[] = $profile;
                }
            }
            $childPromise = $isiteService->setChildrenOn($groupProfiles, $project);
            $this->resolvePromises([$childPromise]);
            $parameters['profiles'] = $profiles;
        }

        $this->overridenDescription = 'Profiles for ' . $coreEntity->getTitle();

        return $this->renderWithChrome('profiles/index.html.twig', $parameters);
    }
}
