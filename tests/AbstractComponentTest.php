<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\AbstractComponent;
use GlueApps\Components\AbstractParentComponent;
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
}
