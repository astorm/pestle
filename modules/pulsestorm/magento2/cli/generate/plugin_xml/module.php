<?php
namespace Pulsestorm\Magento2\Cli\Generate\Plugin_Xml;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\input');
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Format_Xml_String\format_xml');
pestle_import('Pulsestorm\Magento2\Cli\Library\inputOrIndex');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');
pestle_import('Pulsestorm\Magento2\Cli\Library\askForModuleAndReturnInfo');
pestle_import('Pulsestorm\Magento2\Cli\Library\formatXmlString');
pestle_import('Pulsestorm\Magento2\Cli\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\createClassTemplate');
pestle_import('Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass');
pestle_import('Pulsestorm\Magento2\Cli\Library\simpleXmlAddNodesXpath');


function getDiXmlTemplate($config_attributes='xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd"')
{   
    if(!$config_attributes)
    {
        $config_attributes = 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../../lib/internal/Magento/Framework/App/etc/routes.xsd"';
    }
    return trim('
<?xml version="1.0"?>
<config '.$config_attributes.'>

</config>
');

}

function underscoreClass($class)
{
    return strToLower(str_replace('\\','_',$class));
}

/**
* Generates plugin XML
* Long
* Description
* @command generate_plugin_xml
*/
function pestle_cli($argv)
{
    $module_info = askForModuleAndReturnInfo($argv);
    
    $class          = inputOrIndex(
        "Which class are you plugging into?", 'Magento\Framework\Logger\Monolog',
        $argv, 1);
    // $class          = input("Which class are you plugging into?", 'Magento\Framework\Logger\Monolog');
    
    $class_plugin      = inputOrIndex(
        "What's your plugin class name?", 
        $module_info->vendor . '\\' . $module_info->short_name .'\Plugin\\' . str_replace('\\','',$class),
        $argv, 2);    
    // $class_plugin   = input("What's your plugin class name?", 'Packagename\Vendor\Plugin\\' . str_replace('\\','',$class));
    
    $path_di = $module_info->folder . '/etc/di.xml';
    if(!file_exists($path_di))
    {
        $xml =  simplexml_load_string(getBlankXml('di'));           
        writeStringToFile($path_di, $xml->asXml());
    }
    
    $xml            =  simplexml_load_file($path_di);   
//     $plugin_name    = strToLower($module_info->name) . '_' . underscoreClass($class);
//     simpleXmlAddNodesXpath($xml,
//         "/type[@name=$class]/plugin[@name=$plugin_name,@type=$class_plugin]");
             
    $type = $xml->addChild('type');
    $type->addAttribute('name', $class);
    $plugin = $type->addChild('plugin');
    
    $plugin->addAttribute('name',strToLower($module_info->name) . '_' . underscoreClass($class));
    $plugin->addAttribute('type',$class_plugin);
    
    writeStringToFile($path_di, formatXmlString($xml->asXml()));
    
    $path_plugin = getPathFromClass($class_plugin);  
    $body = implode("\n", [
        '    //function beforeMETHOD($subject, $arg1, $arg2){}',
        '    //function aroundMETHOD($subject, $procede, $arg1, $arg2){return $proceed($arg1, $arg2);}',
        '    //function afterMETHOD($subject, $result){return $result;}']);
    $class_definition = str_replace('<$body$>', "\n$body\n", createClassTemplate($class_plugin));
    writeStringToFile($path_plugin, $class_definition);
}






