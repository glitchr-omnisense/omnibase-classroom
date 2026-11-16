<?php

namespace Base\Classroom\Tests\Persistence;

use Base\Contract\Locales;
use Base\Contract\Runtime;
use Base\Testing\Engines;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Base\Classroom\Entity\Card;
use Base\Classroom\Entity\Level;
use Base\Classroom\Entity\Resource;
use Base\Classroom\Entity\Sequence;
use Base\Classroom\Entity\Subject;
use Base\Classroom\Repository\CardRepository;
use Base\Classroom\Repository\ResourceRepository;
use Base\Classroom\Repository\SequenceRepository;
use App\Entity\User; // the host's user, Base\Entity\User where it has none (ClassAliases, registered by Engines::of())
use Base\Enum\ThreadState;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The classroom's filters by several taxa at once (written once, Criterion::hasAll()): every slug given among the
 * record's taxa, the same records on
 * both engines, in the same order.
 */
final class ClassifiedStatementsParityTest extends TestCase
{
    private static Engines $engines;

    public static function backends(): array
    {
        return Engines::backends();
    }

    /** The application's clock, which a case moves (Runtime::clock()); PHP's zone put back after each case. */
    private MockClock $clock;
    private string $zone;

    protected function setUp(): void
    {
        $this->zone = date_default_timezone_get();
        $this->clock = new MockClock('2026-06-01 12:00:00', 'UTC');
        Runtime::set(ClockInterface::class, $this->clock);
    }

    protected function tearDown(): void
    {
        Runtime::set(ClockInterface::class, null);
        date_default_timezone_set($this->zone);
    }

    public static function setUpBeforeClass(): void
    {
        // The entities' constructors read the runtime's locale (User::setLocale()): one, as a host gives it.
        if (!Runtime::has(Locales::class)) {
            Runtime::set(Locales::class, new class implements Locales {
                public function getLocale(): string { return 'fr'; }
                public function getDefaultLocale(): string { return 'fr'; }
                public function getAvailableLocales(): array { return ['fr']; }
                public function getTimezone(): string { return 'UTC'; }
            });
        }
        self::$engines = Engines::of([Card::class, Sequence::class, Resource::class, \Base\Marketplace\Entity\Product::class], ['project_dir' => \dirname(__DIR__, 2), 'enumerations' => [\dirname((string) (new \ReflectionClass(\Base\Marketplace\Entity\Product::class))->getFileName(), 2).'/Enum'], 'resolve_target_entities' => ['Base\\Marketplace\\Model\\MerchantInterface' => User::class]]);
        $seed = [];
        $owner = self::user('teacher@example.org');
        $cp = new Level('CP', 'cp');
        $ce1 = new Level('CE1', 'ce1');
        $cm1 = new Level('CM1', 'cm1');
        $maths = new Subject('Maths', 'maths');
        $seed[] = $owner;
        foreach ([$cp, $ce1, $cm1, $maths] as $taxon) {
            $seed[] = $taxon;
        }

        $resources = [
            ['cp-maths', [$cp, $maths], true, '2026-01-01'],
            ['cp-only', [$cp], true, '2026-01-02'],
            ['ce1-maths', [$ce1, $maths], true, '2026-01-03'],
            ['cp-maths-hidden', [$cp, $maths], false, '2026-01-04'],
            ['cp-maths-late', [$cp, $maths], true, '2026-01-05'],
        ];
        foreach ($resources as [$slug, $taxa, $published, $at]) {
            $resource = (new Resource($owner, $slug))->setSlug($slug)->setPublished($published);
            foreach ($taxa as $taxon) {
                $resource->addTaxon($taxon);
            }
            $seed[] = $resource;
        }
        $threads = [
            [Card::class, 'card-a', [$cp, $maths], ThreadState::PUBLISH, '2026-01-01', 2],
            [Card::class, 'card-b', [$cp, $maths], ThreadState::PUBLISH, '2026-01-02', 1],
            [Card::class, 'card-draft', [$cp, $maths], ThreadState::DRAFT, '2026-01-03', 0],
            [Card::class, 'card-future', [$cp, $maths], ThreadState::PUBLISH, '2099-01-01', 0],
            [Card::class, 'card-ce1', [$ce1, $maths], ThreadState::PUBLISH, '2026-01-04', 0],
            [Card::class, 'card-at-ten', [$cm1], ThreadState::PUBLISH, '2026-03-01 10:00:00', 0],
            [Sequence::class, 'seq-a', [$cp, $maths], ThreadState::PUBLISH, '2026-01-01', null],
            [Sequence::class, 'seq-b', [$cp, $maths], ThreadState::PUBLISH, '2026-01-05', null],
            [Sequence::class, 'seq-c', [$cp], ThreadState::PUBLISH, '2026-01-06', null],
        ];
        foreach ($threads as [$class, $slug, $taxa, $state, $at, $position]) {
            $thread = new $class($owner, null, $slug, $slug);
            $thread->setState($state);
            $thread->setPublishedAt(new \DateTime($at, new \DateTimeZone('UTC')));
            if (null !== $position) {
                $thread->setPosition($position);
            }
            if ('seq-b' === $slug) {
                $thread->setPeriod('p1');
            }
            foreach ($taxa as $taxon) {
                $thread->addTaxon($taxon);
            }
            $seed[] = $thread;
        }
        self::$engines->seed(...$seed);

        // #[Timestamp(on: 'create')] dates the resources at the flush: their days are written after it.
        $resource = self::$engines->description()->entities[Resource::class];
        $dated = self::$engines->pdo()->prepare(sprintf('UPDATE %s SET %s = ? WHERE %s = ?', $resource->table, $resource->fields['createdAt']->column, $resource->fields['slug']->column));
        foreach ($resources as [$slug, , , $at]) {
            $dated->execute([$at.' 10:00:00', $slug]);
        }
    }

