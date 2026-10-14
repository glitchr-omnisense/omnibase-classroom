<?php

namespace Base\Classroom\Tests\Controller;

use Base\Classroom\Controller\Admin\Crud\CardCrudController;
use Base\Classroom\Controller\Admin\Crud\ResourceCrudController;
use Base\Classroom\Controller\Admin\Crud\SequenceCrudController;
use Base\Classroom\Controller\Admin\Crud\SessionCrudController;
use Base\Classroom\Entity\Level;
use Base\Classroom\Entity\Purpose;
use Base\Classroom\Entity\Subject;
use Base\Field\FieldDescriptor;
use PHPUnit\Framework\TestCase;

/**
 * What the classroom's screens declare, read without a kernel: one form field
 * per property (two pickers on `taxa` were one field, the last), every field
 * written through the entity's own accessors, and only what a card, a
 * sequence, a resource or a session cannot do without marked required - the
 * browser refuses to send a form with a required field left empty (the links,
 * the frames, the picture of a card stopped it).
 * The saving itself is a host's functional test (apfelschule's ClassroomAdminTest).
 */
class CrudFieldsTest extends TestCase
{
    /** What may be marked required, by screen: the rest is optional. */
    private const REQUIRED = [
        CardCrudController::class => ['title', 'state', 'position', 'slug', 'featured', 'content'],
        SequenceCrudController::class => ['title', 'state', 'slug', 'featured', 'content'],
        ResourceCrudController::class => ['title', 'visibilityValue', 'published', 'slug', 'file'],
        SessionCrudController::class => ['parent', 'position', 'state', 'title', 'slug', 'content'],
    ];

    /** The pickers of the taxa, by screen: property => class. */
    private const PICKERS = [
        CardCrudController::class => ['levels' => Level::class, 'subjects' => Subject::class],
        SequenceCrudController::class => ['levels' => Level::class, 'subjects' => Subject::class, 'purposes' => Purpose::class],
        ResourceCrudController::class => ['levels' => Level::class, 'subjects' => Subject::class],
        SessionCrudController::class => [],
    ];

    /**
     * Every screen's new and edit forms (a loop rather than a data provider:
     * the suite runs on PHPUnit 9 in omnibase's harness and on 12 in a host).
     *
     * @return iterable<string, array{class-string, string}>
     */
    private static function screens(): iterable
    {
        foreach (array_keys(self::REQUIRED) as $controller) {
            foreach ([FieldDescriptor::PAGE_NEW, FieldDescriptor::PAGE_EDIT] as $page) {
                yield substr(strrchr($controller, '\\'), 1).' '.$page => [$controller, $page];
            }
        }
    }

    public function testOneFieldPerPropertyWrittenThroughTheEntity(): void
    {
        foreach (self::screens() as $screen => [$controller, $page]) {
            $this->assertOneFieldPerProperty($screen, $controller, $page);
        }
    }

    private function assertOneFieldPerProperty(string $screen, string $controller, string $page): void
    {
        $fields = self::fields($controller, $page);
        $names = array_map(fn (FieldDescriptor $field) => $field->getPropertyWithSuffix(), $fields);
        self::assertSame(array_values(array_unique($names)), $names, $screen.': no property twice');
        self::assertNotContains('taxa', $names, $screen.': a picker per kind, not one on taxa');

        $entity = $controller::getEntityFqcn();
        foreach ($fields as $field) {
            $property = ucfirst($field->getProperty());
            $written = method_exists($entity, 'set'.$property) || (method_exists($entity, 'add'.rtrim($property, 's')) && method_exists($entity, 'remove'.rtrim($property, 's')));
            self::assertTrue($written, $entity.' writes '.$field->getProperty().' (a setter, or an adder and a remover)');
        }
    }

    /** The slug follows the title as it is typed (else it stays empty, required, and the form is not sent). */
    public function testTheSlugFollowsTheTitle(): void
    {
        foreach (self::screens() as $screen => [$controller, $page]) {
            $slug = array_values(array_filter(self::fields($controller, $page), fn (FieldDescriptor $field) => 'slug' === $field->getProperty()))[0] ?? null;
            self::assertNotNull($slug, $screen.': a slug');
            self::assertSame('title', $slug->getFormTypeOptions()['target'] ?? null, $screen);
        }
    }

    public function testEachKindOfTaxaHasItsPicker(): void
    {
        foreach (self::screens() as $screen => [$controller, $page]) {
            $this->assertPickers($screen, $controller, $page);
        }
    }

    private function assertPickers(string $screen, string $controller, string $page): void
    {
        $pickers = [];
        foreach (self::fields($controller, $page) as $field) {
            if (isset(self::PICKERS[$controller][$field->getProperty()])) {
                $pickers[$field->getProperty()] = $field->getFormTypeOptions()['class'] ?? null;
                self::assertTrue($field->getFormTypeOptions()['multiple'] ?? false, $field->getProperty().' takes several');
                self::assertFalse($field->isRequired(), $field->getProperty().' may be left empty');
                self::assertFalse($field->isSortable(), 'a list is not sorted by '.$field->getProperty().': no column');
            }
        }
        self::assertSame(self::PICKERS[$controller], $pickers, $screen);
    }

    public function testOnlyWhatTheRecordNeedsIsRequired(): void
    {
        $required = [];
        foreach (self::screens() as $screen => [$controller, $page]) {
            foreach (self::fields($controller, $page) as $field) {
                if (false !== $field->isRequired() && !\in_array($field->getProperty(), self::REQUIRED[$controller], true)) {
                    $required[] = $screen.': '.$field->getProperty();
                }
            }
        }
        self::assertSame([], $required, 'left empty, these would stop the browser');
    }

    /** @return list<FieldDescriptor> the fields the page's form holds */
    private static function fields(string $controller, string $page): array
    {
        // configureFields() needs none of the controller's services.
        $crud = (new \ReflectionClass($controller))->newInstanceWithoutConstructor();
        $fields = [];
        foreach ($crud->configureFields($page) as $field) {
            $descriptor = $field->getAsDto();
            if ($descriptor->isDisplayedOn($page)) {
                $fields[] = $descriptor;
            }
        }

        return $fields;
    }
}
