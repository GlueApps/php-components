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

    public function testSetParentInvokeToAddChildOnParent()
    {
        $parent = $this->getMockBuilder(AbstractParentComponent::class)
            ->setMethods(['addChild'])
            ->getMockForAbstractClass();
        $parent->expects($this->once())
            ->method('addChild')
            ->with(
                $this->equalTo($this->component),
                $this->equalTo(false)
            );

        $this->component->setParent($parent);
    }

    public function testSetParentNotInvokesToAddChildOnParentIfSecondArgumentIsFalse()
    {
        $parent = $this->getMockBuilder(AbstractParentComponent::class)
            ->setMethods(['addChild'])
            ->getMockForAbstractClass();
        $parent->expects($this->exactly(0))
            ->method('addChild');

        $this->component->setParent($parent, false);
    }

    public function testSetParentNotAssignsNothingIfResultOfAddChildOnTheParentIsFalse()
    {
        $parent = $this->createMock(AbstractParentComponent::class);
        $parent->method('addChild')->willReturn(false);

        $this->component->setParent($parent);

        $this->assertNull($this->component->getParent());
    }

    public function testWhenSetsParentAsNullIsInvokedDropChildInTheOld()
    {
        $parent = $this->getMockBuilder(AbstractParentComponent::class)
            ->setMethods(['dropChild'])
            ->getMockForAbstractClass();
        $parent->expects($this->once())
            ->method('dropChild')
            ->with(
                $this->equalTo($this->component)
            );
        $parent->addChild($this->component);

        $this->component->setParent(null); // Act
    }

    public function testWhenSetsParentAsNullTheOldParentRemovesTheChild()
    {
        $parent = $this->createParentComponent();
        $parent->addChild($this->component);

        $this->component->setParent(null); // Act

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
}
