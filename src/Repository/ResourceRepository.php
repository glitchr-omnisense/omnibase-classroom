<?php

namespace Base\Classroom\Repository;

use Base\Classroom\Entity\Resource;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/** @extends ServiceEntityRepository<Resource> */
class ResourceRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Resource::class);
    }

    /** @return list<Resource> the published ones, newest first, of the taxa given */
    public function findPublished(array $taxa = [], int $limit = 200): array
    {
        $criteria = ['published' => true];
        if ([] !== $taxa = array_values(array_filter($taxa))) {
            $criteria[] = Criterion::hasAll('taxa.slug', $taxa);
        }

        return $this->findBy($criteria, ['createdAt' => 'DESC'], $limit);
    }

    public function findOnePublished(string $slug): ?Resource
    {
        return $this->findOneBy(['published' => true, 'slug' => $slug]);
    }

    /** @return list<Resource> */
    public function search(string $term, int $limit = 20): array
    {
        return $this->findBy([
            'published' => true,
            Group::of(Parser::SEPARATOR_OR,
                Criterion::of('title', '%'.$term.'%', Parser::OPTION_PARTIAL),
                Criterion::of('description', '%'.$term.'%', Parser::OPTION_PARTIAL),
            ),
        ], null, $limit);
    }

    public function sumDownloads(): int
    {
        return (int) ($this->sumOfDownloadsBy([]) ?? 0);
    }
}
