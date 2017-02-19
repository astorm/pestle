<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Acl\Change_Title;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Xml_Library\getByAttributeXmlBlockWithNodeNames');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');

/**
* Changes the title of a specific ACL rule in a Magento 2 acl.xml file
*
* @command magento2:generate:acl:change_title
* @argument path_acl Path to ACL file? 
* @argument acl_rule_id ACL Rule ID? 
* @argument title New Title? 
*/
function pestle_cli($argv)
{
    $xml = simplexml_load_file($argv['path_acl']);
    
    $nodes = getByAttributeXmlBlockWithNodeNames(
        'id', $xml, $argv['acl_rule_id'], ['resource']);
    
    if(count($nodes) > 1)
    {
        exitWithErrorMessage("Found more than one node with {$argv['acl_rule_id']}");
    }

    $node = array_pop($nodes);            
    $node['title'] = $argv['title'];   
    
    writeStringToFile($argv['path_acl'], $xml->asXml());
    output("Changed Title");
}
