<?php

namespace Base\Classroom\Tests\Controller;

use Base\Admin\Form\FieldFormBuilder;
use Base\Classroom\Controller\Admin\Crud\SequenceCrudController;
use Base\Classroom\Entity\Sequence;
use Base\Field\FieldDescriptor;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * A new sequence has no period: the entity holds none, the select of the
 * form starts with an empty option (no P1 chosen for the teacher), and sent
 * empty the period stays empty. P1 was shown, and sent, by a select printed
 * without that option (glitchr/omnibase before daaf428).
 * In a host application only (omnibase's harness): the form is built by
 * omnibase/admin's FieldFormBuilder with omnibase's SelectType.
 */
class PeriodFieldTest extends KernelTestCase
{
    protected function setUp(): void
    {
        if (!class_exists('App\\Kernel')) {
            self::markTestSkipped('Requires a host application (the omnibase harness).');
        }

        self::bootKernel();
        $request = Request::create('/admin/sequences');
        $request->setSession(new Session(new MockArraySessionStorage()));
        static::getContainer()->get('request_stack')->push($request);
    }

    /** The new form with the period's field alone, on a sequence built as the screen builds it (its services aside). */
    private function form(Sequence $sequence): \Symfony\Component\Form\FormInterface
    {
        $crud = (new \ReflectionClass(SequenceCrudController::class))->newInstanceWithoutConstructor();
        $fields = array_filter(iterator_to_array($crud->configureFields(FieldDescriptor::PAGE_NEW), false), fn ($field) => 'periodValue' === $field->getAsDto()->getProperty());
        self::assertCount(1, $fields);

        return (new FieldFormBuilder(static::getContainer()->get('form.factory'), static::getContainer()->get('doctrine')))
            ->createForm($sequence, $fields, FieldDescriptor::PAGE_NEW, ['csrf_protection' => false]);
    }

    private static function sequence(): Sequence
    {
        return (new \ReflectionClass(Sequence::class))->newInstanceWithoutConstructor();
    }

    public function testANewSequenceShowsNoPeriod(): void
    {
        $sequence = self::sequence();
        self::assertNull($sequence->getPeriodValue(), 'none recorded by default');

        $html = static::getContainer()->get('twig')
            ->createTemplate("{% form_theme form with ['@Admin/crud/form_theme.html.twig'] %}{{ form_widget(form.periodValue) }}")
            ->render(['form' => $this->form($sequence)->createView()]);

        self::assertMatchesRegularExpression('/<select[^>]*>\s*<option value="">/', $html, 'an empty option first');
        self::assertDoesNotMatchRegularExpression('/<option[^>]*selected/', $html, 'none chosen');
    }

    public function testSentEmptyThePeriodStaysEmpty(): void
    {
        $form = $this->form($sequence = self::sequence());
        $form->submit(['periodValue' => ['choice' => '']]);

        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        self::assertNull($sequence->getPeriodValue());
    }

    public function testAPeriodChosenIsRecorded(): void
    {
        $form = $this->form($sequence = self::sequence());
        $form->submit(['periodValue' => ['choice' => 'p3']]);

        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        self::assertSame('p3', $sequence->getPeriodValue());
    }
}
