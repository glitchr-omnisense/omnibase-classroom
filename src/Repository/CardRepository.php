<?php

namespace Base\Classroom\Repository;

use Base\Classroom\Entity\Card;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/** @extends ServiceEntityRepository<Card> */
class CardRepository extends ServiceEntityRepository
{
    use PublishedThreadsTrait;

    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Card::class);
    }

    /** The fan: the featured cards in order, then the newest, up to $limit. @return list<Card> */
    public function findFeatured(int $limit = 9): array
    {
        return $this->findBy($this->published(), ['featured' => 'DESC', 'position' => 'ASC', 'publishedAt' => 'DESC'], $limit);
    }

    /** @return list<Card> */
    public function findPublished(array $taxa = [], int $limit = 200): array
    {
        return $this->findBy([...$this->published(), ...self::taxa($taxa)], ['position' => 'ASC', 'publishedAt' => 'DESC'], $limit);
    }

    public function findOnePublished(string $slug): ?Card
    {
        return $this->findOneBy($this->published() + ['slug' => $slug]);
    }

    /** @return list<Card> */
    public function search(string $term, int $limit = 20): array
    {
        return $this->findBy($this->published() + [
            Group::of(Parser::SEPARATOR_OR,
                Criterion::of('translations.title', '%'.$term.'%', Parser::OPTION_PARTIAL),
                Criterion::of('translations.headline', '%'.$term.'%', Parser::OPTION_PARTIAL),
                Criterion::of('translations.content', '%'.$term.'%', Parser::OPTION_PARTIAL),
            ),
        ], null, $limit);
    }
}
