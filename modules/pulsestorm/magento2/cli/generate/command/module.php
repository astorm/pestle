<?php
namespace Pulsestorm\Magento2\Cli\Generate\Command;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\input');
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');
pestle_import('Pulsestorm\Magento2\Cli\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\askForModuleAndReturnInfo');
pestle_import('Pulsestorm\Magento2\Cli\Library\formatXmlString');
pestle_import('Pulsestorm\Magento2\Cli\Library\simpleXmlAddNodesXpath');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');

function templateCommandClass($namespace, $module_name, $command_name)
{
    $command_prefix = 'ps';
    
    $class_file_string = 
'<?php
namespace '.$namespace.'\\'.$module_name.'\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class '.$command_name.' extends Command
{
    protected function configure()
    {
        $this->setName("'.$command_prefix.':'.strToLower($command_name).'");
        $this->setDescription("A command the programmer was too lazy to enter a description for.");
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Hello World");  
    }
} ';
    return $class_file_string;
}

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
* Short Description
* Long
* Description
* @command generate_command
*/
function pestle_cli($argv)
{
    $module_info        = askForModuleAndReturnInfo($argv);
    
    $namespace          = $module_info->vendor;
    $module_name        = $module_info->name;
    $module_shortname   = $module_info->short_name;
    $module_dir         = $module_info->folder;    
    $command_name       = input("Command Name?", 'Testbed');    
        
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
