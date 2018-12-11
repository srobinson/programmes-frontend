<?php

namespace Tests\App\ValueObject;

use App\ValueObject\AnalyticsCounterName;
use BBC\ProgrammesPagesService\Domain\ApplicationTime;
use BBC\ProgrammesPagesService\Domain\Entity\Brand;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Network;
use BBC\ProgrammesPagesService\Domain\Entity\Series;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use BBC\ProgrammesPagesService\Domain\ValueObject\Nid;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Domain\ValueObject\Sid;
use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;

class AnalyticsCounterNameTest extends TestCase
{
    /**
     * @dataProvider urlWithDifferentParamsFormattedProvider
     */
    public function testCounterNameValueCanBeGeneratedForDifferentParamsInTheUrl(string $expectedValueOutput, string $serviceUrlKey, string $relativePathProvided)
    {
        ApplicationTime::setTime((new Chronos('2017-09-12 12:00:00'))->getTimestamp()); // week 37

        preg_match('/\/schedules\/(\w+)/', $relativePathProvided, $matches);
        $servicePid = $matches[1];

        $serviceContext = $this->createConfiguredMock(Service::class, [
            'getUrlKey' => $serviceUrlKey,
            'getNetwork' => $this->createConfiguredMock(Network::class, [
                'getNid' => new Nid('bbc_world_service'),
                'getServices' => [$this->createMock(Service::class), $this->createMock(Service::class), $this->createMock(Service::class)],
                'getDefaultService' => $this->createConfiguredMock(Service::class, [
                    'getPid' => new Pid('p00fzl9p'),
                ]),
            ]),
            'getPid' => new Pid($servicePid),
        ]);

        $analyticsCounterName = new AnalyticsCounterName($serviceContext, $relativePathProvided);

        $this->assertEquals($expectedValueOutput, (string) $analyticsCounterName);
    }

    public function urlWithDifferentParamsFormattedProvider(): array
    {
        return [
            'CASE 1: WS europe schedule by day' => ['programmes.bbc_world_service.schedules.europe.page', 'europe', '/schedules/p02y9rgr'],
            'CASE 2: WS europe schedule by day with slash in the end' => ['programmes.bbc_world_service.schedules.europe.page', 'europe', '/schedules/p02y9rgr/'],
            'CASE 3: WS europe schedule by day' => ['programmes.bbc_world_service.schedules.europe.2017.02.11.page', 'europe', '/schedules/p02y9rgr/2017/02/11'],
            'CASE 4: WS europe schedule by this week using words' => ['programmes.bbc_world_service.schedules.europe.2017.this_week.page', 'europe', '/schedules/p02y9rgr/2017/this_week'],
            'CASE 5: WS europe schedule by this week using number' => ['programmes.bbc_world_service.schedules.europe.2017.w37.page', 'europe', '/schedules/p02y9rgr/2017/w37'],
            'CASE 6: WS europe schedule by week' => ['programmes.bbc_world_service.schedules.europe.2017.w38.page', 'europe', '/schedules/p02y9rgr/2017/w38'],
            'CASE 7: WS europe schedule by week work also for weeks with one digit' => ['programmes.bbc_world_service.schedules.europe.2017.w01.page', 'europe', '/schedules/p02y9rgr/2017/w01'],
            'CASE 8: WS europe schedule by month' => ['programmes.bbc_world_service.schedules.europe.2017.07.page', 'europe', '/schedules/p02y9rgr/2017/07'],
            'CASE 9: WS europe schedule by year' => ['programmes.bbc_world_service.schedules.europe.2017.page', 'europe', '/schedules/p02y9rgr/2017'],
            'CASE 10: WS online schedule by day' => ['programmes.bbc_world_service.schedules.2017.02.11.page', 'europe', '/schedules/p00fzl9p/2017/02/11'],
            'CASE 11: WS online schedule by day' => ['programmes.bbc_world_service.schedules.page', 'europe', '/schedules/p00fzl9p'],
            'CASE 11: WS africa schedule by day' => ['programmes.bbc_world_service.schedules.africa.page', 'africa', '/schedules/p00fzl9g'],
        ];
    }

