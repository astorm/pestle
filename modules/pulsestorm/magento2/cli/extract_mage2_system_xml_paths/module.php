<?php
namespace Pulsestorm\Magento2\Cli\Extract_Mage2_System_Xml_Paths;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\input');
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getSimpleTreeFromSystemXmlFile');

/**
* Generates Mage2 config.xml
* Extracts configuration path's from a Magento 2 module's
* system.xml file, and then generates a config.xml file
* for the creation of default values
*
* @command extract_mage2_system_xml_paths
*/
function pestle_cli($argv)
{
    $paths = $argv;
    if(count($argv) === 0)
    {
        $paths = [input("Which system.xml?", './app/code/Magento/Theme/etc/adminhtml/system.xml')];
    }

    foreach($paths as $path)
    {
        $tree = getSimpleTreeFromSystemXmlFile($path);
    }
    
    $xml = simplexml_load_string(
    '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../Store/etc/config.xsd"><default></default></config>');
    foreach($tree as $section=>$groups)
    {
        $section = $xml->default->addChild($section);
        foreach($groups as $group=>$fields)
        {
            $group   = $section->addChild($group);
            foreach($fields as $field)
            {
                $group->addChild($field, 'DEFAULTVALUE');
            }
        }
    }
    echo $xml->asXml();
}
