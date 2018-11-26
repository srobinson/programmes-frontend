<?php

namespace Tests\App\Controller\Clips;

use Symfony\Bundle\FrameworkBundle\Client;
use Tests\App\BaseWebTestCase;

class ClipsListControllerTest extends BaseWebTestCase
{
    /** @var Client */
    protected $client;

    public function setUp()
    {
        $this->loadFixtures([
            'ProgrammeItemsFixture',
            'Clips\ClipsListFixture',
        ]);
        $this->client = static::createClient();
    }

    public function testClipsListControllerCanLoadClipsList()
    {
        $crawler = $this->client->request('GET', '/programmes/b006q2x0/clips');

        $this->assertResponseStatusCode($this->client, 200);
        $this->assertCount(
            1,
            $crawler->filter('.footer'),
            'NON Partial-templates should include a footer'
        );
        $this->assertCount(1, $crawler->filter('.clips-grid-wrapper'));
        $this->assertCount(6, $crawler->filter('.clips-grid-wrapper > li'));
        $this->assertEquals('BBC Radio 2 - B1 - Clips', $crawler->filter('title')->text());
    }

    public function testClipsListControllerCanLoadNoClipsPage()
    {
        $crawler = $this->client->request('GET', '/programmes/b006pnjk/clips');

        $this->assertResponseStatusCode($this->client, 200);
        $this->assertCount(
            1,
            $crawler->filter('p.no_clips_page')
        );
    }

    public function testClipsPageWithNonParentTleoHaveNoFiltersList()
    {
        $crawler = $this->client->request('GET', '/programmes/p1000001/clips');

        $this->assertResponseStatusCode($this->client, 200);
        $this->assertCount(
            0,
            $crawler->filter('ul.clips-series')
        );
        $this->assertCount(1, $crawler->filter('.clips-grid-wrapper'));
        $this->assertCount(1, $crawler->filter('.clips-grid-wrapper > li'));
    }
}
