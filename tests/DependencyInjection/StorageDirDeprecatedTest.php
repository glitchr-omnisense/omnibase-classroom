<?php

namespace Tests\Base\Classroom\DependencyInjection;

use Base\Classroom\DependencyInjection\ClassroomConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * classroom.storage_dir is deprecated: it was declared and read by nothing (the resources go to
 * the flysystem storage local.classroom, whose directory the site sets). Not removed: a site's
 * classroom.yaml still sets it.
 */
final class StorageDirDeprecatedTest extends TestCase
{
    public function testStorageDirIsDeprecated(): void
    {
        $tree = (new ClassroomConfiguration())->getConfigTreeBuilder()->buildTree();
        self::assertTrue($tree->getChildren()['storage_dir']->isDeprecated());
        self::assertFalse($tree->getChildren()['download_ttl']->isDeprecated());
    }
}
