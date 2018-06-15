<?php
declare(strict_types=1);

namespace GlueApps\Components\Tests;

use GlueApps\Components\AbstractComponent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class UniqueIdentifierTest extends BaseTestCase
{
    public function testIsARandomWord()
    {
        $this->assertRegExp('/^\w{13}$/', $this->component->getUId());
    }

    public function testSetUIdAssignsTheUniqueIdentifier()
    {
        $uid = uniqid();

        $this->component->setUId($uid);

        $this->assertEquals($uid, $this->component->getUId());
    }

    public function testGenerateUIdDefinesTheGenerationMethodOfTheUniqueIdentifier()
    {
        $uid = uniqid();

        $component = new class($uid) extends AbstractComponent {
            public function __construct($newUId)
            {
                $this->newUId = $newUId;

                parent::__construct(); // Act
            }

            protected function generateUId(): void
            {
                $this->uid = $this->newUId;
            }
        };

        $this->assertEquals($uid, $component->getUId());
    }
}
