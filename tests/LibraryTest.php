<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplate');
pestle_import('Pulsestorm\Pestle\Library\isAboveRoot');

class LibraryTest extends PestleBaseTest
{
    public function setup()
    {
        $path = $this->getPathToModuleFileUnderTest(
            'modules/pulsestorm/magento2/cli/library/module.php');
        require_once $path;
    }
    
    public function testSetup()
    {
        $this->assertEquals(-1, -1);
    }

    public function testCreateClassTemplate()
    {
        $fixture = '<' . '?php
namespace ;

/**
 * Class Foo
 */
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