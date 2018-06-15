<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\AbstractComponent;
use GlueApps\Components\Event\BeforeDeletionEvent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class BeforeDeletionEventTest extends BaseTestCase
{
    public function testDropChildTriggersAnBeforeDeletionEvent1()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->addChild($child);
        $parent->on(AbstractComponent::EVENT_BEFORE_DELETION, function (BeforeDeletionEvent $event) use (&$executed, $parent, $child) {
            $executed = true;
            $this->assertEquals($parent, $event->getSource());
            $this->assertEquals($parent, $event->getParent());
            $this->assertEquals($child, $event->getChild());
            $this->assertTrue($parent->hasChild($child));
        });

        $parent->dropChild($child); // Act

        $this->assertTrue($executed);
    }

    public function testDropChildTriggersAnBeforeDeletionEvent2()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->addChild($child);
        $parent->on(AbstractComponent::EVENT_BEFORE_DELETION, function (BeforeDeletionEvent $event) use (&$executed, $parent, $child) {
            $executed = true;
            $this->assertEquals($parent, $event->getSource());
            $this->assertEquals($parent, $event->getParent());
            $this->assertEquals($child, $event->getChild());
            $this->assertTrue($parent->hasChild($child));
        });

        $child->setParent(null); // Act

        $this->assertTrue($executed);
    }

    public function testAcrossTheBeforeDeletionEventIsPossibleCancelTheDeletion()
    {
        $parent = $this->createComponent();
        $child = $this->createComponent();
        $executed = false;

        $parent->addChild($child);
        $parent->on(AbstractComponent::EVENT_BEFORE_DELETION, function (BeforeDeletionEvent $event) use (&$executed) {
            $executed = true;
            $event->cancel();
        });

        $child->setParent(null); // Act

        $this->assertTrue($executed);
        $this->assertTrue($parent->hasChild($child));
    }
}
