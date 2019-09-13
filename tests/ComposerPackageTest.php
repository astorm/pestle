<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Config\getPathConfig');
pestle_import('Pulsestorm\Pestle\Config\loadConfig');

class ComposerPackageTest extends PestleBaseTest
{
//     public function setup()
//     {
//         $path = $this->getPathToModuleFileUnderTest(
//             'modules/pulsestorm/magento2/cli/library/module.php');
//         require_once $path;
//     }

    public function testSetup()
    {
        $this->assertEquals(-1, -1);
    }

    public function testConfigLoading()
    {
        $path = getPathConfig();
        $this->assertTrue(is_string($path));
        $this->assertTrue(is_dir($path));

        $path = getPathConfig('test');
        $parts = explode('/', $path);

        $testDotJson = array_pop($parts);
        $dotPestle = array_pop($parts);

        $this->assertEquals('test.json', $testDotJson);
        $this->assertEquals('.pestle', $dotPestle);

        $object = loadConfig('test');
        $this->assertTrue(is_object($object));
    }

}
