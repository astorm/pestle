<?php
namespace Pulsestorm\Magento2\Cli\Generate\Layout_Xml;
use function Pulsestorm\Pestle\Runner\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\inputOrIndex');
pestle_import('Pulsestorm\Magento2\Cli\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\output');

/**
* One Line Description
*
* @command generate_layout_xml
*/
function pestle_cli($argv)
{
    output("Hello");
}