    /**
     * @dataProvider servicesWithAddedOutletProvider
     * @dataProvider servicesWithTwoServicesInNetworkProvider
     */
    public function testCounterNameValueIsBuiltProperlyForSomeRealServices(string $expectedValueOutput, string $relativePathProvided)
    {
        ApplicationTime::setTime((new Chronos('2017-09-12 12:00:00'))->getTimestamp()); // week 37

        $analyticsCounterName = new AnalyticsCounterName(
            $this->buildServiceFromUrl($relativePathProvided),
            $relativePathProvided
        );

        $this->assertEquals($expectedValueOutput, (string) $analyticsCounterName);
    }

    public function servicesWithAddedOutletProvider(): array
    {
        // expected counter name value, input url
        return [
            // TV:
            // world news
            'CASE 1: WORLD NEWS / asia' => [
                // @see http://www.bbc.co.uk/worldnews/programmes/schedules/asiapacific/2017/09/16
                'programmes.bbc_world_news.schedules.asiapacific.2017.09.16.page', 'schedules/p00fzl9h/2017/09/16',
            ],
            'CASE 1: WORLD NEWS / latin america' => [
                // @see http://www.bbc.co.uk/worldnews/programmes/schedules/2017/09/16
                'programmes.bbc_world_news.schedules.latinamerica.2017.09.16.page', 'schedules/p00fzl9k/2017/09/16',
            ],
            'CASE 1: WORLD NEWS / africa' => [
                // @see http://www.bbc.co.uk/worldnews/programmes/schedules/africa/2017/09/16
                'programmes.bbc_world_news.schedules.africa.2017.09.16.page', 'schedules/p00fzl9g/2017/09/16',
            ],
            // RADIO:
            'CASE 2: RADIO 1' => [
                // @see http://www.bbc.co.uk/radio1/programmes/schedules
                'programmes.bbc_radio_one.schedules.page', 'schedules/p00fzl86',
            ],
            'CASE 3: RADIO 1 EXTRA' => [
                // @see http://www.bbc.co.uk/1xtra/programmes/schedules
                'programmes.bbc_1xtra.schedules.page', 'schedules/p00fzl64',
            ],
            // radio scotland
            'CASE 4: RADIO SCOTLAND / FM' => [
                // @see http://www.bbc.co.uk/radioscotland/programmes/schedules/fm
                'programmes.bbc_radio_scotland.schedules.fm.page', 'schedules/p00fzl8d',
            ],
            'CASE 4: RADIO SCOTLAND / MW' => [
                // @see http://www.bbc.co.uk/radioscotland/programmes/schedules/mw
                'programmes.bbc_radio_scotland.schedules.mw.page', 'schedules/p00fzl8g',
            ],
            'CASE 4: RADIO SCOTLAND / shetland' => [
                // @see http://www.bbc.co.uk/radioscotland/programmes/schedules/shetland
                'programmes.bbc_radio_scotland.schedules.shetland.page', 'schedules/p00fzl8j',
            ],
            'CASE 5: RADIO BRISTOL' => [
                // @see http://www.bbc.co.uk/radiobristol/programmes/schedules
                'programmes.bbc_radio_bristol.schedules.page', 'schedules/p00fzl7w',
            ],
        ];
    }

