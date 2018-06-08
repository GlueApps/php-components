<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\Events;
use GlueApps\Components\AbstractComponent;
use GlueApps\Components\AbstractParentComponent;
use GlueApps\Components\Event\BeforeInsertionEvent;
use GlueApps\Components\Event\AfterInsertionEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class AbstractComponentTest extends BaseTestCase
{
    public function setUp()
    {
        $this->component = $this->createComponent();
    }

    public function testTheUniqueIdentifierEndsWithARandomWord()
    {
        $component = new MyDummyComponent;

        $this->assertRegExp('/\w{13}$/', $component->getUId());
    }

    public function testTheUniqueIdentifierStartsWithTheClassNameInLowerCase()
    {
        $component = new MyDummyComponent;

        $this->assertStringStartsWith('mydummycomponent', $component->getUId());
    }

    public function testTheUniqueIdentifierStartsWithAnonymouseWhenTheClassIsAnonymous()
    {
        $component = new class extends AbstractComponent {};

        $this->assertStringStartsWith('anonymous', $component->getUId());
    }

    public function testByDefaultTheParentIsNull()
    {
        $this->assertNull($this->component->getParent());
    }

    public function testSetParentAssignsTheParent()
    {
        $parent = $this->createParentComponent();

        $this->component->setParent($parent);

        $this->assertEquals($parent, $this->component->getParent());
    }

    public function testSetParentRegistersTheComponentAsChildOfTheParent()
    {
        $parent = $this->createParentComponent();

        $this->component->setParent($parent);

        $this->assertTrue($parent->hasChild($this->component));
    }

    public function testSetParentDoesNotRegistersTheComponentAsChildOfTheParentWhenSecondArgumentIsFalse()
    {
        $parent = $this->createParentComponent();

        $this->component->setParent($parent, false);

        $this->assertFalse($parent->hasChild($this->component));
    }

    public function testGetDispatcherReturnsTheSymfonyEventDispatcher()
    {
        $this->assertInstanceOf(EventDispatcher::class, $this->component->getDispatcher());
    }

    public function testSetDispatcherAssignsTheEventDispatcer()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->component->setDispatcher($dispatcher);

        $this->assertEquals($dispatcher, $this->component->getDispatcher());
    }

    public function testDispatchInvokeToDispatchMethodOnTheEventDispatcher()
    {
        $eventName = uniqid();
        $event = $this->createMock(Event::class);

        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->setMethods(['dispatch'])
            ->getMockForAbstractClass();
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo($eventName),
                $this->equalTo($event)
            );

        $this->component->setDispatcher($dispatcher);
        $this->component->dispatch($eventName, $event); // Act
    }

    public function testParentsForTree1()
    {
        $this->createTree1();

        $parents = $this->component5->parents();

        $expectedKeys = [
            $this->component4->getUId(),
            $this->component3->getUId(),
            $this->component2->getUId(),
            $this->component1->getUId(),
            $this->root->getUId(),
        ];

        $expectedValues = [
            $this->component4,
            $this->component3,
            $this->component2,
            $this->component1,
            $this->root,
        ];

        $this->assertEquals($expectedKeys, array_keys($parents));
        $this->assertEquals($expectedValues, array_values($parents));
    }

    public function testParentsForTree2()
    {
        $this->createTree2();

        $parents = $this->component5->parents();

        $expectedKeys = [
            $this->component3->getUId(),
            $this->component1->getUId(),
            $this->root->getUId(),
        ];

        $expectedValues = [
            $this->component3,
            $this->component1,
            $this->root,
        ];

        $this->assertEquals($expectedKeys, array_keys($parents));
        $this->assertEquals($expectedValues, array_values($parents));
    }

    public function testGetRootReturnNullWhenParentIsNull()
    {
        $this->assertNull($this->component->getRoot());
    }

    public function testGetRootReturnTheRootComponentOfTheTree()
    {
        $this->createTree1();

        $this->assertEquals($this->root, $this->component5->getRoot());
        $this->assertEquals($this->root, $this->component1->getRoot());
    }

    public function testOnRegistersListenersInTheDispatcher()
    {
        $eventName = uniqid('eventName');
        $callback = function () {};

        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->setMethods(['addListener'])
            ->getMockForAbstractClass();
        $dispatcher->expects($this->once())
            ->method('addListener')
            ->with(
                $this->equalTo($eventName),
                $this->equalTo($callback)
            );

        $this->component->setDispatcher($dispatcher);

        $this->component->on($eventName, $callback); // Act
    }

    public function testWhenAComponentSetsHisParentOnThisIsTriggeredAnBeforeInsertionEvent()
    {
        $parent = $this->createParentComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->on(Events::BEFORE_INSERTION, function (BeforeInsertionEvent $event) use (&$executed, $parent, $child) {
            $executed = true;
            $this->assertEquals($parent, $event->getParent());
            $this->assertEquals($child, $event->getChild());
            $this->assertFalse($parent->hasChild($child));
        });

        $child->setParent($parent); // Act

        $this->assertTrue($executed);
        $this->assertTrue($parent->hasChild($child));
    }

    public function testAcrossTheBeforeInsertionEventIsPossibleCancelTheInsertion()
    {
        $parent = $this->createParentComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->on(Events::BEFORE_INSERTION, function (BeforeInsertionEvent $event) use (&$executed) {
            $executed = true;
            $event->cancel();
        });

        $child->setParent($parent); // Act

        $this->assertTrue($executed);
        $this->assertFalse($parent->hasChild($child));
        $this->assertNull($child->getParent());
    }

    public function testWhenABeforeInsertionEventCancelTheInsertionTheAfterInsertionEventIsNotTriggered()
    {
        $parent = $this->createParentComponent();
        $child = $this->createComponent();
        $executedBefore = false;
        $executedAfter = false;

        $parent->on(Events::BEFORE_INSERTION, function (BeforeInsertionEvent $event) use (&$executedBefore) {
            $executedBefore = true;
            $event->cancel();
        });

        $parent->on(Events::AFTER_INSERTION, function (AfterInsertionEvent $event) use (&$executed) {
            $executedAfter = true;
        });

        $child->setParent($parent); // Act

        $this->assertTrue($executedBefore);
        $this->assertFalse($executedAfter);
    }

    public function testWhenAComponentSetsHisParentOnThisIsTriggeredAnAfterInsertionEvent()
    {
        $parent = $this->createParentComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->on(Events::AFTER_INSERTION, function (AfterInsertionEvent $event) use (&$executed, $parent, $child) {
            $executed = true;
            $this->assertEquals($parent, $event->getParent());
            $this->assertEquals($child, $event->getChild());
            $this->assertTrue($parent->hasChild($child));
        });

        $child->setParent($parent); // Act

        $this->assertTrue($executed);
    }
}
