<?php
namespace Pulsestorm\Magento2\Cli\Generate\Layout_Xml;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* ALPHA: Is this needed/working anymore?
* This command will generate the layout handle XML 
* files needed to add a block to Magento's page 
* layout
*
* @command generate-layout-xml
* @todo implement me please
*/
function pestle_cli($argv)
{
    output("Needs to be implemented");
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}