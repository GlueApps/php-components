<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\AbstractComponent;
use GlueApps\Components\Event\BeforeDeletionEvent;
use GlueApps\Components\Event\AfterDeletionEvent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class AfterDeletionEventTest extends BaseTestCase
{
    public function testDropChildTriggersAnAfterDeletionEvent1()
    {
        $this->addThreeChilds();
        $executed = false;

        $this->component->on(AbstractComponent::EVENT_AFTER_DELETION, function (AfterDeletionEvent $event) use (&$executed) {
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

        $this->component->on(AbstractComponent::EVENT_AFTER_DELETION, function (AfterDeletionEvent $event) use (&$executed) {
            $executed = true;
            $this->assertEquals($this->component, $event->getSource());
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
}
