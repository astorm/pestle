<?php
use function Pulsestorm\Magento2\Cli\Generate\Mage2_Command\createNamespaceFromCommandName;
// require_once __DIR__ . '/../runner.php';    
class GenerateMage2CommandTest extends PHPUnit_Framework_TestCase
{
    public function setup()
    {        
        $this->includeRunner();
    }
    
    public function testNamespaceGenerate1()
    {
        // $this->includeRunner();
        $path = createNamespaceFromCommandName('science');        
        $this->assertEquals('Pulsestorm\Magento2\Cli\Science', $path);
    }

    public function testNamespaceGenerate2()
    {
        // $this->includeRunner();
        $path = createNamespaceFromCommandName('generate_science');        
        $this->assertEquals('Pulsestorm\Magento2\Cli\Generate\Science', $path);
    }

    public function testNamespaceGenerate3()
    {
        // $this->includeRunner();
        $path = createNamespaceFromCommandName('foo_science');        
        $this->assertEquals('Pulsestorm\Magento2\Cli\Foo_Science', $path);
    }        
    
    protected function includeRunner()
    {
        global $argv;
        $real_argv = $argv;
        $argv = [];
        ob_start();
        require_once('runner.php');
        ob_end_clean();
        $argv = $real_argv;    
    }
}