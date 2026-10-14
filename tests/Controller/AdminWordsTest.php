<?php

namespace Base\Classroom\Tests\Controller;

use Base\Classroom\Entity\Card;
use Base\Classroom\Entity\Level;
use Base\Classroom\Entity\Product\ResourceOffer;
use Base\Classroom\Entity\Purpose;
use Base\Classroom\Entity\Resource;
use Base\Classroom\Entity\Sequence;
use Base\Classroom\Entity\Session;
use Base\Classroom\Entity\Subject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;

/**
 * The words of the classroom's back office, in each of its languages: the
 * screens' titles (the "entities" domain omnibase/admin names a screen with:
 * "Card" and "Sequence" were the classes' names), and every label, help and
 * title a CRUD controller gives as a key of the classroom's domain - "State"
 * was the state field's name, untranslated.
 */
class AdminWordsTest extends TestCase
{
    private const LOCALES = ['fr', 'en', 'de'];

    /** Each screen's entity, by its key in the "entities" domain. */
    private const ENTITIES = [
        'classroom.card' => Card::class,
        'classroom.sequence' => Sequence::class,
        'classroom.session' => Session::class,
        'classroom.resource' => Resource::class,
        'classroom.level' => Level::class,
        'classroom.subject' => Subject::class,
        'classroom.purpose' => Purpose::class,
        'classroom.product.resource_offer' => ResourceOffer::class,
    ];

    public function testEveryScreenIsNamedInEveryLanguage(): void
    {
        $missing = [];
        foreach (self::LOCALES as $locale) {
            $words = self::catalogue('entities', $locale);
            foreach (array_keys(self::ENTITIES) as $entity) {
                foreach (['_singular', '_plural'] as $noun) {
                    if ('' === trim($words[$entity.'.'.$noun] ?? '')) {
                        $missing[] = $locale.': '.$entity.'.'.$noun;
                    }
                }
            }
        }
        self::assertSame([], $missing);
    }

    public function testEveryEntityOfAScreenIsListed(): void
    {
        $entities = [];
        foreach (glob(\dirname(__DIR__, 2).'/src/Controller/Admin/Crud/*CrudController.php') as $file) {
            $controller = 'Base\\Classroom\\Controller\\Admin\\Crud\\'.basename($file, '.php');
            $entities[] = $controller::getEntityFqcn();
        }
        sort($entities);
        $listed = array_values(self::ENTITIES);
        sort($listed);
        self::assertSame($listed, $entities, 'a new screen is named in entities+intl-icu.*.yaml and listed here');
    }

    public function testEveryKeyTheScreensGiveIsTranslated(): void
    {
        $keys = [];
        $directory = \dirname(__DIR__, 2).'/src/Controller/Admin';
        foreach ([...glob($directory.'/*.php'), ...glob($directory.'/*/*.php')] as $file) {
            preg_match_all("/'@classroom\\.([a-z0-9_.]+)'/", (string) file_get_contents($file), $found);
            array_push($keys, ...$found[1]);
        }
        $keys = array_unique($keys);
        self::assertContains('admin.state', $keys, 'the state field is labelled');

        $missing = [];
        foreach (self::LOCALES as $locale) {
            $words = self::catalogue('classroom', $locale);
            foreach ($keys as $key) {
                // A key completed in the code ('@classroom.resource.visibility.'.$case->value): its words exist.
                if (str_ends_with($key, '.')) {
                    if ([] === array_filter(array_keys($words), fn (string $word) => str_starts_with($word, $key))) {
                        $missing[] = $locale.': '.$key.'*';
                    }
                    continue;
                }
                if ('' === trim($words[$key] ?? '')) {
                    $missing[] = $locale.': '.$key;
                }
            }
        }
        self::assertSame([], $missing);
    }

    /** @return array<string, string> the domain's words in that language, by key */
    private static function catalogue(string $domain, string $locale): array
    {
        $file = \dirname(__DIR__, 2).'/translations/'.$domain.'+intl-icu.'.$locale.'.yaml';
        self::assertFileExists($file);

        return (new YamlFileLoader())->load($file, $locale, $domain)->all($domain);
    }
}
