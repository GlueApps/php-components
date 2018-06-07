<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use PHPUnit\Framework\TestCase;
use GlueApps\Components\AbstractComponent;
use GlueApps\Components\AbstractParentComponent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class AbstractParentComponentTest extends TestCase
{
    public function getComponent()
    {
        return $this->getMockForAbstractClass(AbstractParentComponent::class);
    }

    public function setUp()
    {
        $this->component = $this->getComponent();
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
        $this->child1 = $this->getComponent();
        $this->child2 = $this->getComponent();
        $this->child3 = $this->getComponent();

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
        $child = $this->getComponent();

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
        $child = $this->getComponent();
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

    public function createFiveComponents()
    {
        $this->component1 = $this->getComponent();
        $this->component2 = $this->getComponent();
        $this->component3 = $this->getComponent();
        $this->component4 = $this->getComponent();
        $this->component5 = $this->createMock(AbstractComponent::class);
    }

    /**
     * component
     *     |___component1
     *             |___component2
     *                     |___component3
     *                             |___component4
     *                                     |___component5
     */
    public function buildTree1()
    {
        $this->createFiveComponents();

        $this->component->addChild($this->component1);
        $this->component1->addChild($this->component2);
        $this->component2->addChild($this->component3);
        $this->component3->addChild($this->component4);
        $this->component4->addChild($this->component5);
    }

    /**
     * component
     *     |___component1
     *     |       |___component3
     *     |               |___component5
     *     |
     *     |___component2
     *             |___component4
     */
    public function buildTree2()
    {
        $this->createFiveComponents();

        $this->component->addChild($this->component1);
        $this->component->addChild($this->component2);

        $this->component1->addChild($this->component3);
        $this->component3->addChild($this->component5);

        $this->component2->addChild($this->component4);
    }

    public function testTraverseForTree1()
    {
        $this->buildTree1();

        $iterator = $this->component->traverse();

        $this->assertEquals($this->component1, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component2, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component3, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component4, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component5, $iterator->current());
    }

    public function testTraverseForTree2()
    {
        $this->buildTree2();

        $iterator = $this->component->traverse();

        $this->assertEquals($this->component1, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component3, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component5, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component2, $iterator->current());
        $iterator->next();
        $this->assertEquals($this->component4, $iterator->current());
    }
}
