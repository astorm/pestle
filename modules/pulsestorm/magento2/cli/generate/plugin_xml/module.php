<?php
namespace Pulsestorm\Magento2\Cli\Generate\Plugin_Xml;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Format_Xml_String\format_xml');
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplate');
pestle_import('Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass');
pestle_import('Pulsestorm\Xml_Library\simpleXmlAddNodesXpath');


function getDiXmlTemplate($config_attributes='xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd"')
{   
    if(!$config_attributes)
    {
        $config_attributes = 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd"';
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
* This command generates the necessary files and configuration 
* to "plugin" to a preexisting Magento 2 object manager object. 
*
*     pestle.phar generate_plugin_xml Pulsestorm_Helloworld 'Magento\Framework\Logger\Monolog' 'Pulsestorm\Helloworld\Plugin\Magento\Framework\Logger\Monolog'
* 
* @argument module_name Create in which module? [Pulsestorm_Helloworld]
* @argument class Which class are you plugging into? [Magento\Framework\Logger\Monolog]
* @argument class_plugin What's your plugin class name? [<$module_name$>\Plugin\<$class$>]
* @command generate-plugin-xml
*/
function pestle_cli($argv)
{
    // $module_info = askForModuleAndReturnInfo($argv);
    $module_info    = getModuleInformation($argv['module_name']);
    $class          = $argv['class'];
    $class_plugin   = $argv['class_plugin'];

    $path_di = $module_info->folder . '/etc/di.xml';
    if(!file_exists($path_di))
    {
        $xml =  simplexml_load_string(getBlankXml('di'));           
        writeStringToFile($path_di, $xml->asXml());
        output("Created new $path_di");
    }
    
    $class = ltrim($class, '\\');
    
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
    output("Added nodes to $path_di");
    
    $path_plugin = getPathFromClass($class_plugin);  
    $body = implode("\n", [
        '    //function beforeMETHOD(\\' . $class . ' $subject, $arg1, $arg2){}',
        '    //function aroundMETHOD(\\' . $class . ' $subject, $proceed, $arg1, $arg2){return $proceed($arg1, $arg2);}',
        '    //function afterMETHOD(\\' . $class . ' $subject, $result){return $result;}']);
    $class_definition = str_replace('<$body$>', "\n$body\n", createClassTemplate($class_plugin));
    writeStringToFile($path_plugin, $class_definition);
    output("Created file $path_plugin");
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}
