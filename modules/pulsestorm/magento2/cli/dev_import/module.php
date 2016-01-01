<?php
namespace Pulsestorm\Magento2\Cli\Dev_Import;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
/**
* This is a test
* @command dev_import
*/
function pestle_cli($argv)
{
    output("test");
}