    /** @param iterable<object> $records @return list<string> */
    private static function slugs(iterable $records): array
    {
        return array_values(array_map(static fn (object $record): string => (string) $record->getSlug(), [...$records]));
    }

    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testResourcesOfSeveralTaxa(string $backend): void
    {
        $resources = (new ResourceRepository(self::$engines->finders($backend)));

        self::assertSame(['cp-maths-late', 'cp-maths'], self::slugs($resources->findPublished(['cp', 'maths'])), 'every slug required, published only, newest first');
        self::assertSame(['cp-maths-late', 'cp-only', 'cp-maths'], self::slugs($resources->findPublished(['cp'])));
        self::assertSame(['cp-maths-late'], self::slugs($resources->findPublished(['cp', 'maths'], 1)), 'the limit');
        self::assertSame([], $resources->findPublished(['cp', 'ce1']));
    }

    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testCardsOfSeveralTaxa(string $backend): void
    {
        $cards = (new CardRepository(self::$engines->finders($backend)));

        self::assertSame(['card-b', 'card-a'], self::slugs($cards->findPublished(['cp', 'maths'])), 'published, not scheduled, by position');
        self::assertSame(['card-ce1'], self::slugs($cards->findPublished(['ce1', 'maths'])));
    }

    /**
     * "Not scheduled for later" is read on the application's clock, in UTC, by the statement (the taxa) and by the
     * finder (one card) alike: a card published at 10:00 UTC is out at 10:30 UTC, not at 09:30 UTC - also with PHP in
     * Tokyo, where 09:30 UTC is 18:30 (a comparison of wall clocks would let it out).
     *
     * @dataProvider backends
     */
    #[DataProvider('backends')]
    public function testPublishedOnTheApplicationsClockInUtc(string $backend): void
    {
        date_default_timezone_set('Asia/Tokyo');
        $cards = (new CardRepository(self::$engines->finders($backend)));

        $this->clock->modify('2026-03-01 09:30:00 UTC');
        self::assertSame([], self::slugs($cards->findPublished(['cm1'])), 'half an hour early');
        self::assertNull($cards->findOnePublished('card-at-ten'));

        $this->clock->modify('2026-03-01 10:30:00 UTC');
        self::assertSame(['card-at-ten'], self::slugs($cards->findPublished(['cm1'])), 'half an hour after');
        self::assertSame('card-at-ten', $cards->findOnePublished('card-at-ten')?->getSlug());
    }

    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testSequencesOfSeveralTaxaPagedAndCounted(string $backend): void
    {
        $sequences = (new SequenceRepository(self::$engines->finders($backend)));

        self::assertSame(['seq-b', 'seq-a'], self::slugs($sequences->findPublishedPage(1, 24, ['cp', 'maths'])), 'newest first');
        self::assertSame(['seq-a'], self::slugs($sequences->findPublishedPage(2, 1, ['cp', 'maths'])), 'the second page of one');
        self::assertSame(2, $sequences->countPublished(['cp', 'maths']));
        self::assertSame(3, $sequences->countPublished(['cp']));
        self::assertSame(1, $sequences->countPublished(['cp', 'maths'], 'p1'), 'the period');
        self::assertSame(['seq-b'], self::slugs($sequences->findPublishedPage(1, 24, ['cp'], 'p1')));
    }

    /** A user as the host builds one: its own User may require more than an e-mail (a username, written when it has one). */
    private static function user(string $email): User
    {
        $user = new User($email);
        $accessor = PropertyAccess::createPropertyAccessor();
        if ($accessor->isWritable($user, 'username')) {
            $accessor->setValue($user, 'username', strstr($email, '@', true));
        }

        return $user;
    }
}