    public function servicesWithTwoServicesInNetworkProvider(): array
    {
        return [
            // tv 4
            'CASE 1: TV 4 -- NO OUTLET' => [
                // @see http://www.bbc.co.uk/cbeebies/programmes/schedules
                'programmes.bbc_four.schedules.page', 'schedules/p00fzl6b',
            ],
            'CASE 1: TV 4 / HD' => [
                // @see http://www.bbc.co.uk/bbcfour/programmes/schedules/hd
                'programmes.bbc_four.schedules.hd.page', 'schedules/p01kv81d',
            ],
            // cbeebies
            'CASE 2: CBEEBIES -- NO OUTLET' => [
                // @see http://www.bbc.co.uk/cbeebies/programmes/schedules
                'programmes.cbeebies.schedules.page', 'schedules/p00fzl9s',
            ],
            'CASE 2: CBEEBIES HD' => [
                // @see http://www.bbc.co.uk/cbeebies/programmes/schedules/hd
                'programmes.cbeebies.schedules.hd.page', 'schedules/p01kv8yz',
            ],
            // cbbc
            'CASE 3: CBBC -- NO OUTLET' => [
                // @see http://www.bbc.co.uk/cbbc/programmes/schedules
                'programmes.cbbc.schedules.page', 'schedules/p00fzl9r',
            ],
            'CASE 3: CBBC / HD' => [
                // @see http://www.bbc.co.uk/cbbc/programmes/schedules/hd
                'programmes.cbbc.schedules.hd.page', 'schedules/p01kv86b',
            ],
            // news
            'CASE 4: NEWS -- NO OUTLET' => [
                // @see http://www.bbc.co.uk/bbcnews/programmes/schedules
                'programmes.bbc_news24.schedules.page', 'schedules/p00fzl6g',
            ],
            'CASE 4: NEWS / HD' => [
                // @see htrtp://www.bbc.co.uk/bbcnews/programmes/schedules/bbc_news_channel_hd
                'programmes.bbc_news24.schedules.bbc_news_channel_hd.page', 'schedules/p01kv924',
            ],
            // radio 4
            'CASE 5: RADIO 4 / FM -- NO OUTLET' => [
                // @see http://www.bbc.co.uk/radio4/programmes/schedules
                'programmes.bbc_radio_four.schedules.page', 'schedules/p00fzl7j',
            ],
            'CASE 5: RADIO 4 / LW' => [
                // @see http://www.bbc.co.uk/radio4/programmes/schedules/lw
                'programmes.bbc_radio_four.schedules.lw.page', 'schedules/p00fzl7k',
            ],
            // radio wales
            'CASE 6: RADIO WALES / FM -- NO OUTLET' => [
                // @see http://www.bbc.co.uk/radiowales/programmes/schedules
                'programmes.bbc_radio_wales.schedules.page', 'schedules/p00fzl8y',
            ],
            'CASE 6: RADIO WALES / MW' => [
                // @see http://www.bbc.co.uk/radiowales/programmes/schedules/mw
                'programmes.bbc_radio_wales.schedules.mw.page', 'schedules/p00fzl8x',
            ],
        ];
    }

    /**
     * @dataProvider relativePathForProgrammeProvider
     */
    public function testCounterNameValueIsBuiltProperlyWhenContextTypeIsProgramme(string $expectedValueOutput, string $relativePathProvided)
    {
        $programmeContext = $this->createConfiguredMock(Brand::class, [
            'getType' => 'brand',
            'getPid' => new Pid('b006q2x0'), // dr who
            'getparent' => null,
            'getTitle' => 'doctor_who',
        ]);

        $analyticsCounterName = new AnalyticsCounterName($programmeContext, $relativePathProvided);

        $this->assertEquals($expectedValueOutput, (string) $analyticsCounterName);
    }

    public function relativePathForProgrammeProvider(): array
    {
        return [
            // expected counter variable value, url input to produce the counter variable
            'CASE 1: request a programme with no parents' => ['programmes.doctor_who.brand.b006q2x0.page', '/programmes/b006q2x0'],
            'CASE 2: request clips of a programme with no parents' => ['programmes.doctor_who.brand.b006q2x0.clips.page', '/programmes/b006q2x0/clips'],
        ];
    }

