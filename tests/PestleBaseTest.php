<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
require_once __DIR__ . '/../modules/pulsestorm/pestle/runner/module.php';
namespace Pulsestorm\Pestle\Tests;
// use \PHPUnit_Framework_TestCase;
use PHPUnit\Framework\TestCase;
class PestleBaseTest extends TestCase
{
    protected function runCommand($command, $argv=[])
    {
        ob_start();
        $argv = array_merge([__FILE__, $command], $argv);
        \Pulsestorm\Pestle\Runner\main($argv);
        $results = ob_get_clean();
        return $results;
    }

    /**
    * Avoids no tests in
    */
    public function testIncludeAsBase()
    {
        $this->assertTrue(true);
    }

    protected function getPathToModuleFileUnderTest($file)
    {
        return __DIR__ . '/../' . $file;
    }
}
