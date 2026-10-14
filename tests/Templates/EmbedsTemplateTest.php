<?php

namespace Base\Classroom\Tests\Templates;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @Classroom/client/_embeds.html.twig: on a site with omnibase/consent a frame
 * has no address until the visitor accepts EMBEDS, and a sentence with a
 * button to the cookie panel stands in its place; without it, the frame
 * loads with the page. Rendered with the bundle's words, glitchr/omnibase's
 * embed_url() answered here.
 */
class EmbedsTemplateTest extends TestCase
{
    private const FRAMES = [
        'https://www.canva.com/design/DAGx1y2Z3/AbCdEf/view' => ['provider' => 'canva', 'src' => 'https://www.canva.com/design/DAGx1y2Z3/view?embed', 'ratio' => '16 / 9', 'height' => null, 'known' => true],
        'https://youtu.be/M7lc1UVf-VE' => ['provider' => 'youtube', 'src' => 'https://www.youtube-nocookie.com/embed/M7lc1UVf-VE', 'ratio' => '16 / 9', 'height' => null, 'known' => true],
        'https://soundcloud.com/a/b' => ['provider' => 'sound-cloud', 'src' => 'https://w.soundcloud.com/player/?url=b', 'ratio' => '16 / 9', 'height' => 400, 'known' => false],
    ];

    private static function render(?string $consent, string $locale = 'fr'): \DOMXPath
    {
        $translator = new Translator($locale);
        $translator->addLoader('yaml', new YamlFileLoader());
        foreach (['fr', 'en', 'de'] as $each) {
            $translator->addResource('yaml', \dirname(__DIR__, 2).'/translations/classroom+intl-icu.'.$each.'.yaml', $each, 'classroom+intl-icu');
        }

        $loader = new FilesystemLoader();
        $loader->addPath(\dirname(__DIR__, 2).'/templates', 'Classroom');
        $twig = new Environment($loader, ['strict_variables' => true, 'autoescape' => 'html']);
        // omnibase's "@domain.key" translations.
        $twig->addFilter(new TwigFilter('trans', static function (string $key, array $parameters = []) use ($translator): string {
            [$domain, $id] = str_starts_with($key, '@') ? explode('.', substr($key, 1), 2) : ['messages', $key];

            return $translator->trans($id, $parameters, $domain);
        }));
        $twig->addFunction(new TwigFunction('embed_url', static fn (string $url): ?array => self::FRAMES[$url] ?? null));
        $twig->addFunction(new TwigFunction('classroom_consent', static fn (): ?string => $consent));

        $html = $twig->render('@Classroom/client/_embeds.html.twig', ['thing' => ['embeds' => [
            ['url' => 'https://www.canva.com/design/DAGx1y2Z3/AbCdEf/view', 'label' => 'Les couleurs'],
            ['url' => 'https://youtu.be/M7lc1UVf-VE', 'label' => ''],
            ['url' => 'https://soundcloud.com/a/b', 'label' => ''],
            ['url' => 'https://example.org/fiche.pdf', 'label' => 'La fiche'],
        ]]]);
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8"?>'.$html);

        return new \DOMXPath($document);
    }

    public function testWithConsentNoFrameHasAnAddressBeforeTheYes(): void
    {
        $page = self::render('EMBEDS');

        $frames = $page->query('//iframe');
        self::assertSame(3, $frames->length, 'Canva, YouTube, the oEmbed player; the PDF stays a link');
        foreach ($frames as $frame) {
            self::assertFalse($frame->hasAttribute('src'), 'nothing is loaded from the provider before the yes');
            self::assertSame('EMBEDS', $frame->getAttribute('data-consent-script'));
            self::assertStringStartsWith('https://', $frame->getAttribute('data-consent-src'));
        }
        self::assertSame('https://www.canva.com/design/DAGx1y2Z3/view?embed', $frames->item(0)->getAttribute('data-consent-src'));

        $holders = $page->query('//*[@data-consent-placeholder="EMBEDS"]');
        self::assertSame(3, $holders->length, 'one in each frame\'s place');
        self::assertStringContainsString('Ce contenu vient de Canva, qui dépose ses propres cookies', $holders->item(0)->textContent);
        self::assertStringContainsString('Ce contenu vient de YouTube', $holders->item(1)->textContent);
        self::assertStringContainsString('Ce contenu vient de Sound cloud', $holders->item(2)->textContent, 'a provider not known by name: its name as embed_url() gives it');
        $button = $page->query('.//button', $holders->item(0))->item(0);
        self::assertSame('details', $button->getAttribute('data-consent-open'), 'opens the detailed section, where EMBEDS is listed');
        self::assertSame('Gérer les cookies', trim($button->textContent));

        self::assertSame('Les couleurs', $frames->item(0)->getAttribute('title'));
        self::assertSame('YouTube', $frames->item(1)->getAttribute('title'));
        self::assertSame(1, $page->query('//p[@class="classroom-embed-link"]/a[@href="https://example.org/fiche.pdf"]')->length);
        self::assertSame(1, $page->query('//figcaption/a[.="Ouvrir dans Canva"]')->length, 'the link to the provider is there before the yes');
    }

    public function testWithoutConsentTheFramesLoadWithThePage(): void
    {
        $page = self::render(null);

        self::assertSame(['https://www.canva.com/design/DAGx1y2Z3/view?embed', 'https://www.youtube-nocookie.com/embed/M7lc1UVf-VE', 'https://w.soundcloud.com/player/?url=b'], array_map(static fn (\DOMElement $frame): string => $frame->getAttribute('src'), iterator_to_array($page->query('//iframe'))));
        self::assertSame(0, $page->query('//*[@data-consent-placeholder] | //*[@data-consent-src]')->length);
    }

    public function testThePlaceholderSpeaksEveryLanguageOfTheBundle(): void
    {
        foreach (['en' => ['This content comes from Canva, which sets its own cookies', 'Manage cookies'], 'de' => ['Dieser Inhalt stammt von Canva, das eigene Cookies setzt', 'Cookies verwalten']] as $locale => [$sentence, $button]) {
            $holder = self::render('EMBEDS', $locale)->query('//*[@data-consent-placeholder="EMBEDS"]')->item(0);
            self::assertStringContainsString($sentence, $holder->textContent);
            self::assertStringContainsString($button, $holder->textContent);
        }
    }

    /** classroom+intl-icu and consent+intl-icu (the EMBEDS feature's words): the same keys in fr, en and de. */
    public function testEveryLanguageHasEveryKey(): void
    {
        foreach (['classroom', 'consent'] as $domain) {
            $keys = [];
            foreach (['fr', 'en', 'de'] as $locale) {
                $translator = new Translator($locale);
                $translator->addLoader('yaml', new YamlFileLoader());
                $translator->addResource('yaml', \dirname(__DIR__, 2).'/translations/'.$domain.'+intl-icu.'.$locale.'.yaml', $locale, $domain.'+intl-icu');
                $keys[$locale] = array_keys($translator->getCatalogue($locale)->all($domain.'+intl-icu'));
                sort($keys[$locale]);
            }
            self::assertSame($keys['fr'], $keys['en'], $domain.': en');
            self::assertSame($keys['fr'], $keys['de'], $domain.': de');
        }
    }
}
