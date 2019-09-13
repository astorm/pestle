<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Config\storageMethod');
pestle_import('Pulsestorm\Pestle\Config\loadConfig');
pestle_import('Pulsestorm\Pestle\Config\saveConfig');
pestle_import('Pulsestorm\Pestle\Config\setConfigBase');
pestle_import('Pulsestorm\Pestle\Config\getOrSetConfigBase');

// function loadConfigMemory($configType) {
//     return storeOrFetchMemoryBasedConfig($configType);
//
// }
//
// function saveConfigMemory($configType, $config) {
//     storeOrFetchMemoryBasedConfig($configType, $config);
//     return true;
// }

class ConfigTest extends PestleBaseTest
{
    public function setup() {
        $dir = '/tmp/.pestle';
        $file = $dir . '/some-config.json';
        if(file_exists($file)) {
            unlink($file);
        }
        if(is_dir($dir)) {
            rmdir($dir);
        }
    }

    public function testStorage() {
        $this->assertEquals('file', storageMethod());
    }

    public function testStorageSetMemory() {
        storageMethod('memory');
        $this->assertEquals('memory', storageMethod());
        $this->configTests();
    }

    public function testStorageSetFile() {
        storageMethod('file');
        $this->assertEquals('file', storageMethod());
        getOrSetConfigBase('/tmp/.pestle');
        $this->assertEquals('/tmp/.pestle', getOrSetConfigBase());
        $this->configTests();
    }

    private function configTests() {
        $type = 'some-config';

        // loadng a blank config returns a stdClass
        $config = loadConfig($type);
        $this->assertTrue(is_object($config));
        $this->assertEquals([], get_object_vars($config));

        $config->Pulsestorm_Science = '/foo/baz/bar';
        saveConfig($type, $config);

        $config = loadConfig($type);
        $this->assertEquals('/foo/baz/bar', $config->Pulsestorm_Science);
    }

}
