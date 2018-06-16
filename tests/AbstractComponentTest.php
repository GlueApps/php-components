<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\AbstractComponent;
use GlueApps\Components\Event\Event;
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
    ///////////////////////
    // Unique identifier //
    ///////////////////////

    public function testTheUniqueIdentifierIsARandomWord()
    {
        $this->assertRegExp('/^\w{13}$/', $this->component->getUId());
    }

    public function testSetUIdAssignsTheUniqueIdentifier()
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

    ////////////
    // Parent //
    ////////////

    public function testTheParentIsNullByDefault()
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

    public function testWhenSetsTheParentAsNullIsInvokedDropChildInTheOldParent()
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

    public function testGetRootReturnsNullWhenParentIsNull()
    {
        $this->assertNull($this->component->getRoot());
    }

    public function testGetRootReturnsTheRootComponentOfTheTree()
    {
        $this->createTree1();

        $this->assertEquals($this->root, $this->component5->getRoot());
        $this->assertEquals($this->root, $this->component1->getRoot());
    }

    //////////////
    // Children //
    //////////////

    public function testByDefaultHasNotChilds()
    {
        $this->assertEmpty($this->component->children());
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
            $this->child2,
            $this->component->getChild($this->child2->getUId())
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
            $this->component4,
            $this->root->getComponentByUId($this->component4->getUId())
        );
        $this->assertEquals(
            $this->component5,
            $this->root->getComponentByUId($this->component5->getUId())
        );
    }

    public function testGetComponentByUIdReturnsNullWhenTheComponentIsNotFound()
    {
        $uid = uniqid();
        $this->assertNull($this->component->getComponentByUId($uid));
    }

    public function testDropChildReturnsFalseIfTheBeforeDeletionEventIsCancelled()
    {
        $this->addThreeChilds();

        $this->component->on(AbstractComponent::EVENT_BEFORE_DELETION, function (BeforeDeletionEvent $event) {
            $event->cancel();
        });

        $this->assertFalse($this->component->dropChild($this->child1));
    }

    ////////////////
    // Dispatcher //
    ////////////////

    public function testByDefaultGetDispatcherReturnsASymfonyEventDispatcher()
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

    public function testDispatchReturnsTheEventObject()
    {
        $event = $this->createMock(Event::class);
        $eventName = uniqid();

        $this->assertEquals($event, $this->component->dispatch($eventName, $event));
    }

    public function testOnRegistersListenersInTheDispatcher()
    {
        $eventName = uniqid('eventName');
        $callback = function () {
        };

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

    //////////////////////////
    // BeforeInsertionEvent //
    //////////////////////////

    public function testWhenAddsAChildIsTriggeredAnBeforeInsertionEvent()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->on(AbstractComponent::EVENT_BEFORE_INSERTION, function (BeforeInsertionEvent $event) use (&$executed, $parent, $child) {
            $executed = true;
            $this->assertEquals($parent, $event->getTarget());
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

    /////////////////////////
    // AfterInsertionEvent //
    /////////////////////////

    public function testWhenAddsAChildIsTriggeredAnAfterInsertionEvent()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->on(AbstractComponent::EVENT_AFTER_INSERTION, function (AfterInsertionEvent $event) use (&$executed, $parent, $child) {
            $executed = true;
            $this->assertEquals($parent, $event->getTarget());
            $this->assertEquals($parent, $event->getParent());
            $this->assertEquals($child, $event->getChild());
            $this->assertTrue($parent->hasChild($child));
        });

        $parent->addChild($child); // Act

        $this->assertTrue($executed);
    }

    public function testWhenABeforeInsertionEventCancelTheInsertionTheAfterInsertionEventIsNotTriggered()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executedBefore = false;
        $executedAfter = false;

        $parent->on(AbstractComponent::EVENT_BEFORE_INSERTION, function (BeforeInsertionEvent $event) use (&$executedBefore) {
            $executedBefore = true;
            $event->cancel();
        });

        $parent->on(AbstractComponent::EVENT_AFTER_INSERTION, function (AfterInsertionEvent $event) use (&$executedAfter) {
            $executedAfter = true;
        });

        $parent->addChild($child); // Act

        $this->assertTrue($executedBefore);
        $this->assertFalse($executedAfter);
    }

    /////////////////////////
    // BeforeDeletionEvent //
    /////////////////////////

    public function testDropChildTriggersAnBeforeDeletionEvent1()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->addChild($child);
        $parent->on(AbstractComponent::EVENT_BEFORE_DELETION, function (BeforeDeletionEvent $event) use (&$executed, $parent, $child) {
            $executed = true;
            $this->assertEquals($parent, $event->getTarget());
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
            $this->assertEquals($parent, $event->getTarget());
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

    ////////////////////////
    // AfterDeletionEvent //
    ////////////////////////

    public function testDropChildTriggersAnAfterDeletionEvent1()
    {
        $this->addThreeChilds();
        $executed = false;

        $this->component->on(AbstractComponent::EVENT_AFTER_DELETION, function (AfterDeletionEvent $event) use (&$executed) {
            $executed = true;
            $this->assertEquals($this->component, $event->getTarget());
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

        $this->component->on(AbstractComponent::EVENT_AFTER_DELETION, function (AfterDeletionEvent $event) use (&$executed) {
            $executed = true;
            $this->assertEquals($this->component, $event->getTarget());
            $this->assertEquals($this->component, $event->getParent());
            $this->assertEquals($this->child1, $event->getChild());
        });

        $this->component->dropChild($this->child1->getUId()); // Act

        $this->assertTrue($executed);
    }

    public function testWhenABeforeDeletionEventCancelTheDeletionTheAfterDeletionEventIsNotTriggered()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executedBefore = false;
        $executedAfter = false;

        $parent->addChild($child);

        $parent->on(AbstractComponent::EVENT_BEFORE_DELETION, function (BeforeDeletionEvent $event) use (&$executedBefore) {
            $executedBefore = true;
            $event->cancel();
        });

        $parent->on(AbstractComponent::EVENT_AFTER_DELETION, function (AfterDeletionEvent $event) use (&$executedAfter) {
            $executedAfter = true;
        });

        $parent->dropChild($child); // Act

        $this->assertTrue($executedBefore);
        $this->assertFalse($executedAfter);
    }

    public function testDropChildNotTriggersAnAfterDeletionEventIfTheChildNotFound1()
    {
        $executed = false;
        $child = $this->createMock(AbstractComponent::class);
        $this->component->on(AbstractComponent::EVENT_AFTER_DELETION, function (AfterDeletionEvent $event) use (&$executed) {
            $executed = true;
        });

        $this->component->dropChild($child); // Act

        $this->assertFalse($executed);
    }

    public function testDropChildNotTriggersAnAfterDeletionEventIfTheChildNotFound2()
    {
        $executed = false;
        $this->component->on(AbstractComponent::EVENT_AFTER_DELETION, function (AfterDeletionEvent $event) use (&$executed) {
            $executed = true;
        });

        $this->component->dropChild(uniqid('child')); // Act

        $this->assertFalse($executed);
    }

    //////////////
    // Bubbling //
    //////////////

    public function testEventOrderInBubbling()
    {
        $this->createTree2();

        $this->executed3 = false;
        $this->executed1 = false;
        $this->executedRoot = false;
        $eventName = uniqid();

        $this->component3->on($eventName, function ($event) {
            $this->executed3 = true;
            $this->time3 = microtime(true);
        });

        $this->component1->on($eventName, function ($event) {
            $this->executed1 = true;
            $this->time1 = microtime(true);
        });

        $this->root->on($eventName, function ($event) {
            $this->executedRoot = true;
            $this->timeRoot = microtime(true);
        });

        $this->component3->dispatch($eventName); // Act

        $this->assertTrue($this->executed3);
        $this->assertTrue($this->executed1);
        $this->assertTrue($this->executedRoot);

        $this->assertLessThan($this->timeRoot, $this->time1);
        $this->assertLessThan($this->time1, $this->time3);
    }

    public function testTheEventBubblingMayBeStopped()
    {
        $this->createTree2();

        $this->executed3 = false;
        $this->executed1 = false;
        $this->executedRoot = false;
        $eventName = uniqid();

        $this->component3->on($eventName, function ($event) {
            $this->executed3 = true;
        });

        $this->component1->on($eventName, function ($event) {
            $this->executed1 = true;
            $event->stopPropagation();
        });

        $this->root->on($eventName, function ($event) {
            $this->executedRoot = true;
        });

        $this->component3->dispatch($eventName); // Act

        $this->assertTrue($this->executed3);
        $this->assertTrue($this->executed1);
        $this->assertFalse($this->executedRoot);
    }
}
