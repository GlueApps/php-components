<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\AbstractComponent;
use GlueApps\Components\Event\AfterInsertionEvent;
use GlueApps\Components\Event\BeforeInsertionEvent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class AfterInsertionEventTest extends BaseTestCase
{
    public function testWhenAddsAChildIsTriggeredAnAfterInsertionEvent()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->on(AbstractComponent::EVENT_AFTER_INSERTION, function (AfterInsertionEvent $event) use (&$executed, $parent, $child) {
            $executed = true;
            $this->assertEquals($parent, $event->getSource());
            $this->assertEquals($parent, $event->getParent());
            $this->assertEquals($child, $event->getChild());
            $this->assertTrue($parent->hasChild($child));
        });

        $parent->addChild($child); // Act

        $this->assertTrue($executed);
    }

    public function testWhenABeforeInsertionEventCancelTheInsertionTheAfterInsertionEventIsNotTriggered()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executedBefore = false;
        $executedAfter = false;

        $parent->on(AbstractComponent::EVENT_BEFORE_INSERTION, function (BeforeInsertionEvent $event) use (&$executedBefore) {
            $executedBefore = true;
            $event->cancel();
        });

        $parent->on(AbstractComponent::EVENT_AFTER_INSERTION, function (AfterInsertionEvent $event) use (&$executedAfter) {
            $executedAfter = true;
        });

        $parent->addChild($child); // Act

        $this->assertTrue($executedBefore);
        $this->assertFalse($executedAfter);
    }
}
