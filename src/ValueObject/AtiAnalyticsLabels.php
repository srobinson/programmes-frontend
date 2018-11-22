<?php
declare (strict_types = 1);

namespace App\ValueObject;

class AtiAnalyticsLabels
{
    /** @var mixed */
    private $context;

    /** @var string */
    private $pageType;

    public function __construct($context, string $progsPageType)
    {
        $this->context = $context;
        $this->pageType = $progsPageType;
    }

    public function orbLabels()
    {
        // TODO: This destination logic is incorrect and needs to cover BBC_Language_English (PROGRAMMES-6747)
        // This also needs to be moved into something environment based as we shouldn't be using test on live
        $destination = 'PS Programmes Test';
        if (method_exists($this->context, 'getNetwork') && $this->context->getNetwork()
            && ((string) $this->context->getNetwork()->getNid() === 'bbc_world_service' || $this->context->getNetwork()->isWorldServiceInternational())
        ) {
            $destination = 'WS Programmes Test';
        }

        // TODO: This needs to be set based on the masterbrand - seperate ticket incoming
        $producer = 'progs_v3';

        // TODO: This contentID needs to not be based on pips if it's an article or profile - that should be iSite and use GUID
        // If it's on pips, it should have the pips authority and the pid as the identifier
        // Seperate ticket incoming for that.
        $contentId = 'urn:bbc:<authority>:<identifier>';

        $labels = [
            'destination' => $destination,
            'producer' => $producer,
            'contentId' => $contentId,
            'contentType' => $this->pageType,
        ];

        return $labels;
    }
}
