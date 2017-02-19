<?php
namespace Pulsestorm\Magento2\Cli\Pestle_Clear_Cache;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Importer\getCacheDir');
/**
* BETA: Clears the pestle cache
*
* @command pestle_clear_cache
*/
function pestle_cli($argv)
{
    $cache_dir = getCacheDir();
    rename($cache_dir, $cache_dir . '.' . time());
    getCacheDir();
}
