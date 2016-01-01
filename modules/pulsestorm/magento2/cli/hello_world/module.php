<?php
namespace Pulsestorm\Magento2\Cli\Hello_World;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\output');

/**
* One Line Description
*
* @command hello_world
*/
function pestle_cli($argv, $options)
{
    $person = 'Sailor';
    if(array_key_exists('service', $options))
    {
        if($options['service'] === 'army')
        {
            $person = 'Soldier';
        }
        
    }
    output("Hello $person");
}
