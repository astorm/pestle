<?php

namespace Pulsestorm\Pestle\TestsIntegration;

require_once 'PestleTestIntegration.php';

class HelloWorldTest extends PestleTestIntegration
{

    public function testSetup()
    {
        $results = trim($this->runCommand('hello_world'));
        $this->assertEquals($results,'Hello Sailor');
    }
}
