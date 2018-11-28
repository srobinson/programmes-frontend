<?php
declare (strict_types = 1);

namespace App\ValueObject;

class AtiAnalyticsLabels
{
    /** @var mixed */
    private $context;

    /** @var string */
    private $pageType;

    /** @var string */
    private $appEnvironment;

    public function __construct($context, string $progsPageType, string $environment)
    {
        $this->context = $context;
        $this->pageType = $progsPageType;
        $this->appEnvironment = $environment;
    }

    public function orbLabels()
    {
        // TODO: This needs to be set based on the masterbrand - seperate ticket incoming
        $producer = 'progs_v3';

        // TODO: This contentID needs to not be based on pips if it's an article or profile - that should be iSite and use GUID
        // If it's on pips, it should have the pips authority and the pid as the identifier
        // Seperate ticket incoming for that.
        $authority = 'pips';
        $identifier = (string) $this->context->getPid();

        // dump($this->context); exit;

        $labels = [
            'destination' => $this->getDestination(),
            'producer' => $producer,
            'contentId' => 'urn:bbc:' . $authority . ':' . $identifier,
            'contentType' => $this->pageType,
        ];

        return $labels;
    }

    private function getDestination(): string
    {
        $destination =  'programmes_ps';

        if (method_exists($this->context, 'getNetwork') && $this->context->getNetwork()
            && (
                in_array((string) $this->context->getNetwork()->getNid(), ['bbc_world_service', 'bbc_world_service_tv', 'bbc_learning_english', 'bbc_world_news'])
                || $this->context->getNetwork()->isWorldServiceInternational()
            )
        ) {
            $destination = 'ws_programmes';
        }

        if (in_array($this->appEnvironment, ['int', 'stage', 'sandbox', 'test'])) {
            $destination .= '_test';
        }

        return $destination;
    }
}
