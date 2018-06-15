<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use PHPUnit\Framework\TestCase;
use GlueApps\Components\AbstractComponent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class BaseTestCase extends TestCase
{
    public function setUp()
    {
        $this->component = $this->createComponent();
    }

    public function createComponent()
    {
        return $this->getMockForAbstractClass(AbstractComponent::class);
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

    public function createTreeComponents()
    {
        $this->root = $this->createComponent();
        $this->component1 = $this->createComponent();
        $this->component2 = $this->createComponent();
        $this->component3 = $this->createComponent();
        $this->component4 = $this->createComponent();
        $this->component5 = $this->createComponent();
    }

    /**
     * root
     *   |___component1
     *           |___component2
     *                   |___component3
     *                           |___component4
     *                                   |___component5
     */
    public function createTree1()
    {
        $this->createTreeComponents();

        $this->root->addChild($this->component1);
        $this->component1->addChild($this->component2);
        $this->component2->addChild($this->component3);
        $this->component3->addChild($this->component4);
        $this->component4->addChild($this->component5);
    }

    /**
     * root
     *   |___component1
     *   |       |___component3
     *   |               |___component5
     *   |
     *   |___component2
     *           |___component4
     */
    public function createTree2()
    {
        $this->createTreeComponents();

        $this->root->addChild($this->component1);
        $this->root->addChild($this->component2);

        $this->component1->addChild($this->component3);
        $this->component3->addChild($this->component5);

        $this->component2->addChild($this->component4);
    }
}
