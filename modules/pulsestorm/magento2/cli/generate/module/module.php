<?php
namespace Pulsestorm\Magento2\Cli\Generate\Module;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');
pestle_import('Pulsestorm\Magento2\Cli\Library\addSchemaToXmlString');
pestle_import('Pulsestorm\Magento2\Cli\Library\templateRegistrationPhp');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');

/**
* Generates new module XML, adds to file system
* This command generates the necessary files and configuration
* to add a new module to a Magento 2 system.
*
*    pestle.phar Pulsestorm TestingCreator 0.0.1
*
* @argument namespace Vendor Namespace? [Pulsestorm]
* @argument name Module Name? [Testbed]
* @argument version Version? [0.0.1]
* @command generate_module
*/
function pestle_cli($argv)
{
    $namespace = $argv['namespace'];
    $name      = $argv['name'];
    $version   = $argv['version'];
    
    $full_module_name = implode('_', [$namespace, $name]);    
    
    $config = simplexml_load_string(getBlankXml('module'));
    $module = $config->addChild('module');
    $module->addAttribute('name'         , $full_module_name);
    $module->addAttribute('setup_version', $version);
    $xml = $config->asXml();
    
    $base_dir   = getBaseMagentoDir();
    $module_dir = implode('/',[$base_dir, 'app/code', $namespace, $name]);    
    $etc_dir    = $module_dir . '/etc';
    $reg_path   = $module_dir . '/registration.php';
    
    if(is_dir($etc_dir))
    {
        output("Module directory [$etc_dir] already exists, bailing");
        return;
    }
    
    mkdir($etc_dir, 0777, $etc_dir);
    writeStringToFile($etc_dir . '/module.xml', $xml);
    output("Created: " . $etc_dir . '/module.xml');
    
    $register = templateRegistrationPhp($full_module_name);    
    writeStringToFile($reg_path, $register);
    output("Created: " . $reg_path);    
}