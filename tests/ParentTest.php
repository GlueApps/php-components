<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\AbstractComponent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class ParentTest extends BaseTestCase
{
    public function testIsNullByDefault()
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
}
