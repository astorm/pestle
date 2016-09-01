<?php
namespace Pulsestorm\Magento2\Cli\Magento2_Generate_Ui_Add_Column_Sections;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Magento2\Cli\Library\addArgument');
pestle_import('Pulsestorm\Magento2\Cli\Library\addItem');
pestle_import('Pulsestorm\Magento2\Cli\Library\validateAsListing');
pestle_import('Pulsestorm\Magento2\Cli\Library\getOrCreateColumnsNode');
// pestle_import('Pulsestorm\Xml_Library\simpleXmlAddNodesXpath');    
// pestle_import('Pulsestorm\Xml_Library\formatXmlString');
// pestle_import('Pulsestorm\Xml_Library\getXmlNamespaceFromPrefix');
// pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');
// pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
// pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
// pestle_import('Pulsestorm\Magento2\Cli\Library\createClassFile');
// pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse');

/**
* Generates a Magento 2.1 ui grid listing and support classes.
*
* @command magento2:generate:ui:add-column-sections
* @argument listing_file Which Listing File? []
* @argument column_name Column Name? [ids]
* @argument index_field Index Field/Primary Key? [entity_id]
*/
function pestle_cli($argv)
{
    $xml = simplexml_load_file($argv['listing_file']);
    validateAsListing($xml);
    $columns = getOrCreateColumnsNode($xml);
    
    $sectionsColumn = $columns->addChild('selectionsColumn');
    $sectionsColumn->addAttribute('name', $argv['column_name']);    
    $argument = addArgument($sectionsColumn, 'data', 'array');    
    $configItem = addItem($argument, 'config', 'array');
    $indexField = addItem($configItem, 'indexField', 'string', $argv['index_field']);

    writeStringToFile($argv['listing_file'], formatXmlString($xml->asXml()));     

}
