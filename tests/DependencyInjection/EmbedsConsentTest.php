<?php

namespace Base\Classroom\Tests\DependencyInjection;

use Base\Classroom\DependencyInjection\ClassroomExtension;
use Base\Classroom\DependencyInjection\Compiler\EmbedsConsentPass;
use Base\Consent\DependencyInjection\ConsentConfiguration;
use Base\Consent\Twig\ConsentExtension as ConsentTwig;
use Base\Routing\AdvancedRouterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * What a sequence or a card frames waits for omnibase/consent's EMBEDS
 * feature: the bundle adds it to the features every site starts from - on
 * demand, with what Canva, Vimeo and YouTube keep - and gives it its words;
 * without omnibase/consent nothing waits.
 */
class EmbedsConsentTest extends TestCase
{
    public function testWithoutConsentTheFramesLoadAtOnce(): void
    {
        $container = new ContainerBuilder();
        (new ClassroomExtension())->prepend($container);
        (new EmbedsConsentPass())->process($container);

        self::assertFalse($container->getParameter('classroom.consent'));
        self::assertFalse($container->hasParameter('consent.defaults'));
    }

    public function testWithConsentEmbedsIsOneOfTheFeaturesEverySiteStartsFrom(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new class extends Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'consent';
            }
        });
        (new ClassroomExtension())->prepend($container);
        self::assertTrue($container->getParameter('classroom.consent'));
        self::assertSame([], $container->getExtensionConfig('consent'), 'nothing the site configured: its own list of features stays its own');

        $container->setParameter('consent.defaults', ['USER' => ['implicit' => true], self::other()[0] => self::other()[1]]);
        (new EmbedsConsentPass())->process($container);
        $defaults = $container->getParameter('consent.defaults');

        self::assertSame(['implicit' => true], $defaults['USER']);
        self::assertTrue($defaults['EMBEDS']['on_demand'], 'listed where a page frames something, never a reason to ask again');
        self::assertSame(['vuid', 'CDI', 'CL', 'yt-remote-*'], array_column($defaults['EMBEDS']['storage'], 'name'), 'another bundle\'s EMBEDS keeps its entries, the ones it does not name are added');
        self::assertSame('1y', $defaults['EMBEDS']['storage'][0]['duration']);
        foreach ($defaults['EMBEDS']['storage'] as $entry) {
            self::assertTrue(isset($entry['domain']) || 'local' === ($entry['type'] ?? null), $entry['name'].': a cookie of the provider\'s domain, or what its player keeps in the browser');
        }
    }

    /** @return array{string, array<string, mixed>} an EMBEDS another bundle declared first */
    private static function other(): array
    {
        return ['EMBEDS', ['on_demand' => true, 'storage' => [['name' => 'vuid', 'domain' => '.vimeo.com', 'duration' => '1y']]]];
    }

    /** As omnibase/consent reads it, under a site's own configuration, with the bundle's words: what the detailed section lists. */
    public function testThePanelListsEmbedsWithTheBundlesWords(): void
    {
        if (!class_exists(ConsentTwig::class)) {
            self::markTestSkipped('omnibase/consent is not installed here.');
        }
        $consentFeatures = \dirname((new \ReflectionClass(ConsentTwig::class))->getFileName(), 3).'/config/features.php';
        $container = new ContainerBuilder();
        $container->setParameter('consent.defaults', require $consentFeatures);
        (new EmbedsConsentPass())->process($container);
        $defaults = $container->getParameter('consent.defaults');

        foreach ([
            'fr' => ['Les contenus intégrés', 'Canva, sur son domaine : un identifiant donné à votre navigateur.'],
            'en' => ['Embedded content', 'Canva, on its own domain: an identifier given to your browser.'],
            'de' => ['Eingebettete Inhalte', 'Canva, auf seiner Domain: eine Kennung für Ihren Browser.'],
        ] as $locale => [$label, $canva]) {
            $categories = $this->categories($defaults, ['groups' => ['USER']], $locale);
            self::assertArrayHasKey('EMBEDS', $categories);
            self::assertTrue($categories['EMBEDS']['onDemand']);
            self::assertFalse($categories['EMBEDS']['default'], 'off until a yes');
            self::assertSame($label, $categories['EMBEDS']['label']);
            self::assertNotEmpty($categories['EMBEDS']['description']);
            $stored = array_column($categories['EMBEDS']['storage'], null, 'name');
            self::assertSame(['CDI', 'CL', 'vuid', 'yt-remote-*'], array_keys($stored));
            self::assertSame($canva, $stored['CDI']['purpose']);
            self::assertSame('www.canva.com', $stored['CDI']['domain']);
            self::assertSame('local', $stored['yt-remote-*']['type']);
        }

        // A site's consent.features.EMBEDS is laid over it, field by field: declared there, it is listed; an entry replaces the one of its name.
        $categories = $this->categories($defaults, ['features' => ['EMBEDS' => ['storage' => [['name' => 'vuid', 'domain' => '.vimeo.com', 'duration' => '13mo']]]]], 'fr');
        self::assertFalse($categories['EMBEDS']['onDemand']);
        self::assertSame('13mo', array_column($categories['EMBEDS']['storage'], null, 'name')['vuid']['duration']);
        self::assertCount(4, $categories['EMBEDS']['storage']);
        // defaults: false, only what the site declares: EMBEDS is known by its words alone.
        self::assertSame([], $this->categories($defaults, ['defaults' => false], 'fr')['EMBEDS']['storage']);
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $site
     *
     * @return array<string, array<string, mixed>>
     */
    private function categories(array $defaults, array $site, string $locale): array
    {
        $translator = new Translator($locale);
        $translator->addLoader('yaml', new YamlFileLoader());
        foreach (['fr', 'en', 'de'] as $each) {
            $translator->addResource('yaml', \dirname(__DIR__, 2).'/translations/consent+intl-icu.'.$each.'.yaml', $each, 'consent+intl-icu');
        }
        $config = (new Processor())->processConfiguration(new ConsentConfiguration(), [$site]);
        $router = $this->createStub(AdvancedRouterInterface::class);

        return array_column((new ConsentTwig($config, $router, $translator, $defaults))->settings()['categories'], null, 'key');
    }
}
