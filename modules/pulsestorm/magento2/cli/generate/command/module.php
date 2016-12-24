<?php
namespace Pulsestorm\Magento2\Cli\Generate\Command;
use function Pulsestorm\Pestle\Importer\pestle_import;
use Exception;
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Xml_Library\simpleXmlAddNodesXpath');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');
pestle_import('Pulsestorm\Cli\Code_Generation\templateCommandClass');

function createPhpClass($module_dir, $namespace, $module_name, $command_name)
{
    $class_file_string = templateCommandClass($namespace, $module_name, $command_name);

    if(!is_dir($module_dir . '/Command'))
    {
        mkdir($module_dir . '/Command');
    }
    
    writeStringToFile($module_dir . '/Command/'.$command_name.'.php', $class_file_string);
}

function createDiIfNeeded($module_dir)
{
    $path_di_xml = $module_dir . '/etc/di.xml';
    
    if(!file_exists($path_di_xml))
    {
        $xml_di = simplexml_load_string(getBlankXml('di'));
        simpleXmlAddNodesXpath($xml_di, 'type[@name=Magento\Framework\Console\CommandList]');
        writeStringToFile($path_di_xml, formatXmlString($xml_di->asXml()));       
    }
    return $path_di_xml;
}

/**
* Generates bin/magento command files
* This command generates the necessary files and configuration 
* for a new command for Magento 2's bin/magento command line program.
*
*   pestle.phar generate_command Pulsestorm_Generate Example
* 
* Creates
* app/code/Pulsestorm/Generate/Command/Example.php
* app/code/Pulsestorm/Generate/etc/di.xml
*
* @command generate_command
* @argument module_name In which module? [Pulsestorm_Helloworld]
* @argument command_name Command Name? [Testbed]
*/
function pestle_cli($argv)
{
    $module_info        = getModuleInformation($argv['module_name']);    
    $namespace          = $module_info->vendor;
    $module_name        = $module_info->name;
    $module_shortname   = $module_info->short_name;
    $module_dir         = $module_info->folder;    
    $command_name       = $argv['command_name'];
    // $command_name       = input("Command Name?", 'Testbed');    
        
    output($module_dir);    
            
    createPhpClass($module_dir, $namespace, $module_shortname, $command_name);
    
    $path_di_xml = createDiIfNeeded($module_dir);
    
    $xml_di = simplexml_load_file($path_di_xml);
    
    //get commandlist node
    $nodes = $xml_di->xpath('/config/type[@name="Magento\Framework\Console\CommandList"]');
    $xml_type_commandlist = array_shift($nodes);
    if(!$xml_type_commandlist)
    {
        throw new Exception("Could not find CommandList node");
    }
    
    $argument = simpleXmlAddNodesXpath($xml_type_commandlist, 
        '/arguments/argument[@name=commands,@xsi:type=array]');

    $full_class = $namespace.'\\'.$module_shortname.'\\Command\\' . $command_name;    
    $item_name  = str_replace('\\', '_', strToLower($full_class));
    $item       = $argument->addChild('item', $full_class);
    $item->addAttribute('name', $item_name);
    $item->addAttribute('xsi:type', 'object', 'http://www.w3.org/2001/XMLSchema-instance');
    
    $xml_di     = formatXmlString($xml_di->asXml());
        
    writeStringToFile($path_di_xml, $xml_di);       
    
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}