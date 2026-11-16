<?php

namespace Base\Classroom\Service;

use Base\Classroom\Entity\Level;
use Base\Classroom\Entity\Purpose;
use Base\Classroom\Entity\Subject;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Repositories;

/** The levels, subjects and purposes, in order, as the menus and filters list them. */
final class Taxa
{
    public function __construct(private readonly Repositories $repositories)
    {
    }

    /** @return list<Level> the cycles, each holding its levels */
    public function cycles(): array
    {
        return $this->repositories->of(Level::class)->findBy(['parent' => Key::of(null)], ['position' => 'ASC', 'id' => 'ASC']);
    }

    /** @return list<Level> the levels alone (CP, CE1...), in order */
    public function levels(): array
    {
        $levels = array_filter($this->repositories->of(Level::class)->findBy([], ['position' => 'ASC', 'id' => 'ASC']), fn (Level $l) => !$l->isCycle());

        return array_values($levels);
    }

    /** @return list<Subject> the top subjects, each holding its domains */
    public function subjects(): array
    {
        return $this->repositories->of(Subject::class)->findBy(['parent' => Key::of(null)], ['position' => 'ASC', 'id' => 'ASC']);
    }

    /** @return list<Purpose> */
    public function purposes(): array
    {
        return $this->repositories->of(Purpose::class)->findBy(['parent' => Key::of(null)], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function level(string $slug): ?Level { return $this->repositories->of(Level::class)->findOneBy(['slug' => $slug]); }
    public function subject(string $slug): ?Subject { return $this->repositories->of(Subject::class)->findOneBy(['slug' => $slug]); }
    public function purpose(string $slug): ?Purpose { return $this->repositories->of(Purpose::class)->findOneBy(['slug' => $slug]); }
}
