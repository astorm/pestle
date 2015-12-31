<?php
namespace Pulsestorm\Magento2\Cli\Generate\Psr_Log_Level;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getZendPsrLogLevelMap');

/**
* Converts Zend Log Level into PSR Log Level
* @command generate_psr_log_level
*/
function pestle_cli($argv)
{   
    $map = getZendPsrLogLevelMap();
    foreach($map as $key=>$value)
    {
        output($key . "\t\t" . $value);
    }
}