<?php
namespace Pulsestorm\Magento2\Cli\Generate\Psr_Log_Level;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Cli\Code_Generation\getZendPsrLogLevelMap');

/**
* For conversion of Zend Log Level into PSR Log Level
* 
* This command generates a list of Magento 1 log levels, 
* and their PSR log level equivalents.
*
* @command generate-psr-log-level
*/
function pestle_cli($argv)
{   
    $map = getZendPsrLogLevelMap();
    foreach($map as $key=>$value)
    {
        output($key . "\t\t" . $value);
    }
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}