<?php
namespace Pulsestorm\Magento2\Cli\Baz_Bar;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* Another Hello World we can probably discard
*
* @command pestle:baz_bar
*/
function pestle_cli($argv)
{
    output("Hello Sailor");
}
