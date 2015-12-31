<?php
namespace Pulsestorm\Magento2\Cli\Dev_Import;
use function Pulsestorm\Pestle\Importer\pestle_import;
// use function Pulsestorm\Magento2\Cli\Library\output;
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
/**
* This is a test
* @command dev_import
*/
function pestle_cli($argv)
{
    output("test");
}