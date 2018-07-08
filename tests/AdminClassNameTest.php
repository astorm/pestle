<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Cli\Code_Generation\createControllerClass');

class AdminClassNameTest extends PestleBaseTest
{
    public function testSetup()
    {
        $this->assertEquals(-1, -1);
    }

    public function testBaseFrontendArgs()
    {
        $controllerClass = createControllerClass(
            'Pulsestorm\HelloWorld\Controller\Index\Index',
            'frontend',
            'some-acl-rule-name');
        $fixture = $this->loadFixture(__METHOD__);
        $this->assertEquals($fixture, $controllerClass);
        // echo $controllerClass;
    }

    public function testBaseAdminArgs()
    {
        $controllerClass = createControllerClass(
            'Pulsestorm\HelloWorld\Controller\Adminhtml\Index\Index',
            'adminhtml',
            'some-acl-rule-name');
        $fixture = $this->loadFixture(__METHOD__);
        $this->assertEquals($fixture, $controllerClass);
       // echo $controllerClass;
    }

    protected function loadFixture($method)
    {
        return file_get_contents(__DIR__ . '/fixtures/AdminClassName/' .
            preg_replace('%^.*?::test%','',$method) . '.php' );
    }
}
