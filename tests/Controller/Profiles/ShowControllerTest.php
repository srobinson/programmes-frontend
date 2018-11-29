<?php
namespace Tests\App\Controller\Profiles;

use Symfony\Bundle\FrameworkBundle\Client;
use Tests\App\BaseWebTestCase;

/**
 * @group profiles
 */
class ShowControllerTest extends BaseWebTestCase
{
    const KEY_NOT_FOUND = '7YDBGmJwZTYtGTk2PCCbsXw';
    const PROFILE_GROUP = '6YDBGmJwZTYtGTk2PCCbsXw';
    const PROFILE_INDIVIDUAL = '4YDBGmJwZTYtGTk2PCCbsXw';

    const SLUG = 'men-minutes-presenters';

    /** @var Client */
    private $client;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->loadFixtures(['ProgrammeEpisodes\EpisodesFixtures']);
    }

    /**
     * - Code repsponse 200
     */
    public function testTemplateGroupIsDisplayed()
    {
        $crawler = $this->client->request('GET', '/programmes/profiles/' . self::PROFILE_GROUP . '/' . self::SLUG);
        $this->assertResponseStatusCode($this->client, 200);
        $this->assertEquals(1, $crawler->filter('.profile--group')->count());
    }

    public function testTemplateIndividualIsDisplayed()
    {
        $crawler = $this->client->request('GET', '/programmes/profiles/' . self::PROFILE_INDIVIDUAL . '/' . self::SLUG);
        $this->assertResponseStatusCode($this->client, 200);

        $this->assertEquals(1, $crawler->filter('.profile--individual')->count());
    }

    /**
     * - Code response 301
     */
    public function test301RedirectWhenPassingGuid()
    {
        $this->client->request('GET', '/programmes/profiles/a8ed3fbc-db98-3f8e-b974-3a6348083c0f/' . self::SLUG);
        // redirects from guid to key
        $this->assertRedirectTo($this->client, 301, '/programmes/profiles/3YDBGmJwZTYtGTk2PCCbsXw/' . self::SLUG);
    }

    public function test301RedirectWhenSlugsNotMatching()
    {
        $this->client->request('GET', '/programmes/profiles/' . self::PROFILE_INDIVIDUAL . '/SLUG_DIFERENT_TO_PROFILE_SLUG');
        // redirect using profile key and profile slug
        $this->assertRedirectTo($this->client, 301, '/programmes/profiles/profile1key/men-minutes-presenters');
    }

    /**
     * - Code response 404
     */
    public function test404NoProfilesResults()
    {
        $this->client->request('GET', '/programmes/profiles/' . self::KEY_NOT_FOUND . '/' . self::SLUG);
        $this->assertResponseStatusCode($this->client, 404);
    }

    public function test404WhenKeyIsJustWrongAgain()
    {
        $this->client->request('GET', '/programmes/profiles/-/' . self::SLUG);
        $this->assertResponseStatusCode($this->client, 404);
    }
}
