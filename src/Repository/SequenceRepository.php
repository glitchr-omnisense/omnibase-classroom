<?php

namespace Base\Classroom\Repository;

use Base\Classroom\Entity\Sequence;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;
use Base\Enum\ThreadState;

/** @extends ServiceEntityRepository<Sequence> */
class SequenceRepository extends ServiceEntityRepository
{
    use PublishedThreadsTrait;

    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Sequence::class);
    }

    /** @return list<Sequence> newest first, filtered by the taxa slugs given and the period */
    public function findPublishedPage(int $page = 1, int $perPage = 24, array $taxa = [], ?string $period = null): array
    {
        return $this->findBy([...$this->published(), ...self::taxa($taxa)] + ($period ? ['periodValue' => $period] : []), ['publishedAt' => 'DESC', 'id' => 'DESC'], $perPage, max(0, $page - 1) * $perPage);
    }

    public function countPublished(array $taxa = [], ?string $period = null): int
    {
        return $this->count([...$this->published(), ...self::taxa($taxa)] + ($period ? ['periodValue' => $period] : []));
    }

    /** @return list<Sequence> */
    public function findLatest(int $limit = 3): array
    {
        return $this->findBy($this->published(), ['featured' => 'DESC', 'publishedAt' => 'DESC'], $limit);
    }

    public function findOnePublished(string $slug): ?Sequence
    {
        return $this->findOneBy($this->published() + ['slug' => $slug]);
    }

    /** @return list<Sequence> */
    public function search(string $term, int $limit = 20): array
    {
        return $this->findBy($this->published() + [
            Group::of(Parser::SEPARATOR_OR,
                Criterion::of('translations.title', '%'.$term.'%', Parser::OPTION_PARTIAL),
                Criterion::of('translations.excerpt', '%'.$term.'%', Parser::OPTION_PARTIAL),
                Criterion::of('translations.content', '%'.$term.'%', Parser::OPTION_PARTIAL),
            ),
        ], null, $limit);
    }

    public function countDrafts(): int
    {
        return $this->count(['state' => ThreadState::DRAFT]);
    }
}
