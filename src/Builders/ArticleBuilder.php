<?php
namespace App\Builders;

use App\ExternalApi\Isite\Domain\Article;
use App\ExternalApi\Isite\Domain\IsiteImage;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use DateTimeImmutable;
use Faker\Factory;

class ArticleBuilder extends AbstractBuilder
{
    protected function __construct()
    {
        $faker = Factory::create();
        $this->classTarget = Article::class;

        $this->blueprintConstructorTarget = [
            'title' => $faker->sentence(3),
            'key' => $faker->word,
            'fileId' => $faker->word,
            'projectSpace' => $faker->word,
            'parentPid' => new Pid($faker->regexify('[0-9b-df-hj-np-tv-z]{8,15}')),
            'shortSynopsis' => $faker->sentence(5),
            'brandingId' => $faker->word,
            'image' => new IsiteImage(new Pid($faker->regexify('[0-9b-df-hj-np-tv-z]{8,15}'))),
            'parents' => [],
            'rowGroups' => [],
            'bbc_site' => null,
            'creationDateTime' => new DateTimeImmutable(),
            'modifiedDatetime' => new DateTimeImmutable(),
        ];
    }
}
