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

    /** @var array */
    private $extraLabels;

    /** @var string */
    private $contentId;

    public function __construct($context, string $progsPageType, string $environment, array $extraLabels, string $contentId)
    {
        $this->context = $context;
        $this->pageType = $progsPageType;
        $this->appEnvironment = $environment;
        $this->extraLabels = $extraLabels;
        $this->contentId = $contentId;
    }

    public function orbLabels()
    {
        // TODO: This needs to be set based on the masterbrand - seperate ticket incoming
        $producer = 'progs_v3';

        $labels = [
            'destination' => $this->getDestination(),
            'producer' => $producer,
            'contentType' => $this->pageType,
            'contentId' => $this->contentId,
        ];

        $labels = array_merge($labels, $this->extraLabels);

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
