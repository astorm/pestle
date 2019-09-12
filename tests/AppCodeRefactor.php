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

class AppCodeRefactorTest extends PestleBaseTest
{
    const PATH_FAKE_BASE = '/fake/base';
    public function testSetup()
    {
        $this->assertEquals(-1, -1);
    }

    public function testGetModulePathInFullModuleGeneration() {
        $result = getRelativeModulePath('Foo', 'Bar', self::PATH_FAKE_BASE);
        $this->assertEquals('app/code/Foo/Bar', $result);
    }

    public function testGetFullModulePath() {
        $result = getFullModulePath('Foo', 'Bar', self::PATH_FAKE_BASE);
        $this->assertEquals('/fake/base/app/code/Foo/Bar', $result);
    }

    public function testGetModuleBaseDir() {
        $result = getModuleBaseDir('Foo_Bar', self::PATH_FAKE_BASE);
        $this->assertEquals('/fake/base/app/code/Foo/Bar', $result);
        // $this->assertEquals('/fake/base/app/code/Foo/Bar', $result);
    }

    public function testCreateClassFilePath() {
        $result = createClassFilePath('Foo\Bar\Baz\Bap', self::PATH_FAKE_BASE);
        $this->assertEquals('/fake/base/app/code/Foo/Bar/Baz/Bap.php', $result);
    }

    public function testGetPathFromClass() {
        $result = getPathFromClass('Foo\Bar\Baz\Bap', self::PATH_FAKE_BASE);
        $this->assertEquals('/fake/base/app/code/Foo/Bar/Baz/Bap.php', $result);
    }

    public function testGetAppCode() {
        // should always return app/code, since that's the function's
        // name.  When we get to creating stuff in someone's folder
        // we'll want to use a different function.  This test holds
        // us to that.
        $this->assertEquals('app/code', getAppCodePath());
    }

    public function testGetBaseMagentoDirFromFile() {
        $path1 = self::PATH_FAKE_BASE . '/app/code/Foo/Bar/Model/Thing.php';
        $path2 = self::PATH_FAKE_BASE . '/vendor/some-vendor/some-module/src/Foo/Bar/Model/Thing.php';

        $result1 = getBaseMagentoDirFromFile($path1, true);
        $result2 = getBaseMagentoDirFromFile($path2, true);

        $this->assertEquals(self::PATH_FAKE_BASE . '/app/code', $result1);
        $this->assertEquals(self::PATH_FAKE_BASE . '/vendor', $result2);
    }

    public function testGetModuleDir() {
        $result = getModuleDir(self::PATH_FAKE_BASE, 'Foo', 'Bar');
        $this->assertEquals('/fake/base/app/code/Foo/Bar', $result);
    }
}
