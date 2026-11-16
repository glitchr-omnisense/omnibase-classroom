<?php

namespace Base\Classroom\Repository;

use Base\Contract\Runtime;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Type\Utc;
use Base\Enum\ThreadState;

/** The criteria of a thread published and not scheduled for later, and of its taxa, for the classroom's thread repositories. */
trait PublishedThreadsTrait
{
    /**
     * State published, and no publication date or one passed. "Passed" is read on the application's clock, in UTC.
     *
     * @return array<int|string, mixed>
     */
    private function published(): array
    {
        return [
            'state' => ThreadState::PUBLISH,
            Group::of(Parser::SEPARATOR_OR,
                Criterion::of('publishedAt', null, Parser::OPTION_NULL),
                Criterion::of('publishedAt', Utc::from(Runtime::clock()->now()), Parser::OPTION_LOWER_EQUAL),
            ),
        ];
    }

    /**
     * Every taxon slug given among the record's taxa (none given: no condition).
     *
     * @param array<string|null> $taxa
     *
     * @return array<int, Criterion>
     */
    private static function taxa(array $taxa): array
    {
        $taxa = array_values(array_filter($taxa));

        return [] === $taxa ? [] : [Criterion::hasAll('taxa.slug', $taxa)];
    }
}
