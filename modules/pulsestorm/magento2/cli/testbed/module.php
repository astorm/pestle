<?php
namespace Pulsestorm\Magento2\Cli\Testbed;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
pestle_import('Pulsestorm\Cli\Build_Command_List\getListOfFilesInModuleFolder');

/**
* Reports on possible 
*
* @todo bug where a second "/module/" in the path borks things
* @command testbed
*/
function pestle_cli($argv)
{
    var_dump($argv);
}
