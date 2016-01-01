<?php
namespace Pulsestorm\Magento2\Cli\Baz_Bar;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* One Line Description
*
* @command baz_bar
*/
function pestle_cli($argv)
{
    output("Hello Sailor");
}
