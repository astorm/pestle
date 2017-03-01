<?php
namespace Pulsestorm\Magento2\Cli\Foo_Bar;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* ALPHA: Another Hello World we can probably discard
*
* @command pestle:foo-bar
*/
function pestle_cli($argv)
{
    output("Hello Sailor");
}
