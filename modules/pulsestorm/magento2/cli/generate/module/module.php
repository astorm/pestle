<?php
namespace Pulsestorm\Magento2\Cli\Generate\Module;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Pestle\Library\writeFormattedXmlStringToFile');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');
pestle_import('Pulsestorm\Xml_Library\addSchemaToXmlString');
pestle_import('Pulsestorm\Cli\Code_Generation\templateRegistrationPhp');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');

function getModuleDir($base_dir, $namespace, $name) {
    $result = getModuleInformation(implode('_', [$namespace,$name]), $base_dir);
    return $result->folder;
}

function getPackageDir($base_dir, $namespace, $name) {
    $result = getModuleInformation(implode('_', [$namespace,$name]), $base_dir);
    return $result->folder_package;
}


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
* @command generate-module
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

    $base_dir    = getBaseMagentoDir();
    $module_dir  = getModuleDir($base_dir, $namespace, $name);
    $package_dir = getPackageDir($base_dir, $namespace, $name);
    $etc_dir     = $module_dir . '/etc';
    $reg_path    = $module_dir . '/registration.php';

    if(is_dir($etc_dir))
    {
        output("Module directory [$etc_dir] already exists");
    } else {
        output("Creating [$etc_dir] ");
        mkdir($etc_dir, 0755, $etc_dir);
    }

    writeFormattedXmlStringToFile($etc_dir . '/module.xml', $xml);
    output("Created: " . $etc_dir . '/module.xml');

    $register = templateRegistrationPhp($full_module_name);
    writeStringToFile($reg_path, $register);
    output("Created: " . $reg_path);
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}

