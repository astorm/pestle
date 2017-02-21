<?php
namespace Pulsestorm\Magento2\Cli\Read_Rest_Schema;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* BETA: Magento command, reads the rest schema on a Magento system
*
* @command magento2:read_rest_schema
* @argument url Base Url? [http://magento-2-with-keys.dev/]
*/
function pestle_cli($argv)
{
    extract($argv);
    $url .= '/rest/default/schema';
    $contents = file_get_contents($url);
    $object = json_decode($contents);    
    var_dump($object);
}
