<?php
namespace Pulsestorm\Magento2\Cli\Fix_Permissions_Modphp;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');

/**
* ALPHA: "Fixes" permissions for development boxes
* running mod_php by setting things to 777. 
* @command magento2:fix-permissions-modphp
*/
function pestle_cli($argv)
{
    $base = getBaseMagentoDir();
    $cmds = [
        "find $base/pub/static -exec chmod 777 '{}' +",
	    "find $base/var/ -exec chmod 777 '{}' +",
    ];
    
    foreach($cmds as $cmd)
    {
        $results = `$cmd`;
        if($results)
        {
            output($results);
        }
    }
}
