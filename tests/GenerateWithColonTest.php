<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Cli\Code_Generation\createNamespaceFromNamespaceAndCommandName');

class GenerateWithColonTest extends PestleBaseTest
{
    public function testBaseline1()
    {
        //createNamespaceFromNamespaceAndCommandName($namespace_module, $command_name)
        $value = createNamespaceFromNamespaceAndCommandName('Foo\Baz\Bar', 'test');
        $this->assertEquals('Foo\Baz\Bar\Test', $value);
    }
    
    public function testBaseline2()
    {
        //createNamespaceFromNamespaceAndCommandName($namespace_module, $command_name)
        $value = createNamespaceFromNamespaceAndCommandName('Foo\Baz\Bar', 'test_command');
        $this->assertEquals('Foo\Baz\Bar\Test_Command', $value);
    }    
    
    public function testBaseline3()
    {
        //createNamespaceFromNamespaceAndCommandName($namespace_module, $command_name)
        $value = createNamespaceFromNamespaceAndCommandName('Foo\Baz\Bar', 'generate_test_command_thing');
        $this->assertEquals('Foo\Baz\Bar\Generate\Test\Command\Thing', $value);
    }   
        
//     public function testCommandWithColon()
//     {
//         //createNamespaceFromNamespaceAndCommandName($namespace_module, $command_name)
//         $value = createNamespaceFromNamespaceAndCommandName('Foo\Baz\Bar', 'test:command');
//         $this->assertEquals('Foo\Baz\Bar\Test\Command', $value);
//     }        
}