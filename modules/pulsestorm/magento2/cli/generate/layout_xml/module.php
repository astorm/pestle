<?php
namespace Pulsestorm\Magento2\Cli\Generate\Layout_Xml;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* One Line Description
* This command will generate the layout handle XML 
* files needed to add a block to Magento's page 
* layout
*
* @command generate_layout_xml
* @todo implement me please
*/
function pestle_cli($argv)
{
    output("Needs to be implemented");
}