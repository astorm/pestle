<?php

namespace Pulsestorm\Pestle\TestsIntegration;

require_once 'PestleTestIntegration.php';

class HelloWorldTest extends PestleTestIntegration
{

    const COMMAND = 'hello_world';

    public function testSetup()
    {
        $results = trim($this->runCommand());
        $this->assertEquals($results, 'Hello Sailor');
    }
}