    public function testCounterNameValueIsBuiltProperlyWhenContextTypeIsProgrammeAndHasParents()
    {
        $programmeContext = $this->createConfiguredMock(Episode::class, [
            'getType' => 'episode',
            'getPid' => new Pid('b00744pm'), // destiny
            'getTitle' => 'destiny',
            'getParent' => $this->createConfiguredMock(Series::class, [
                'getType' => 'series',
                'getPid' => new Pid('b00xyv72'), // the planets
                'getTitle' => 'the_planets',
                'getParent' => null,
            ]),
        ]);

        $analyticsCounterName = new AnalyticsCounterName($programmeContext, '/programmes/b00744pm');

        $this->assertEquals('programmes.the_planets.destiny.episode.b00744pm.page', (string) $analyticsCounterName);
    }

    public function testCounterNameValueIsBuiltProperlyForArticle()
    {
        $programmeContext = $this->createConfiguredMock(Episode::class, [
            'getType' => 'episode',
            'getPid' => new Pid('b00744pm'), // destiny
            'getTitle' => 'destiny',
            'getParent' => $this->createConfiguredMock(Series::class, [
                'getType' => 'series',
                'getPid' => new Pid('b00xyv72'), // the planets
                'getTitle' => 'the_planets',
                'getParent' => null,
            ]),
        ]);

        $analyticsCounterName = new AnalyticsCounterName($programmeContext, '/programmes/articles/5N50KLTDDbsBPVmdfwNjcBc/a-good-man-goes-to-war-jigsaw');

        $this->assertEquals('programmes.the_planets.destiny.episode.b00744pm.articles.5n50kltddbsbpvmdfwnjcbc.a_good_man_goes_to_war_jigsaw.page', (string) $analyticsCounterName);
    }

    public function testCounterNameIsBuildUsingDefaultBuilderForOtherContextTypes()
    {
        $analyticsCounterName = new AnalyticsCounterName(null, '/programmes/genres/childrens/activities');

        $this->assertEquals('programmes.genres.childrens.activities.page', (string) $analyticsCounterName);
    }

