<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\AbstractComponent;
use GlueApps\Components\Event\AfterDeletionEvent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class BubblingTest extends BaseTestCase
{
    public function testEventOrderInBubbling()
    {
        $this->createTree2();
        $this->executed3 = false;
        $this->executed1 = false;
        $this->executedRoot = false;

        $this->component3->on(AbstractComponent::EVENT_AFTER_DELETION, function (AfterDeletionEvent $event) {
            $this->assertEquals($this->component3, $event->getSource());
            $this->assertEquals($this->component3, $event->getParent());
            $this->assertEquals($this->component5, $event->getChild());
            $this->executed3 = true;
            $event->last = 3;
        });

        $this->component1->on(AbstractComponent::EVENT_AFTER_DELETION, function (AfterDeletionEvent $event) {
            $this->assertEquals(3, $event->last);
            $this->assertEquals($this->component3, $event->getSource());
            $this->assertEquals($this->component3, $event->getParent());
            $this->assertEquals($this->component5, $event->getChild());
            $this->executed1 = true;
            $event->last = 1;
        });

        $this->root->on(AbstractComponent::EVENT_AFTER_DELETION, function (AfterDeletionEvent $event) {
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
