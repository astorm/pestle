<?php
namespace Pulsestorm\Pestle\Version;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
/**
* Displays Pestle Version
*
* @command version
*/
function pestle_cli($argv)
{
    output('pestle Ver ' . PULSESTORM_PESTLE_VERSION);
}
