<?php
require_once(__DIR__ . '/../modules/pulsestorm/magento2/cli/library/module.php');
use function Pulsestorm\Magento2\Cli\Library\createClassTemplate;
class LibraryTest extends PHPUnit_Framework_TestCase
{
    public function testSetup()
    {
        $this->assertEquals(-1, -1);
    }

    public function testCreateClassTemplate()
    {
        $fixture = '<' . '?php
namespace ;
class Foo
{<$body$>}' . "\n";  
 
        $template = createClassTemplate('Foo');        
        $this->assertEquals($template, $fixture);        
    }
}