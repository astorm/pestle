<?php
namespace Pulsestorm\Pestle\Version;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
define('PULSESTORM_PESTLE_VERSION', '1.4.0');
/**
* Displays Pestle Version
*
* @command version
*/
function pestle_cli($argv)
{
    output('pestle Ver ' . PULSESTORM_PESTLE_VERSION);
}
