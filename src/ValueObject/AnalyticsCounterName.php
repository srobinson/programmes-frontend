<?php
declare(strict_types = 1);

namespace App\ValueObject;

use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use InvalidArgumentException;

class AnalyticsCounterName
{
    /** @var string */
    private $counterName;

    public function __construct($context = null, string $relativePath = null)
    {
        /*
         * Build counter name variable with next pattern:
         * programmes.<NID>.schedules.<[outlet]>.<[date]>.page
         */

        // prefix
        $this->counterName = 'programmes';

        if ($context instanceof Service) {
            $this->counterName .= $this->getServiceCounterNamePart($context, $relativePath);
        } elseif ($context instanceof Programme) {
            $this->counterName .= $this->getProgrammeCounterNamePart($context, $relativePath);
        } else {
            $this->counterName .= $this->getDefaultCounterNamePart($relativePath);
        }

        // Suffix
        $this->counterName .= '.page';
    }

    public function __toString(): string
    {
        return $this->counterName;
    }

    /**
     *
     * Examples:
     * @see \Tests\App\ValueObject\AnalyticsCounterNameTest::testCounterNameValueIsBuiltProperlyForSomeRealServices
     */
    private function getServiceCounterNamePart(Service $service, string $relativePath): string
    {
        if (!preg_match('#(?<ROUTE_PREFIX>[a-z]+)\/(?<PID>[a-z0-9]+)(\/(?<DATE>.*))?#', $relativePath, $relativePathPieces)
            || !$service->getNetwork()
        ) {
            throw new InvalidArgumentException('Invalid format url');
        }
        // NOTE: Currently all Service contexts are on schedules pages. IF THIS CHANGES YOUR COUTNERNAME
        // WILL BE WRONG.
        $counterNamePieces = [
            $service->getNetwork()->getNid(),
            'schedules',
        ];

        //Calculate outlet
        $allServices = $service->getNetwork()->getServices();
        $defaultService = $service->getNetwork()->getDefaultService();
        $notOnDefaultServiceOfNetworkWithTwoServices = (count($allServices) === 2 && (string) $service->getPid() !== (string) $defaultService->getPid());
        // World service online is a special case. It is the only V2 schedules page that had a disambiguation (list of services)
        // On the same page as the schedule for the default outlet.
        $isWorldServiceOnline = ('p00fzl9p' === (string) $service->getPid());
        if ((count($allServices) > 2 || $notOnDefaultServiceOfNetworkWithTwoServices) && !$isWorldServiceOnline) {
            // We need to add the V2 outlet key. Which is hairy. Sorry.
            $outlet = trim($service->getUrlKey(), '_');
            if (!empty($outlet)) {
                $counterNamePieces[] = $outlet;
            }
        }

        if (!empty($relativePathPieces['DATE'])) {
            $counterNamePieces[] = $relativePathPieces['DATE'];
        }

        $value = implode('.', $counterNamePieces);
        $value = str_replace(['/', '-'], '.', $value);

        return '.' . $this->replaceDisallowedCharacters($value);
    }

    /**
     * Examples:
     * @see \Tests\App\ValueObject\AnalyticsCounterNameTest::testCounterNameValueIsBuiltProperlyWhenContextTypeIsProgramme
     * @see \Tests\App\ValueObject\AnalyticsCounterNameTest::testCounterNameValueIsBuiltProperlyWhenContextTypeIsProgrammeAndHasParents
     */
    private function getProgrammeCounterNamePart(Programme $context, string $relativePath): string
    {
        $partialString = $this->getParentTitlesRecursively($context);
        $partialString .= '.' . $context->getType();
        $partialString .= '.' . $context->getPid();

        // add the rest of the URL to the counter name
        $restOfUrl = str_replace(['/programmes', '/' . $context->getPid()], '', $relativePath);
        $restOfUrl = str_replace('/', '.', $restOfUrl);
        $restOfUrl = $this->replaceDisallowedCharacters($restOfUrl);
        $partialString .= $restOfUrl;
        return $partialString;
    }

    /**
     * This function uses recursion to climb up the parents tree and return a concatenation of the titles
     */
    private function getParentTitlesRecursively($context): string
    {
        if (isset($context)) {
            // concatenate title with the parents titles
            $titlesConcatenation = $this->getParentTitlesRecursively($context->getParent());
            return $titlesConcatenation . '.' . $this->replaceDisallowedCharacters($context->getTitle());
        }
        return '';
    }

    /**
     * Build the counter name based on the URL or use a predefined one if there is any for this route
     *
     * Examples:
     * @see \Tests\App\ValueObject\AnalyticsCounterNameTest::testCounterNameIsBuildUsingDefaultBuilderForOtherContextTypes
     *
     * @param string $relativePath
     * @return string
     */
    private function getDefaultCounterNamePart(string $relativePath): string
    {
        switch ($relativePath) {
            case '/programmes':
                $partialString = '.home';
                break;
            case '/schedules':
                $partialString = '.home.schedules';
                break;
            default:
                $partialString = str_replace('/programmes', '', $relativePath);
                $partialString = str_replace('/', '.', $partialString);
                $partialString = $this->replaceDisallowedCharacters($partialString);
        }
        return $partialString;
    }

    private function replaceDisallowedCharacters(string $string): string
    {
        $string = preg_replace(array('/[^a-zA-Z0-9_.]/', '{_+}', '{\._}'), '_', $string);
        $string = str_replace('_.', '.', $string);
        $string = preg_replace('{_+}', '_', $string);
        $string = trim($string, '_');
        $string = strtolower($string);
        return $string;
    }
}
