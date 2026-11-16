<?php

namespace Base\Classroom\Repository;

use App\Entity\User;
use Base\Classroom\Entity\Entitlement;
use Base\Classroom\Entity\Resource;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/** @extends ServiceEntityRepository<Entitlement> */
class EntitlementRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Entitlement::class);
    }

    /** @return list<Entitlement> what this person bought, newest first */
    public function findForUser(?object $user): array
    {
        return $user instanceof User ? $this->findBy(['user' => Key::of($user)], ['createdAt' => 'DESC']) : [];
    }

    public function findOne(?object $user, Resource $resource): ?Entitlement
    {
        return $user instanceof User ? $this->findOneBy(['user' => Key::of($user), 'resource' => Key::of($resource)]) : null;
    }

    /** Since the first of the month: how many downloads were bought. */
    public function countSince(\DateTimeInterface $since): int
    {
        return $this->count([Group::of(Parser::SEPARATOR_AND, Criterion::of('createdAt', $since, Parser::OPTION_GREATER_EQUAL))]);
    }
}
