<?php

namespace Base\Classroom\Tests\Controller;

use Base\Admin\Test\CrudFieldsTestCase;
use Base\Classroom\Controller\Admin\Crud\ResourceCrudController;
use Base\Classroom\Controller\Admin\Crud\SessionCrudController;
use Base\Classroom\Controller\Admin\Crud\ResourceOfferCrudController;

/**
 * The fields marked required on this bundle's back office screens, against
 * what its records need (see CrudFieldsTestCase): only those the record
 * cannot do without, all of them, an optional one able to take an empty
 * value, one field per property, a slug following its title.
 */
final class CrudRequiredFieldsTest extends CrudFieldsTestCase
{
    protected static function sourceDirectory(): string
    {
        return __DIR__.'/../../src';
    }

    protected static function requiredByTheScreen(): array
    {
        return [
            ResourceCrudController::class => [
                'file' => 'a resource is the file it hands out (required on a new one only: an edited one keeps its file)',
            ],
            SessionCrudController::class => [
                'parent' => 'a session is a step of a sequence, listed and numbered there',
            ],
            ResourceOfferCrudController::class => [
                'parent' => "a product is sold in a store: its page, its basket and its orders are the store's",
            ],
        ];
    }
}
