<?php
namespace Pulsestorm\Magento2\Cli\Generate\Route;
use function Pulsestorm\Pestle\Importer\pestle_import;
use Exception;
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\askForModuleAndReturnInfo');
pestle_import('Pulsestorm\Xml_Library\simpleXmlAddNodesXpath');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');
pestle_import('Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass');
pestle_import('Pulsestorm\Cli\Code_Generation\createControllerClassTemplate');
pestle_import('Pulsestorm\Cli\Code_Generation\createControllerClass');

/**
* Creates a Route XML
* generate_route module area id 
* @command generate_route
*/
function pestle_cli($argv)
{    
    $module_info = askForModuleAndReturnInfo($argv);
    $module      = $module_info->name;
    
    $legend      = [
        'frontend'=>'standard',
        'adminhtml'=>'admin'
    ];
    $areas       = array_keys($legend);
    $area        = inputOrIndex(
        'Which area? [frontend, adminhtml]','frontend',
        $argv, 1
    );    
    
    $router_id   = $legend[$area];
    
    if(!in_array($area, $areas))
    {
        throw new Exception("Invalid areas");
    }
    
    $frontname   = inputOrIndex(
        'Frontname/Route ID?', null,
        $argv, 2
    );
    $route_id    = $frontname;

    $path = $module_info->folder . '/etc/'. $area . '/routes.xml';
    if(!file_exists($path))
    {
        $xml = simplexml_load_string(getBlankXml('routes'));
        writeStringToFile($path, $xml->asXml());
    }

    $xml = simplexml_load_file($path);
        
    simpleXmlAddNodesXpath($xml,
        "router[@id=$router_id]/" .
        "route[@id=$route_id,@frontName=$frontname]/" .
        "module[@name=$module]");
    
    writeStringToFile($path, formatXmlString($xml->asXml()));
    
    $class = str_replace('_','\\',$module) . '\Controller\Index\Index';
    $controllerClass = createControllerClass(
        $class, 
        $area
    );
    
    $path_controller = getPathFromClass($class);
    
    writeStringToFile($path_controller, $controllerClass);
    output($path);   
    output($path_controller);
}