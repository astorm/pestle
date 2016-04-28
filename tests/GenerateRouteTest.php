<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Generate\Route\createControllerClassName');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Route\getRouterIdFromArea');        
class GenerateRouteTest extends PestleBaseTest
{    
    public function testFrontend()
    {
        $module = 'Pulsestorm_Hellotest';
        $class = createControllerClassName($module);
        $this->assertEquals(
            $class,
            'Pulsestorm\Hellotest\Controller\Index\Index'
        );
    }

    public function testFrontendWithArg()
    {
        $module = 'Pulsestorm_Hellotest';
        $area   = 'frontend';
        $class = createControllerClassName($module, $area);
        $this->assertEquals(
            $class,
            'Pulsestorm\Hellotest\Controller\Index\Index'
        );
    }    
    
    public function testAdminhtmlWithArg()
    {
        $module = 'Pulsestorm_Hellotest';
        $area   = 'adminhtml';
        $class = createControllerClassName($module, $area);
        $this->assertEquals(
            $class,
            'Pulsestorm\Hellotest\Controller\Adminhtml\Index\Index'
        );
    }     
    
    public function testGetRouterIdFromAreaFrontend()
    {
        $this->assertEquals('standard', getRouterIdFromArea('frontend'));
    }

    public function testGetRouterIdFromAreaAdminhtml()
    {
        $this->assertEquals('admin', getRouterIdFromArea('adminhtml'));    
    }    
    
}