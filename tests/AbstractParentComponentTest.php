<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\Events;
use GlueApps\Components\AbstractComponent;
use GlueApps\Components\AbstractParentComponent;
use GlueApps\Components\Event\BeforeInsertionEvent;
use GlueApps\Components\Event\AfterInsertionEvent;
use GlueApps\Components\Event\BeforeDeletionEvent;
use GlueApps\Components\Event\AfterDeletionEvent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class AbstractParentComponentTest extends BaseTestCase
{
    public function setUp()
    {
        $this->component = $this->createParentComponent();
    }

    public function testIsInstanceOfAbstractComponent()
    {
        $component = $this->getMockForAbstractClass(
            AbstractParentComponent::class
        );

        $this->assertInstanceOf(AbstractComponent::class, $component);
    }

    public function testByDefaultHasNotChilds()
    {
        $this->assertEmpty($this->component->children());
    }

    public function addThreeChilds()
    {
        $this->child1 = $this->createParentComponent();
        $this->child2 = $this->createParentComponent();
        $this->child3 = $this->createParentComponent();

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
        $child = $this->createParentComponent();

        $this->component->addChild($child, false);

        $this->assertNotEquals($this->component, $child->getParent());
    }

    public function testAddChildsMayInsertSeveralChildsAtOnce()
    {
        $child1 = $this->createMock(AbstractComponent::class);
        $child2 = $this->createMock(AbstractComponent::class);
        $child3 = $this->createMock(AbstractComponent::class);

        $component = $this->getMockBuilder(AbstractParentComponent::class)
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

        $component = $this->getMockBuilder(AbstractParentComponent::class)
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
        $child = $this->createParentComponent();
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
        $parent = $this->createParentComponent();
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
        $parent = $this->createParentComponent();
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
        $parent = $this->createParentComponent();
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
        $parent = $this->createParentComponent();
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
        $parent = $this->createParentComponent();
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
        $parent = $this->createParentComponent();
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
        $parent = $this->createParentComponent();
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
        $parent = $this->createParentComponent();
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
}
