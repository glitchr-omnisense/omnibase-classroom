<?php

namespace Base\Classroom\Tests\Entity;

use Base\Classroom\Entity\Card;
use Base\Classroom\Entity\Level;
use Base\Classroom\Entity\Purpose;
use Base\Classroom\Entity\Resource;
use Base\Classroom\Entity\Sequence;
use Base\Classroom\Entity\Subject;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * The levels, the subjects and the purposes share one association (taxa)
 * and are written one kind at a time - the back office's three pickers:
 * setting one kind leaves the others where they are, and what is not of
 * that kind (an identifier no entity was found for) never reaches the
 * association. The entities are built without their constructor (a thread's
 * needs omnibase's services), their taxa an ArrayCollection.
 */
class TaxaOfKindTest extends TestCase
{
    public function testACardsLevelsAreReplacedAndItsSubjectsStay(): void
    {
        [$cp, $ce1, $ce2] = [self::level(1), self::level(2), self::level(3)];
        $german = self::taxon(Subject::class);
        $card = self::thread(Card::class);

        $card->setLevels([$ce1, $cp])->setSubjects([$german]);
        self::assertSame([$cp, $ce1], $card->getLevels(), 'read back in their order');
        self::assertSame([$german], $card->getSubjects());

        $card->setLevels([$ce2]);
        self::assertSame([$ce2], $card->getLevels());
        self::assertSame([$german], $card->getSubjects(), 'the subjects stay');
        self::assertCount(2, $card->getTaxa());

        $card->setLevels(null);
        self::assertSame([], $card->getLevels());
        self::assertSame([$german], $card->getSubjects());
    }

    public function testASequencesThreeKindsAreWrittenApart(): void
    {
        $sequence = self::thread(Sequence::class);
        $level = self::level(1);
        $subject = self::taxon(Subject::class);
        [$first, $second] = [self::taxon(Purpose::class), self::taxon(Purpose::class)];

        $sequence->setLevels([$level])->setSubjects([$subject])->setPurposes([$first]);
        $sequence->setPurposes(new ArrayCollection([$second, $second]));

        self::assertSame([$level], $sequence->getLevels());
        self::assertSame([$subject], $sequence->getSubjects());
        self::assertSame([$second], $sequence->getPurposes(), 'once, in place of the first');
        self::assertCount(3, $sequence->getTaxa());
    }

    /** What the picker of a kind may hand over that is not of its kind: an identifier left as it was sent, a taxon of another kind. */
    public function testWhatIsNotOfTheKindIsIgnored(): void
    {
        $card = self::thread(Card::class);
        $level = self::level(1);
        $subject = self::taxon(Subject::class);

        $card->setSubjects(['117', $level, $subject, null]);
        self::assertSame([$subject], $card->getTaxa()->toArray());
    }

    public function testAResourceIsWrittenTheSameWay(): void
    {
        $resource = new Resource();
        [$cp, $ce1] = [self::level(1), self::level(2)];
        $german = self::taxon(Subject::class);

        $resource->setLevels([$cp])->setSubjects([$german]);
        $resource->setLevels([$ce1]);
        self::assertSame([$ce1], $resource->getLevels());
        self::assertSame([$german], $resource->getSubjects());
        self::assertCount(2, $resource->getTaxa());
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private static function thread(string $class): object
    {
        $thread = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty($thread, 'taxa'))->setValue($thread, new ArrayCollection());

        return $thread;
    }

    private static function level(int $position): Level
    {
        return self::taxon(Level::class)->setPosition($position);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private static function taxon(string $class): object
    {
        return (new \ReflectionClass($class))->newInstanceWithoutConstructor();
    }
}
