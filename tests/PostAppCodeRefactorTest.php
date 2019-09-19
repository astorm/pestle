<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\getRelativeModulePath');
pestle_import('Pulsestorm\Magento2\Cli\Library\getFullModulePath');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleBaseDir');
pestle_import('Pulsestorm\Magento2\Cli\Library\createClassFilePath');
pestle_import('Pulsestorm\Magento2\Cli\Library\getAppCodePath');
pestle_import('Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass');
pestle_import('Pulsestorm\Magento2\Cli\Fix_Direct_Om\getBaseMagentoDirFromFile');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Module\getModuleDir');
pestle_import('Pulsestorm\Pestle\Config\storageMethod');
pestle_import('Pulsestorm\Pestle\Config\loadConfig');
pestle_import('Pulsestorm\Pestle\Config\saveConfig');


class PostAppCodeRefactorTest extends PestleBaseTest
{
    // const PATH_FAKE_BASE = '/fake/base';
    protected $pathBaseMagento;
    private function fixtureComposerJson($path) {
        file_put_contents($path . '/composer.json', '{
    "name": "foo/bar",
    "description": "A Magento Module",
    "type": "magento2-module",
    "minimum-stability": "stable",
    "require": {},
    "autoload": {
        "files": [
            "registration.php"
        ],
        "psr4": {
            "Foo\\\\Bar\\\\": "src/"
        }
    }
}');
    }

    public function setup() {
        storageMethod('memory');

        $this->pathBaseMagento     = '/tmp/' . uniqid() . '/base/magento';
        $pathFooBarExtension = $this->pathBaseMagento . '/extensions/foo_bar';

        mkdir($this->pathBaseMagento, 0755, true);
        mkdir($pathFooBarExtension, 0755, true);

        $this->fixtureComposerJson($pathFooBarExtension);

        $config = (object) [
            'Foo_Bar'=>$pathFooBarExtension
        ];

        saveConfig('package-folders', $config);
    }

    public function teardown() {
        storageMethod('file');
    }

    public function testSetup()
    {
        $this->assertEquals(-1, -1);
    }

    public function testGetModulePathInFullModuleGeneration() {
        $result = getRelativeModulePath('Foo', 'Bar', $this->pathBaseMagento);
        $this->assertEquals('extensions/foo_bar/src', $result);
    }

    public function testGetFullModulePath() {
        $result = getFullModulePath('Foo', 'Bar', $this->pathBaseMagento);
        $this->assertEquals($this->pathBaseMagento . '/extensions/foo_bar/src', $result);
    }

    public function testGetModuleBaseDir() {
        $result = getModuleBaseDir('Foo_Bar', $this->pathBaseMagento);
        $this->assertEquals($this->pathBaseMagento . '/extensions/foo_bar/src', $result);
        // $this->assertEquals($this->pathBaseMagento . '/app/code/Foo/Bar', $result);
    }

    public function testCreateClassFilePath() {
        $result = createClassFilePath('Foo\Bar\Baz\Bap', $this->pathBaseMagento);
        $this->assertEquals($this->pathBaseMagento . '/extensions/foo_bar/src/Baz/Bap.php', $result);
    }

    public function testGetPathFromClass() {
        $result = getPathFromClass('Foo\Bar\Baz\Bap', $this->pathBaseMagento);
        $this->assertEquals($this->pathBaseMagento . '/extensions/foo_bar/src/Baz/Bap.php', $result);
    }

    public function testGetAppCode() {
        // should always return app/code, since that's the function's
        // name.  When we get to creating stuff in someone's folder
        // we'll want to use a different function.  This test holds
        // us to that.
        $this->assertEquals('app/code', getAppCodePath());
    }

    public function testGetBaseMagentoDirFromFile() {
        $path1 = $this->pathBaseMagento . '/app/code/Foo/Bar/Model/Thing.php';
        $path2 = $this->pathBaseMagento . '/vendor/some-vendor/some-module/src/Foo/Bar/Model/Thing.php';

        $result1 = getBaseMagentoDirFromFile($path1, true);
        $result2 = getBaseMagentoDirFromFile($path2, true);

        $this->assertEquals($this->pathBaseMagento . '/app/code', $result1);
        $this->assertEquals($this->pathBaseMagento . '/vendor', $result2);
    }

    public function testGetModuleDir() {
        $result = getModuleDir($this->pathBaseMagento, 'Foo', 'Bar');
        $this->assertEquals($this->pathBaseMagento . '/extensions/foo_bar/src', $result);
    }

    public function testToDo() {
        // var_dump("TODO: What about composer files that are missing an autoload?");
        // var_dump("TODO: Also, I don't think we need to look for an autoloader in the command?");
        // var_dump("TODO: Also, print a reminder about adding the repository section and composer require?");
        // var_dump("TODO: Also, fix the composer package name that's used.  Make an argument?");
        // var_dump("TODO: registration.php is generated in the wrong folder?");
        // var_dump("TODO: there's a trim that needs to rtrim?");
        var_dump("TODO: Test with real modules");
        // $this->assertTrue(false);
        $this->assertTrue(true);
    }
}
