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
}
