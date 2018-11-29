<?php
declare(strict_types = 1);

namespace App\Controller\Schedules;

use App\Controller\BaseController;
use App\Cosmos\Dials;
use App\ExternalApi\SoundsNav\Service\SoundsNavService;
use BBC\ProgrammesPagesService\Domain\Entity\Service;

abstract class SchedulesBaseController extends BaseController
{
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            Dials::class,
            SoundsNavService::class,
        ]);
    }

    protected function preRender()
    {
        if ($this->shouldShowSoundsNav()) {
            $this->overrideBrandingOption('orb_header', 'white');
        }
        parent::preRender();
    }

    protected function renderWithChrome(string $view, array $parameters = [])
    {
        if ($this->shouldShowSoundsNav()) {
            $soundsNavPromise = $this->container->get(SoundsNavService::class)->getContent();
            $resolvedPromises = $this->resolvePromises(['soundsNav' => $soundsNavPromise]);
            if ($resolvedPromises['soundsNav'] !== null) {
                $parameters = array_merge($parameters, $resolvedPromises);
            }
        }
        return parent::renderWithChrome($view, $parameters);
    }

    protected function shouldRedirectToOverriddenUrl(Service $service): bool
    {
        if ($service->getNetwork() === null) {
            return false;
        }

        return $service->getNetwork()->getOption('pid_override_url') && $service->getNetwork()->getOption('pid_override_code');
    }

    private function shouldShowSoundsNav(): bool
    {
        return $this->context->isRadio();
    }
}
