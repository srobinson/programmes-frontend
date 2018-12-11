<?php
declare(strict_types = 1);
namespace App\Controller;

use BBC\ProgrammesCachingLibrary\CacheInterface;
use BBC\ProgrammesPagesService\Domain\Entity\Network;
use BBC\ProgrammesPagesService\Service\NetworksService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class HomeController extends BaseController
{
    private const BLACKLISTED_NETWORKS = [
        'bbc_afrique_radio' => true, // Afrique
        'bbc_amharic_radio' => true, // Amharic
        'bbc_arabic_radio' => true, // Arabic Radio
        'bbc_arabic_tv' => true, // Arabic TV
        'bbc_bangla_radio' => true, // Bangla
        'bbc_burmese_radio' => true, // Burmese
        'bbc_cantonese_radio' => true, // Cantonese
        'bbc_dari_radio' => true, // Dari
        'bbc_gahuza_radio' => true, // Gahuza
        'bbc_hausa_radio' => true, // Hausa
        'bbc_hindi_radio' => true, // Hindi
        'bbc_indonesian_radio' => true, // Indonesia
        'bbc_korean_radio' => true, // Korean Radio
        'bbc_kyrgyz_radio' => true, // Kyrgyz
        'bbc_nepali_radio' => true, // Nepali
        'bbc_oromo_radio' => true, // Oromo
        'bbc_pashto_radio' => true, // Pashto
        'bbc_persian_radio' => true, // Persian
        'bbc_russian_radio' => true, // Russian
        'bbc_sinhala_radio' => true, // Sinhala
        'bbc_somali_radio' => true, // Somali
        'bbc_swahili_radio' => true, // Swahili
        'bbc_tamil_radio' => true, // Tamil
        'bbc_tigrinya_radio' => true, // Tigrinya
        'bbc_urdu_radio' => true, // Urdu
        'bbc_uzbek_radio' => true, // Uzbek
    ];

    public function __invoke(
        EntityManagerInterface $em,
        CacheInterface $cache,
        NetworksService $networksService
    ) {
        $this->setIstatsProgsPageType('home_index');
        $programmeCount = $this->countProgrammesAndGroups($em, $cache);
        $this->setAtiContentId('', 'pips');

        $serviceTypes = ['TV', 'National Radio', 'Regional Radio', 'Local Radio'];
        $publishedNetworks = $networksService->findPublishedNetworksByType(
            $serviceTypes,
            NetworksService::NO_LIMIT
        );

        $networks = array_filter($publishedNetworks, function (Network $n) {
            return !isset(self::BLACKLISTED_NETWORKS[(string) $n->getNid()]);
        });

        return $this->renderWithChrome('home/show.html.twig', [
            'programme_count' => $programmeCount,
            'tv_networks' => $this->filterNetworks($networks, 'TV'),
            'national_radio_networks' => $this->filterNetworks($networks, 'National Radio'),
            'regional_radio_networks' => $this->filterNetworks($networks, 'Regional Radio'),
            'local_radio_networks' => $this->filterNetworks($networks, 'Local Radio'),
        ]);
    }

    private function filterNetworks(array $networks, string $type)
    {
        // We don't need to sort after filtering as the array of
        // networks was already in order
        return array_filter($networks, function ($network) use ($type) {
            return $network->getType() == $type;
        });
    }

    private function countProgrammesAndGroups(
        EntityManagerInterface $em,
        CacheInterface $cache
    ) {
        $ttl = CacheInterface::NORMAL;
        $key = $cache->keyHelper(__CLASS__, __FUNCTION__, $ttl);

        // This is very naughty, as all Programmes query logic should be in
        // programmes-pages-service. But we'll make an exception here as it
        // really is a one-off.
        // Use a native query here because providing an accurate value by
        // excluding embargoed items takes too long, and it's not worth adding
        // an index to the table for this one query.
        return $cache->getOrSet($key, $ttl, function () use ($em) {
            $qText = "SELECT COUNT(*) as cnt FROM core_entity c";

            $rms = new ResultSetMapping();
            $rms->addScalarResult('cnt', 'cnt');

            return $em->createNativeQuery($qText, $rms)->getSingleScalarResult();
        });
    }
}
