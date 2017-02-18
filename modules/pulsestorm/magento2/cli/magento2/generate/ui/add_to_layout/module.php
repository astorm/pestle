<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Ui\Add_To_Layout;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Xml_Library\getNamedXmlBlockWithNodeNames');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');

function exitWithErrorMessage($message)
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function validateNoSuchComponent($xml, $name)
{
    $nodes = getNamedXmlBlockWithNodeNames($xml, $name, ['uiComponent']);
    if(count($nodes) === 0)
    {
        return;
    }
    exitWithErrorMessage("Bailing: uiComponent Node Already Exists");
}

function getContentBlockOrContainerOrReference($xml, $name)
{
    return getNamedXmlBlockWithNodeNames($xml, $name, 
        ['container', 'block', 'referenceContainer','referenceBlock']);
}

function getContentNode($xml,$argv)
{
    $nodes = getContentBlockOrContainerOrReference($xml, $argv['block_name']);    
    if(count($nodes) > 1)
    {
        exitWithErrorMessage("BAILING: Found more than one name=\"".$argv['block_name']."\" node.\n");
    }
    return array_pop($nodes);
}

/**
* One Line Description
*
* @command magento2:generate:ui:add_to_layout
* @argument path_layout Layout XML File?
* @argument block_name Block or Reference Name?
* @argument ui_component_name UI Component Name?
*/
function pestle_cli($argv)
{
    $xml    = simplexml_load_file($argv['path_layout']);    
    validateNoSuchComponent($xml, $argv['ui_component_name']);
    $node   = getContentNode($xml, $argv);
    
    $node->addChild('uiComponent')
        ->addAttribute('name', $argv['ui_component_name']);
    $xmlString    = formatXmlString($xml->asXml());        
    writeStringToFile($argv['path_layout'], $xmlString);        
    output("Added Component");
}
