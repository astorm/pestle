<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Ladeda;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* One Line Description
*
* @command magento2:generate:ladeda
*/
function pestle_cli($argv)
{
    output("Hello Sailor");
}
