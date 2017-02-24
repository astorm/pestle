<?php
namespace Pulsestorm\Magento2\Generate\Remove_Named_Node;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Xml_Library\getByAttributeXmlBlockWithNodeNames');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');

/**
* Removes a named node from a generic XML configuration file
*
* @command magento2:generate:remove-named-node
* @argument path_xml The XML file? []
* @argument node_name The <node_name/>? [block]
* @argument name The {node_name}="" value? []
*/
function pestle_cli($argv)
{
    $xml = simplexml_load_file($argv['path_xml']);
    $nodes = getByAttributeXmlBlockWithNodeNames(
        'name', $xml, $argv['name'], [$argv['node_name']]);    

    if(count($nodes) === 0)
    {
        exitWithErrorMessage("Bailing: No such node.");
    }

    if(count($nodes) > 1)
    {
        exitWithErrorMessage("Bailing: Found more than one node.");
    }
            
    $node = $nodes[0];            
    
    if(count($node->children()) > 0)
    {
        exitWithErrorMessage("Bailing: Contains child nodes");
    }

    unset($node[0]); //http://stackoverflow.com/questions/262351/remove-a-child-with-a-specific-attribute-in-simplexml-for-php/16062633#16062633
        
    writeStringToFile(
        $argv['path_xml'],formatXmlString($xml->asXml())
    );
    output("Node Removed");
}
