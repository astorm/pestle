<?php 
class FirstTest extends PHPUnit_Framework_TestCase
{
    protected function runCommand($cmd)
    {
        return `pestle.phar $cmd`;
    }
    
    public function testSetup()
    {
        $results = trim($this->runCommand('hello_world'));
        $this->assertEquals($results,'Hello Sailor');
    }
}