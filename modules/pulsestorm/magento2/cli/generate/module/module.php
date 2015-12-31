<?php
namespace Pulsestorm\Magento2\Cli\Generate\Module;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\inputOrIndex');
pestle_import('Pulsestorm\Magento2\Cli\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');
pestle_import('Pulsestorm\Magento2\Cli\Library\addSchemaToXmlString');
pestle_import('Pulsestorm\Magento2\Cli\Library\templateRegistrationPhp');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');

/**
* Generates new module XML, adds to file system
* Long
* Description
* @todo generate_registration
* @command generate_module
*/
function pestle_cli($argv)
{
    // $namespace = input("Namespace?",'Pulsestorm');
    // $name = input("Module Name?",'Helloworld');
    // $version = input("Version?",'0.0.1');

    $namespace = inputOrIndex("Namespace?",'Pulsestorm', $argv, 0);
    $name      = inputOrIndex("Module Name?",'Helloworld', $argv, 1);
    $version   = inputOrIndex("Version?",'0.0.1', $argv, 2);
    
    $full_module_name = implode('_', [$namespace, $name]);    
    
    $config = simplexml_load_string(getBlankXml('module'));
    $module = $config->addChild('module');
    $module->addAttribute('name'         , $full_module_name);
    $module->addAttribute('setup_version', $version);
    $xml = $config->asXml();
    // $xml = addSchemaToXmlString($xml);
    
    $base_dir = getBaseMagentoDir();
    $module_dir = implode('/',[$base_dir, 'app/code', $namespace, $name, 'etc']);
    
    if(is_dir($module_dir))
    {
        output("Module directory [$module_dir] already exists, bailing");
        return;
    }
    
    mkdir($module_dir, 0777, $module_dir);
    writeStringToFile($module_dir . '/module.xml', $xml);
    output("Created: " . $module_dir . '/module.xml');
    
    $register = templateRegistrationPhp($full_module_name);    
    writeStringToFile($module_dir . '/../registration.php', $register);
    output("Created: " . $module_dir . '/../registration.php');    
}