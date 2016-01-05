<?php
namespace Pulsestorm\Pestle\Tests\Foo;
use Pulsestorm\Pestle\Tests\PestleBaseTest;
require_once 'PestleBaseTest.php';

use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplate');
pestle_import('Pulsestorm\PhpDotNet\glob_recursive');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Importer\getNamespaceCalledFrom');

class NamespaceRefactoringTest extends PestleBaseTest
{
    public function testsOutput()
    {
        ob_start();
        output("Hello");
        $results = ob_get_clean();
        $this->assertEquals(trim($results), "Hello");
    }
    
    public function testsFunctionExists()
    {
        $this->assertTrue(function_exists('glob_recursive'));
    }
    
    // public function testNamespaceCalledFrom()
    // {
    //     $namespace = getNamespaceCalledFrom();
    //     $this->assertEquals($namespace, __NAMESPACE__);
    // }
    // 
    // public function testNamespaceCalledFromFunction()
    // {
    //     $namespace = cleverHackToTestReflectionFunction();
    //     $this->assertEquals($namespace, __NAMESPACE__);
    // }    
}

function cleverHackToTestReflectionFunction()
{
    return getNamespaceCalledFrom();
}

/**
* @command library
*/
function pestle_cli()
{
}