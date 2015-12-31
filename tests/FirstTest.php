<?php
class FirstTest extends PHPUnit_Framework_TestCase
{
    public function testSetup()
    {
        $this->assertEquals(-1, -1);
    }

    public function testRunner()
    {
        global $argv;
        $real_argv = $argv;
        $argv = [];
        ob_start();
        require_once('runner.php');
        ob_end_clean();
        $argv = $real_argv;
        
        // Hello Sailor
        ob_start();
        \Pulsestorm\Pestle\Runner\main(['fake-script.php','hello_world']);        
        $results = ob_get_clean();
        $this->assertEquals('Hello Sailor' . "\n", $results);
    }
}