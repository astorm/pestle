<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
use is_dir;
pestle_import('Pulsestorm\Pestle\Importer\getHomeDirectory');

class WindowsTest extends PestleBaseTest {
    public function testGetHomeDirectory() {
        $dir = getHomeDirectory();
        $this->assertTrue(is_dir($dir));
    }
}
