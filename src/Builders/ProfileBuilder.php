<?php
namespace App\Builders;

use App\ExternalApi\Isite\Domain\Profile;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use Faker\Factory;

class ProfileBuilder extends AbstractBuilder
{
    protected function __construct()
    {
        $faker = Factory::create();
        $this->classTarget = Profile::class;

        $this->blueprintConstructorTarget = [
            'title' => $faker->sentence(3),
            'key' => $faker->word,
            'fileId' => $faker->word,
            'type' => $faker->randomElement(['group', 'individual']),
            'projectSpace' => $faker->word,
            'parentPid' => new Pid($faker->regexify('[0-9b-df-hj-np-tv-z]{8,15}')),
            'shortSynopsis' => $faker->sentence(5),
            'longSynopsis' => $faker->sentence(10),
            'brandingId' => $faker->word,
            'contentBlocks' => [],
            'keyFacts' => [],
            'image' => $faker->regexify('[0-9b-df-hj-np-tv-z]{8,15}'),
            'portraitImage' => '',
            'onwardJourneyBlock' => null,
            'tagline' => "This brand new sofa is only £399! (Other sofas are available)",
            'parents' => [],
        ];
    }

    public static function anyGroup()
    {
        return self::any()->with([
            'type' => 'group',
        ]);
    }

    public static function anyIndividual()
    {
        return self::any()->with([
            'type' => 'individual',
        ]);
    }
}
