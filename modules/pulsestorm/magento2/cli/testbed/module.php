<?php
namespace Pulsestorm\Magento2\Cli\Testbed;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\input');

/**
* Test Command
* @command testbed
* @argument argument_name Please enter a value for argument_name [the default value]
* @argument argument_foo Please enter a value for argument_name [the default value]
*/
function pestle_cli($arguments, $options)
{
    foreach($arguments as $key=>$value)
    {
        output($key . '::' . $value);
    }    
}
