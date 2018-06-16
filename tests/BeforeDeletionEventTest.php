<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\AbstractComponent;
use GlueApps\Components\Event\BeforeDeletionEvent;
use GlueApps\Components\Event\AfterDeletionEvent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class BeforeDeletionEventTest extends BaseTestCase
{
    public function testDropChildTriggersAnBeforeDeletionEvent1()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->addChild($child);
        $parent->on(AbstractComponent::EVENT_BEFORE_DELETION, function (BeforeDeletionEvent $event) use (&$executed, $parent, $child) {
            $executed = true;
            $this->assertEquals($parent, $event->getSource());
            $this->assertEquals($parent, $event->getParent());
            $this->assertEquals($child, $event->getChild());
            $this->assertTrue($parent->hasChild($child));
        });

        $parent->dropChild($child); // Act

        $this->assertTrue($executed);
    }

    public function testDropChildTriggersAnBeforeDeletionEvent2()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->addChild($child);
        $parent->on(AbstractComponent::EVENT_BEFORE_DELETION, function (BeforeDeletionEvent $event) use (&$executed, $parent, $child) {
            $executed = true;
            $this->assertEquals($parent, $event->getSource());
            $this->assertEquals($parent, $event->getParent());
            $this->assertEquals($child, $event->getChild());
            $this->assertTrue($parent->hasChild($child));
        });

        $child->setParent(null); // Act

        $this->assertTrue($executed);
    }

    public function testAcrossTheBeforeDeletionEventIsPossibleCancelTheDeletion()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->addChild($child);
        $parent->on(AbstractComponent::EVENT_BEFORE_DELETION, function (BeforeDeletionEvent $event) use (&$executed) {
            $executed = true;
            $event->cancel();
        });

        $child->setParent(null); // Act

        $this->assertTrue($executed);
        $this->assertTrue($parent->hasChild($child));
    }

    public function createMockForDeletionEvents()
    {
        $this->parent = $this->getMockBuilder(AbstractComponent::class)
            ->setMethods(['dispatch'])
            ->getMockForAbstractClass();
        $this->parent->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->equalTo(AbstractComponent::EVENT_BEFORE_DELETION),
                    $this->isInstanceOf(BeforeDeletionEvent::class)
                ],
                [
                    $this->equalTo(AbstractComponent::EVENT_AFTER_DELETION),
                    $this->isInstanceOf(AfterDeletionEvent::class)
                ]
            )
        ;

        $this->child = $this->createComponent();

        // Adds the child to the parent without invoke the insertion events.
        // Emphasizing that too much knows the SUT.
        (function ($child) {
            $this->children = [$child->getUId() => $child];
        })->call($this->parent, $this->child);

        $this->child->setParent($this->parent, false);
    }

    public function testTheBeforeDeletionEventAndTheAfterDeletionEventAreTriggeredAcrossTheDispatchMethod1()
    {
        $this->createMockForDeletionEvents();

        $this->parent->dropChild($this->child);
    }

    public function testTheBeforeDeletionEventAndTheAfterDeletionEventAreTriggeredAcrossTheDispatchMethod2()
    {
        $this->createMockForDeletionEvents();

        $this->child->setParent(null);
    }
}
