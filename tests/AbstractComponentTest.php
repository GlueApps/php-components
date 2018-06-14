<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\Events;
use GlueApps\Components\AbstractComponent;
use GlueApps\Components\Event\TreeEvent;
use GlueApps\Components\Event\BeforeInsertionEvent;
use GlueApps\Components\Event\AfterInsertionEvent;
use GlueApps\Components\Event\BeforeDeletionEvent;
use GlueApps\Components\Event\AfterDeletionEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class AbstractComponentTest extends BaseTestCase
{
    public function testTheUniqueIdentifierIsARandomWord()
    {
        $this->assertRegExp('/\w{13}$/', $this->component->getUId());
    }

    public function testSetUIdSetsTheUniqueIdentifier()
    {
        $uid = uniqid();

        $this->component->setUId($uid);

        $this->assertEquals($uid, $this->component->getUId());
    }

    public function testGenerateUIdDefinesTheGenerationMethodOfTheUniqueIdentifier()
    {
        $uid = uniqid();

        $component = new class($uid) extends AbstractComponent {

            public function __construct($newUId)
            {
                $this->newUId = $newUId;

                parent::__construct(); // Act
            }

            protected function generateUId(): void
            {
                $this->uid = $this->newUId;
            }
        };

        $this->assertEquals($uid, $component->getUId());
    }

    public function testByDefaultTheParentIsNull()
    {
        $this->assertNull($this->component->getParent());
    }

    public function testSetParentAssignsTheParent()
    {
        $parent = $this->createComponent();

        $this->component->setParent($parent);

        $this->assertEquals($parent, $this->component->getParent());
    }

    public function testSetParentRegistersTheComponentAsChildOfTheParent()
    {
        $parent = $this->createComponent();

        $this->component->setParent($parent);

        $this->assertTrue($parent->hasChild($this->component));
    }

    public function testSetParentDoesNotRegistersTheComponentAsChildOfTheParentWhenSecondArgumentIsFalse()
    {
        $parent = $this->createComponent();

        $this->component->setParent($parent, false);

        $this->assertFalse($parent->hasChild($this->component));
    }

    public function testSetParentInvokeToAddChildOnParent()
    {
        $parent = $this->getMockBuilder(AbstractComponent::class)
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
        $parent = $this->getMockBuilder(AbstractComponent::class)
            ->setMethods(['addChild'])
            ->getMockForAbstractClass();
        $parent->expects($this->exactly(0))
            ->method('addChild');

        $this->component->setParent($parent, false);
    }

    public function testSetParentNotAssignsNothingIfResultOfAddChildOnTheParentIsFalse()
    {
        $parent = $this->createMock(AbstractComponent::class);
        $parent->method('addChild')->willReturn(false);

        $this->component->setParent($parent);

        $this->assertNull($this->component->getParent());
    }

    public function testWhenSetsParentAsNullIsInvokedDropChildInTheOld()
    {
        $parent = $this->getMockBuilder(AbstractComponent::class)
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
        $parent = $this->createComponent();
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
        $event = $this->createMock(TreeEvent::class);

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

    public function testByDefaultHasNotChilds()
    {
        $this->assertEmpty($this->component->children());
    }

    public function addThreeChilds()
    {
        $this->child1 = $this->createComponent();
        $this->child2 = $this->createComponent();
        $this->child3 = $this->createComponent();

        $this->component->addChild($this->child1);
        $this->component->addChild($this->child2);
        $this->component->addChild($this->child3);
    }

    public function testAddChildInsertAChildInTheComponent()
    {
        $this->addThreeChilds();

        $expected = [
            $this->child1->getUId() => $this->child1,
            $this->child2->getUId() => $this->child2,
            $this->child3->getUId() => $this->child3,
        ];

        $this->assertEquals($expected, $this->component->children());
    }

    public function testAddChildRegistersTheComponentAsParentOfTheChild()
    {
        $this->addThreeChilds();

        $this->assertEquals($this->component, $this->child1->getParent());
    }

    public function testAddChildDoesNotRegisterTheComponentAsParentOfTheChildWhenSecondArgumentIsFalse()
    {
        $child = $this->createComponent();

        $this->component->addChild($child, false);

        $this->assertNotEquals($this->component, $child->getParent());
    }

    public function testAddChildsMayInsertSeveralChildsAtOnce()
    {
        $child1 = $this->createMock(AbstractComponent::class);
        $child2 = $this->createMock(AbstractComponent::class);
        $child3 = $this->createMock(AbstractComponent::class);

        $component = $this->getMockBuilder(AbstractComponent::class)
            ->setMethods(['addChild'])
            ->getMockForAbstractClass();
        $component->expects($this->exactly(3))
            ->method('addChild')
            ->withConsecutive(
                [$this->equalTo($child1)],
                [$this->equalTo($child2)],
                [$this->equalTo($child3)]
            );

        $component->addChilds($child1, $child2, $child3);
    }

    public function testAddChildsIgnoreValuesDifferentToComponents()
    {
        $child1 = $this->createMock(AbstractComponent::class);
        $child2 = $this->createMock(AbstractComponent::class);

        $component = $this->getMockBuilder(AbstractComponent::class)
            ->setMethods(['addChild'])
            ->getMockForAbstractClass();
        $component->expects($this->exactly(2))
            ->method('addChild')
            ->withConsecutive(
                [$this->equalTo($child1)],
                [$this->equalTo($child2)]
            );

        $component->addChilds($child1, uniqid(), frand(), true, $child2);
    }

    public function testGetChildReturnsTheSearchedChildWhenItExists()
    {
        $this->addThreeChilds();

        $this->assertEquals(
            $this->child2, $this->component->getChild($this->child2->getUId())
        );
    }

    public function testGetChildReturnsNullWhenTheSearchedChildNotExists()
    {
        $uid = uniqid();

        $this->assertNull($this->component->getChild($uid));
    }

    public function testDropChildReturnsTrueOnSuccess()
    {
        $this->addThreeChilds();

        $this->assertTrue($this->component->dropChild($this->child1));
        $this->assertTrue($this->component->dropChild($this->child2->getUId()));
    }

    public function testDropChildUsingTheChildUId()
    {
        $this->addThreeChilds();

        $this->component->dropChild($this->child2->getUId()); // Act

        $expected = [
            $this->child1->getUId() => $this->child1,
            $this->child3->getUId() => $this->child3,
        ];

        $this->assertEquals($expected, $this->component->children());
    }

    public function testDropChildUsingTheChildObject()
    {
        $this->addThreeChilds();

        $this->component->dropChild($this->child2); // Act

        $expected = [
            $this->child1->getUId() => $this->child1,
            $this->child3->getUId() => $this->child3,
        ];

        $this->assertEquals($expected, $this->component->children());
    }

    public function testDropChildReturnsFalseWhenTheChildNotFound()
    {
        $child = $this->createMock(AbstractComponent::class);

        $this->assertFalse($this->component->dropChild(uniqid('child')));
        $this->assertFalse($this->component->dropChild($child));
    }

    public function testHasChildReturnsTrueWhenExistsOneChildWithTheSearchedUId()
    {
        $this->addThreeChilds();
        $uid = $this->child1->getUId();

        $this->assertTrue($this->component->hasChild($uid));
    }

    public function testHasChildReturnsFalseWhenNotExistsOneChildWithTheSearchedUId()
    {
        $child = $this->createComponent();
        $uid = $child->getUId();

        $this->assertFalse($this->component->hasChild($uid));
    }

    public function testHasChildReturnsFalseWhenTheSearchUIdIsNotStringOrInstanceOfComponent()
    {
        $this->assertFalse($this->component->hasChild(frand()));
        $this->assertFalse($this->component->hasChild([]));
        $this->assertFalse($this->component->hasChild(true));
    }

    public function testHasChildReturnsTrueWhenTheSearchedChildAlreadyIsRegistered()
    {
        $this->addThreeChilds();

        $this->assertTrue($this->component->hasChild($this->child1));
    }

    public function testTraverseForTree1()
    {
        $this->createTree1();

        $iterator = $this->root->traverse();

        $this->assertEquals($this->component1, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component2, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component3, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component4, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component5, $iterator->current());
        $iterator->next();
        $this->assertNull($iterator->current());
    }

    public function testTraverseForTree2()
    {
        $this->createTree2();

        $iterator = $this->root->traverse();

        $this->assertEquals($this->component1, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component3, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component5, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component2, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component4, $iterator->current());
        $iterator->next();
        $this->assertNull($iterator->current());
    }

    public function testGetComponentByUIdDoesSearchTheComponentForAllTheTree()
    {
        $this->createTree1();

        $this->assertEquals(
            $this->component4, $this->root->getComponentByUId($this->component4->getUId())
        );
        $this->assertEquals(
            $this->component5, $this->root->getComponentByUId($this->component5->getUId())
        );
    }

    public function testGetComponentByUIdReturnsNullWhenTheComponentIsNotFound()
    {
        $uid = uniqid();
        $this->assertNull($this->component->getComponentByUId($uid));
    }

    public function testWhenAddsAChildIsTriggeredAnBeforeInsertionEvent()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->on(Events::BEFORE_INSERTION, function (BeforeInsertionEvent $event) use (&$executed, $parent, $child) {
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

        $parent->on(Events::BEFORE_INSERTION, function (BeforeInsertionEvent $event) use (&$executed) {
            $executed = true;
            $event->cancel();
        });

        $parent->addChild($child); // Act

        $this->assertTrue($executed);
        $this->assertFalse($parent->hasChild($child));
        $this->assertNull($child->getParent());
    }

    public function testWhenABeforeInsertionEventCancelTheInsertionTheAfterInsertionEventIsNotTriggered()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executedBefore = false;
        $executedAfter = false;

        $parent->on(Events::BEFORE_INSERTION, function (BeforeInsertionEvent $event) use (&$executedBefore) {
            $executedBefore = true;
            $event->cancel();
        });

        $parent->on(Events::AFTER_INSERTION, function (AfterInsertionEvent $event) use (&$executedAfter) {
            $executedAfter = true;
        });

        $parent->addChild($child); // Act

        $this->assertTrue($executedBefore);
        $this->assertFalse($executedAfter);
    }

    public function testWhenAddsAChildIsTriggeredAnAfterInsertionEvent()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->on(Events::AFTER_INSERTION, function (AfterInsertionEvent $event) use (&$executed, $parent, $child) {
            $executed = true;
            $this->assertEquals($parent, $event->getSource());
            $this->assertEquals($parent, $event->getParent());
            $this->assertEquals($child, $event->getChild());
            $this->assertTrue($parent->hasChild($child));
        });

        $parent->addChild($child); // Act

        $this->assertTrue($executed);
    }

    public function testDropChildTriggersAnBeforeDeletionEvent1()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->addChild($child);
        $parent->on(Events::BEFORE_DELETION, function (BeforeDeletionEvent $event) use (&$executed, $parent, $child) {
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
        $parent->on(Events::BEFORE_DELETION, function (BeforeDeletionEvent $event) use (&$executed, $parent, $child) {
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
        $parent->on(Events::BEFORE_DELETION, function (BeforeDeletionEvent $event) use (&$executed) {
            $executed = true;
            $event->cancel();
        });

        $child->setParent(null); // Act

        $this->assertTrue($executed);
        $this->assertTrue($parent->hasChild($child));
    }

    public function testWhenABeforeDeletionEventCancelTheDeletionTheAfterDeletionEventIsNotTriggered()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executedBefore = false;
        $executedAfter = false;

        $parent->addChild($child);

        $parent->on(Events::BEFORE_DELETION, function (BeforeDeletionEvent $event) use (&$executedBefore) {
            $executedBefore = true;
            $event->cancel();
        });

        $parent->on(Events::AFTER_DELETION, function (AfterDeletionEvent $event) use (&$executedAfter) {
            $executedAfter = true;
        });

        $parent->dropChild($child); // Act

        $this->assertTrue($executedBefore);
        $this->assertFalse($executedAfter);
    }

    public function testDropChildTriggersAnAfterDeletionEvent1()
    {
        $this->addThreeChilds();
        $executed = false;

        $this->component->on(Events::AFTER_DELETION, function (AfterDeletionEvent $event) use (&$executed) {
            $executed = true;
            $this->assertEquals($this->component, $event->getSource());
            $this->assertEquals($this->component, $event->getParent());
            $this->assertEquals($this->child1, $event->getChild());
        });

        $this->component->dropChild($this->child1); // Act

        $this->assertTrue($executed);
    }

    public function testDropChildTriggersAnAfterDeletionEvent2()
    {
        $this->addThreeChilds();
        $executed = false;

        $this->component->on(Events::AFTER_DELETION, function (AfterDeletionEvent $event) use (&$executed) {
            $executed = true;
            $this->assertEquals($this->component, $event->getSource());
            $this->assertEquals($this->component, $event->getParent());
            $this->assertEquals($this->child1, $event->getChild());
        });

        $this->component->dropChild($this->child1->getUId()); // Act

        $this->assertTrue($executed);
    }

    public function testDropChildReturnsFalseIfTheBeforeDeletionEventIsCancelled()
    {
        $this->addThreeChilds();

        $this->component->on(Events::BEFORE_DELETION, function (BeforeDeletionEvent $event) {
            $event->cancel();
        });

        $this->assertFalse($this->component->dropChild($this->child1));
    }

    public function testDropChildNotTriggersAnAfterDeletionEventIfTheChildNotFound1()
    {
        $executed = false;
        $child = $this->createMock(AbstractComponent::class);
        $this->component->on(Events::AFTER_DELETION, function (AfterDeletionEvent $event) use (&$executed) {
            $executed = true;
        });

        $this->component->dropChild($child); // Act

        $this->assertFalse($executed);
    }

    public function testDropChildNotTriggersAnAfterDeletionEventIfTheChildNotFound2()
    {
        $executed = false;
        $this->component->on(Events::AFTER_DELETION, function (AfterDeletionEvent $event) use (&$executed) {
            $executed = true;
        });

        $this->component->dropChild(uniqid('child')); // Act

        $this->assertFalse($executed);
    }

    public function testOrderInEventBubbling()
    {
        $this->createTree2();
        $this->executed3 = false;
        $this->executed1 = false;
        $this->executedRoot = false;

        $this->component3->on(Events::AFTER_DELETION, function (AfterDeletionEvent $event) {
            $this->assertEquals($this->component3, $event->getSource());
            $this->assertEquals($this->component3, $event->getParent());
            $this->assertEquals($this->component5, $event->getChild());
            $this->executed3 = true;
            $event->last = 3;
        });

        $this->component1->on(Events::AFTER_DELETION, function (AfterDeletionEvent $event) {
            $this->assertEquals(3, $event->last);
            $this->assertEquals($this->component3, $event->getSource());
            $this->assertEquals($this->component3, $event->getParent());
            $this->assertEquals($this->component5, $event->getChild());
            $this->executed1 = true;
            $event->last = 1;
        });

        $this->root->on(Events::AFTER_DELETION, function (AfterDeletionEvent $event) {
            $this->assertEquals(1, $event->last);
            $this->assertEquals($this->component3, $event->getSource());
            $this->assertEquals($this->component3, $event->getParent());
            $this->assertEquals($this->component5, $event->getChild());
            $this->executedRoot = true;
        });

        $this->component3->dropChild($this->component5); // Act

        $this->assertTrue($this->executed3);
        $this->assertTrue($this->executed1);
        $this->assertTrue($this->executedRoot);
    }
}
