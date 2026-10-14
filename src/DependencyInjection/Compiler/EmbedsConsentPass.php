<?php

namespace Base\Classroom\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * What a sequence or a card frames (Canva, Padlet, a video...) comes with its
 * provider's cookies: with omnibase/consent, it waits for the EMBEDS feature
 * (the name omnibase/video's platform frames use), which this adds to the
 * features every site starts from (consent.defaults, the bundle's own
 * config/features.php) - on demand: listed in the detailed section on a page
 * that frames something, never a reason to ask a visitor again - with what
 * these providers were seen to keep. Its words are this bundle's
 * translations/consent+intl-icu.*.yaml.
 *
 * A default rather than a consent: configuration of its own: the site's
 * consent.features.EMBEDS is laid over it field by field as over any of the
 * bundle's features, `defaults: false` leaves it out, and it is no feature
 * the site configured (data-consent-groups, the list pages served by
 * consent-legacy.js read, stays the site's). Another bundle's EMBEDS keeps
 * its fields; the entries it does not name are added to its storage.
 */
final class EmbedsConsentPass implements CompilerPassInterface
{
    public const FEATURE = 'EMBEDS';

    /** @var list<array{name: string, type?: string, domain?: string, duration?: string, purpose: string}> seen set by the frames themselves, on their own domain (the site can neither read nor erase them) */
    public const STORAGE = [
        ['name' => 'CDI', 'domain' => 'www.canva.com', 'duration' => '1y', 'purpose' => 'embeds_canva'],
        ['name' => 'CL', 'domain' => 'www.canva.com', 'duration' => '60d', 'purpose' => 'embeds_canva_display'],
        ['name' => 'vuid', 'domain' => '.vimeo.com', 'duration' => '2y', 'purpose' => 'embeds_vimeo'],
        ['name' => 'yt-remote-*', 'type' => 'local', 'purpose' => 'embeds_youtube'],
    ];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('consent.defaults')) {
            return;
        }
        $defaults = $container->getParameter('consent.defaults');
        if (!\is_array($defaults)) {
            return;
        }

        $feature = ($defaults[self::FEATURE] ?? []) + ['on_demand' => true, 'storage' => []];
        $named = array_map(static fn (array $entry): string => ($entry['type'] ?? 'cookie').' '.$entry['name'], $feature['storage']);
        foreach (self::STORAGE as $entry) {
            if (!\in_array(($entry['type'] ?? 'cookie').' '.$entry['name'], $named, true)) {
                $feature['storage'][] = $entry;
            }
        }
        $defaults[self::FEATURE] = $feature;
        $container->setParameter('consent.defaults', $defaults);
    }
}
