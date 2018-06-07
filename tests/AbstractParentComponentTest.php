<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\AbstractComponent;
use GlueApps\Components\AbstractParentComponent;

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

    public function testDropChild()
    {
        $this->addThreeChilds();

        $this->component->dropChild($this->child2->getUId());

        $expected = [
            $this->child1->getUId() => $this->child1,
            $this->child3->getUId() => $this->child3,
        ];

        $this->assertEquals($expected, $this->component->children());
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
}
