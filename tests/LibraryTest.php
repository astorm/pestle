<?php
require_once(__DIR__ . '/../modules/pulsestorm/magento2/cli/library/module.php');
use function Pulsestorm\Magento2\Cli\Library\createClassTemplate;
use function Pulsestorm\Magento2\Cli\Library\isAboveRoot;
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
    
    public function testIsAboveRoot1()
    {
        $path = '/foo/baz/bar/../../../../';
        $this->assertTrue(isAboveRoot($path));
    }    

    public function testIsAboveRoot2()
    {
        $path = '/foo/baz/bar/../../../..';
        $this->assertTrue(isAboveRoot($path));
    }    
        
    public function testIsAboveRoot3()
    {
        $path = '/foo/baz/bar/../../..';
        $this->assertTrue(!isAboveRoot($path));
    }        
    
    public function testIsAboveRoot4()
    {
        $path = '/foo/baz/bar/../..';
        $this->assertTrue(!isAboveRoot($path));
    }     
}