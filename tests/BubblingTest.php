<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

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
        $eventName = uniqid();

        $this->component3->on($eventName, function ($event) {
            $this->executed3 = true;
            $this->time3 = microtime(true);
        });

        $this->component1->on($eventName, function ($event) {
            $this->executed1 = true;
            $this->time1 = microtime(true);
        });

        $this->root->on($eventName, function ($event) {
            $this->executedRoot = true;
            $this->timeRoot = microtime(true);
        });

        $this->component3->dispatch($eventName); // Act

        $this->assertTrue($this->executed3);
        $this->assertTrue($this->executed1);
        $this->assertTrue($this->executedRoot);

        $this->assertLessThan($this->timeRoot, $this->time1);
        $this->assertLessThan($this->time1, $this->time3);
    }

    public function testTheEventBubblingMayBeStopped()
    {
        $this->createTree2();

        $this->executed3 = false;
        $this->executed1 = false;
        $this->executedRoot = false;
        $eventName = uniqid();

        $this->component3->on($eventName, function ($event) {
            $this->executed3 = true;
        });

        $this->component1->on($eventName, function ($event) {
            $this->executed1 = true;
            $event->stopPropagation();
        });

        $this->root->on($eventName, function ($event) {
            $this->executedRoot = true;
        });

        $this->component3->dispatch($eventName); // Act

        $this->assertTrue($this->executed3);
        $this->assertTrue($this->executed1);
        $this->assertFalse($this->executedRoot);
    }
}
