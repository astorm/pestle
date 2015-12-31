<?php
namespace Pulsestorm\Magento2\Cli\Format_Xml_String;
use DomDocument;

/**
* @command library
*/
function pestle_cli($argv)
{
}

function format_xml($xml_string)
{
    $dom = new DomDocument();
    $dom->preserveWhitespace = false;			
    $dom->loadXml($xml_string);
    $dom->formatOutput		= true;			
    $output = $dom->saveXml();
    
    return $output;
}
