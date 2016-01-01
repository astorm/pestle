<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Generate\Mage2_Command\createNamespaceFromCommandName');
        
// use function Pulsestorm\Magento2\Cli\Generate\Mage2_Command\createNamespaceFromCommandName;

class GenerateMage2CommandTest extends PestleBaseTest
{    
    public function testNamespaceGenerate1()
    {
        $path = createNamespaceFromCommandName('science');        
        $this->assertEquals('Pulsestorm\Magento2\Cli\Science', $path);
    }

    public function testNamespaceGenerate2()
    {
        $path = createNamespaceFromCommandName('generate_science');        
        $this->assertEquals('Pulsestorm\Magento2\Cli\Generate\Science', $path);
    }

    public function testNamespaceGenerate3()
    {
        $path = createNamespaceFromCommandName('foo_science');        
        $this->assertEquals('Pulsestorm\Magento2\Cli\Foo_Science', $path);
    }        
}