<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\AbstractComponent;
use GlueApps\Components\Event\BeforeInsertionEvent;
use GlueApps\Components\Event\AfterInsertionEvent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class BeforeInsertionEventTest extends BaseTestCase
{
    public function testWhenAddsAChildIsTriggeredAnBeforeInsertionEvent()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->on(AbstractComponent::EVENT_BEFORE_INSERTION, function (BeforeInsertionEvent $event) use (&$executed, $parent, $child) {
            $executed = true;
            $this->assertEquals($parent, $event->getSource());
            $this->assertEquals($parent, $event->getParent());
            $this->assertEquals($child, $event->getChild());
            $this->assertFalse($parent->hasChild($child));
        });

        $parent->addChild($child); // Act

        $this->assertTrue($executed);
        $this->assertTrue($parent->hasChild($child));
    }

    public function testAcrossTheBeforeInsertionEventIsPossibleCancelTheInsertion()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->on(AbstractComponent::EVENT_BEFORE_INSERTION, function (BeforeInsertionEvent $event) use (&$executed) {
            $executed = true;
            $event->cancel();
        });

        $parent->addChild($child); // Act

        $this->assertTrue($executed);
        $this->assertFalse($parent->hasChild($child));
        $this->assertNull($child->getParent());
    }

    public function createMockForInsertionEvents()
    {
        $this->parent = $this->getMockBuilder(AbstractComponent::class)
            ->setMethods(['dispatch'])
            ->getMockForAbstractClass();
        $this->parent->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->equalTo(AbstractComponent::EVENT_BEFORE_INSERTION),
                    $this->isInstanceOf(BeforeInsertionEvent::class)
                ],
                [
                    $this->equalTo(AbstractComponent::EVENT_AFTER_INSERTION),
                    $this->isInstanceOf(AfterInsertionEvent::class)
                ]
            );
    }

    public function testTheBeforeInsertionEventAndTheAfterInsertionEventAreTriggeredAcrossTheDispatchMethod1()
    {
        $this->createMockForInsertionEvents();
        $child = $this->createComponent();

        $this->parent->addChild($child); // Act
    }

    public function testTheBeforeInsertionEventAndTheAfterInsertionEventAreTriggeredAcrossTheDispatchMethod2()
    {
        $this->createMockForInsertionEvents();
        $child = $this->createComponent();

        $child->setParent($this->parent); // Act
    }
}
