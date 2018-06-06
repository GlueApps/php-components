<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use PHPUnit\Framework\TestCase;
use GlueApps\Components\AbstractComponent;
use GlueApps\Components\AbstractParentComponent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class AbstractComponentTest extends TestCase
{
    public function getComponent()
    {
        return $this->getMockForAbstractClass(AbstractComponent::class);
    }

    public function getParent()
    {
        return $this->getMockForAbstractClass(AbstractParentComponent::class);
    }

    public function setUp()
    {
        $this->component = $this->getComponent();
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
        $parent = $this->getParent();

        $this->component->setParent($parent);

        $this->assertEquals($parent, $this->component->getParent());
    }

    public function testSetParentRegistersTheComponentAsChildOfTheParent()
    {
        $parent = $this->getParent();

        $this->component->setParent($parent);

        $this->assertTrue($parent->hasChild($this->component));
    }

    public function testSetParentDoesNotRegistersTheComponentAsChildOfTheParentWhenSecondArgumentIsFalse()
    {
        $parent = $this->getParent();

        $this->component->setParent($parent, false);

        $this->assertFalse($parent->hasChild($this->component));
    }
}
