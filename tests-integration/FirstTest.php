<?php 
class FirstTest extends PHPUnit_Framework_TestCase
{
    public function runPestleCommand($name, $args=[])
    {
        // $dir = realpath(__DIR__) . '/../runner.php'
        chdir('/Users/alanstorm/Sites/magento-2-github.dev/magento2');
        // $cmd = './pestle-integration.php ' . escapeshellcmd($name) . ' ';
        $cmd = '/usr/bin/env php ' . realpath(__DIR__) . '/../runner.php ' . escapeshellcmd($name) . ' ';
        
        foreach($args as $arg)
        {
            $cmd .= escapeshellcmd($arg) . ' ';
        }
        
        $results = `$cmd`;
        return $results;
    }
    
    public function testSetup()
    {
        $result = $this->runPestleCommand('hello_world');
        $this->assertEquals($result, 'Hello Sailor' . "\n");
    }
}