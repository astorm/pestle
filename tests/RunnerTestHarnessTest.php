<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
class RunnerTestHarnessTest extends PestleBaseTest
{   
    public function testRunCommand()
    {
        $results = trim($this->runCommand('hello_world'));
        $this->assertEquals('Hello Sailor', $results);
    }
}