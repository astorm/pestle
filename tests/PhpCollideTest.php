<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Cli\Token_Parse\token_get_all');
class PhpCollideTest extends PestleBaseTest
{
    public function testFunctions()
    {
        $function_exists = function_exists('pestle_token_get_all');
        $this->assertTrue($function_exists);
    }
}