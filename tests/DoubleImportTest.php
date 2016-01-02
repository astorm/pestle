<?php
namespace Pulsestorm\Pestle\Tests\DoubleImportTest;
//extra namespave level is to avoid pestle_importing into
//other test namespaces
use Pulsestorm\Pestle\Tests\PestleBaseTest;

require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;

class DoubleImportTest extends PestleBaseTest
{
    public function testBothOutputs()
    {
        pestle_import('Pulsestorm\Pestle\Library\output');
        ob_start();
        output("Hello");
        $string = ob_get_clean();
        $this->assertEquals($string,'Hello' . "\n");
    }
    
    public function testSecondOutput()
    {
        pestle_import('Pulsestorm\Magento2\Cli\Test_Output\output');    
        ob_start();
        output("Hello");
        $string = ob_get_clean();
        $this->assertEquals($string,'I am hard coded and here for a test.');    
    }
    
    public function testBothAtOne()
    {        
        pestle_import('Pulsestorm\Pestle\Library\output');
        ob_start();
        output("Hello");
        $string = ob_get_clean();
        $this->assertEquals($string,'Hello' . "\n");        
        
        pestle_import('Pulsestorm\Magento2\Cli\Test_Output\output');    
        ob_start();
        output("Hello");
        $string = ob_get_clean();
        $this->assertEquals($string,'I am hard coded and here for a test.');                
    }
    
}