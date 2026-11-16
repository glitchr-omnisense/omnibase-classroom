<?php

namespace Base\Classroom\Repository;

use Base\Classroom\Entity\Session;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/** @extends ServiceEntityRepository<Session> */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Session::class);
    }
}
