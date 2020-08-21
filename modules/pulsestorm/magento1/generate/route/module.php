<?php
namespace Pulsestorm\Magento1\Generate\Route;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Magento1\Generate\Library\getPathModule');
pestle_import('Pulsestorm\Magento1\Generate\Library\getPathModuleConfigFile');
pestle_import('Pulsestorm\Magento1\Generate\Library\createFrontendController');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Xml_Library\simpleXmlAddNodesXpath');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplate');

/**
* Generates frontend route configuration and index controller
*
* @command magento1:generate:route
* @argument full_module_name Full Module Name [Pulsestorm_Helloworld]
*/
function pestle_cli($argv)
{
    $pathConfig = getPathModuleConfigFile($argv['full_module_name']);
    $config = simplexml_load_file($pathConfig);

    $fullNameLc = strToLower($argv['full_module_name']);
    if(isset($config->frontend->routers->{$fullNameLc})) {
        output('frontend/routers/' . $fullNameLc . ' already exists');
        exit;
    }

    $xpathModuleRouters = 'frontend/routers/' . strToLower($argv['full_module_name']);
    $xmlModuleRouters = simpleXmlAddNodesXpath($config, $xpathModuleRouters);
    $xmlUse = simpleXmlAddNodesXpath($config, $xpathModuleRouters . '/use');
    simpleXmlAddNodesXpath($config, $xpathModuleRouters . '/args');
    $xmlModule = simpleXmlAddNodesXpath($config, $xpathModuleRouters . '/args/module');
    $xmlFrontName = simpleXmlAddNodesXpath($config, $xpathModuleRouters . '/args/frontName');

    $xmlUse[0] = 'standard';
    $xmlModule[0] = $argv['full_module_name'];
    $xmlFrontName[0] = strToLower($argv['full_module_name']);

    writeStringToFile(
        $pathConfig,
        formatXmlString($config->asXml())
    );

    createFrontendController(
        $argv['full_module_name'],
        'pulsestorm_simplerest/index/index'
    );

    output('Done');
}
