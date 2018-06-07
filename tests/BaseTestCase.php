<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use PHPUnit\Framework\TestCase;
use GlueApps\Components\AbstractComponent;
use GlueApps\Components\AbstractParentComponent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class BaseTestCase extends TestCase
{
    public function createComponent()
    {
        return $this->getMockForAbstractClass(AbstractComponent::class);
    }

    public function createParentComponent()
    {
        return $this->getMockForAbstractClass(AbstractParentComponent::class);
    }

    public function createFiveComponents()
    {
        $this->component1 = $this->createParentComponent();
        $this->component2 = $this->createParentComponent();
        $this->component3 = $this->createParentComponent();
        $this->component4 = $this->createParentComponent();
        $this->component5 = $this->createComponent();
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
}