    private function buildServiceFromUrl(string $url): Service
    {
        $services  = [
            // bbc world news
            'schedules/p00fzl9h/2017/09/16' => [
                'sid' => 'bbc_world_news_asia_pacific',
                'nid' => 'bbc_world_news',
                'service_url_key' => 'asiapacific',
                'services_in_network' => 7,
            ],
            'schedules/p00fzl9k/2017/09/16' => [
                'sid' => 'bbc_world_news_latin_america',
                'nid' => 'bbc_world_news',
                'service_url_key' => 'latinamerica',
                'services_in_network' => 7,
            ],
            'schedules/p00fzl9g/2017/09/16' => [
                'sid' => 'bbc_world_news_africa',
                'nid' => 'bbc_world_news',
                'service_url_key' => 'africa',
                'services_in_network' => 7,
            ],
            // radio scotland
            'schedules/p00fzl8j' => [
                'sid' => 'bbc_radio_shetland',
                'nid' => 'bbc_radio_scotland',
                'service_url_key' => 'shetland',
                'services_in_network' => 5,
            ],
            'schedules/p00fzl8d' => [
                'sid' => 'bbc_radio_scotland_fm',
                'nid' => 'bbc_radio_scotland',
                'service_url_key' => 'fm',
                'services_in_network' => 5,
                'isDefaultService' => true,
            ],
            'schedules/p00fzl8g' => [
                'sid' => 'bbc_radio_scotland_mw',
                'nid' => 'bbc_radio_scotland',
                'service_url_key' => 'mw',
                'services_in_network' => 5,
            ],
            // bbc 4
            'schedules/p00fzl6b' => [
                'sid' => 'bbc_four',
                'nid' => 'bbc_four',
                'service_url_key' => 'bbc_four',
                'services_in_network' => 2, // NORMAL & HD,
                'isDefaultService' => true,
            ],
            'schedules/p01kv81d' => [
                'sid' => 'bbc_four_hd',
                'nid' => 'bbc_four',
                'service_url_key' => 'hd',
                'services_in_network' => 2, // NORMAL & HD
            ],

            'schedules/p00fzl86' => [
                'sid' => 'bbc_radio_one',
                'nid' => 'bbc_radio_one',
                'service_url_key' => 'bbc_radio_one',
                'services_in_network' => 1,
            ],
            // radio 4
            'schedules/p00fzl7j' => [
                'sid' => 'bbc_radio_fourfm',
                'nid' => 'bbc_radio_four',
                'service_url_key' => 'fm',
                'services_in_network' => 2, // FM, LW
                'isDefaultService' => true,
            ],
            'schedules/p00fzl7k' =>  [
                'sid' => 'bbc_radio_fourlw',
                'nid' => 'bbc_radio_four',
                'service_url_key' => 'lw',
                'services_in_network' => 2, // FM, LW
            ],

            // radio wales
            'schedules/p00fzl8x' => [
                'sid' => 'bbc_radio_wales_am',
                'nid' => 'bbc_radio_wales',
                'service_url_key' => 'mw',
                'services_in_network' => 2,
            ],
            'schedules/p00fzl8y' => [
                'sid' => 'bbc_radio_wales_fm',
                'nid' => 'bbc_radio_wales',
                'service_url_key' => 'fm',
                'services_in_network' => 2,
                'isDefaultService' => true,
            ],
            'schedules/p00fzl7w' => [
                'sid' => 'bbc_radio_bristol',
                'nid' => 'bbc_radio_bristol',
                'service_url_key' => 'bbc_radio_bristol',
                'services_in_network' => 1,
            ],
            'schedules/p00fzl64' => [
                'sid' => 'bbc_1xtra',
                'nid' => 'bbc_1xtra',
                'service_url_key' => 'bbc_1xtra',
                'services_in_network' => 1,
            ],
            // cbeebies
            'schedules/p00fzl9s' => [
                'sid' => 'cbeebies',
                'nid' => 'cbeebies',
                'service_url_key' => 'cbeebies',
                'services_in_network' => 2,
                'isDefaultService' => true,
            ],
            'schedules/p01kv8yz' => [
                'sid' => 'cbeebies_hd',
                'nid' => 'cbeebies',
                'service_url_key' => 'hd',
                'services_in_network' => 2,
            ],
            // cbbc
            'schedules/p00fzl9r' => [
                'sid' => 'cbbc',
                'nid' => 'cbbc',
                'service_url_key' => 'cbbc',
                'services_in_network' => 2,
                'isDefaultService' => true,
            ],
            'schedules/p01kv86b' => [
                'sid' => 'cbbc_hd',
                'nid' => 'cbbc',
                'service_url_key' => 'hd',
                'services_in_network' => 2,
            ],
            // news
            'schedules/p00fzl6g' => [
                'sid' => 'bbc_news24',
                'nid' => 'bbc_news24',
                'service_url_key' => 'bbc_news24',
                'services_in_network' => 2,
                'isDefaultService' => true,
            ],
            'schedules/p01kv924' => [
                'sid' => 'bbc_news_channel_hd',
                'nid' => 'bbc_news24',
                'service_url_key' => 'bbc_news_channel_hd',
                'services_in_network' => 2,
            ],
        ];

        $configService = $services[$url];

        $isThisServiceTheDeafultServiceOnNetwork = (isset($configService['isDefaultService']) && $configService['isDefaultService'] === true) ? 'b00000p6' : 'b00000p8';

        return $this->createConfiguredMock(Service::class, [
            'getSid' => new Sid($configService['sid']),
            'getPid' => new Pid('b00000p6'),
            'getUrlKey' => $configService['service_url_key'],
            'getNetwork' => $this->createConfiguredMock(Network::class, [
                'getNid' => new Nid($configService['nid']),
                'getServices' => range(1, $configService['services_in_network']),
                'getDefaultService' => $this->createConfiguredMock(Service::class, [
                    'getPid' => new Pid($isThisServiceTheDeafultServiceOnNetwork),
                ]),
            ]),
        ]);
    }
}
