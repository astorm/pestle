<?php
namespace Pulsestorm\Magento2\Cli\Check_Htaccess;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');


/**
* Short Description
* Long
* Description
* @command check_htaccess
*/
function pestle_cli($argv)
{
    $files = [
        './app/.htaccess',
        './.htaccess',
        './app/.htaccess',
        './bin/.htaccess',
        './dev/.htaccess',
        './lib/.htaccess',
        './phpserver/.htaccess',
        './pub/.htaccess',
        './pub/errors/.htaccess',
        './pub/media/.htaccess',
        './pub/media/customer/.htaccess',
        './pub/media/downloadable/.htaccess',
        './pub/media/import/.htaccess',
        './pub/media/theme_customization/.htaccess',
        './pub/static/.htaccess',
        './pub/static.finally/.htaccess',
        './setup/.htaccess',
        './setup/config/.htaccess',
        './setup/performance-toolkit/.htaccess',
        './setup/pub/.htaccess',
        './setup/src/.htaccess',
        './setup/view/.htaccess',
        './update/.htaccess',
        './update/app/.htaccess',
        './update/dev/.htaccess',
        './update/pub/.htaccess',
        './update/var/.htaccess',
        './var/.htaccess',
        './var/composer_home/.htaccess',
        './var/composer_home/cache/.htaccess',        
    ];
    
    foreach($files as $file)
    {
        if(!file_exists($file))
        {
            output("ERROR: Missing: " . $file);
            continue;
        }
        output("Found: $file");
    }
    output("Done");
}