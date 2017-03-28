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
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');

function createControllerClassName($module, $area='frontend', $controller ='Index', $action='Index')
{
    $class = str_replace('_','\\',$module) . '\Controller';
    if($area === 'adminhtml')
    {
        $class .= '\Adminhtml';
    }
    $class .= '\\' . $controller . '\\' . $action;
    return $class;
}

function getRouterIdFromArea($area)
{
    $legend      = [
        'frontend' =>'standard',
        'adminhtml'=>'admin'
    ];
    $areas       = array_keys($legend);
    if(!in_array($area, $areas))
    {
        throw new Exception("Could not find router id for area");
    }    

    return $legend[$area];    
}

function createRoutesXmlFile($module_info, $area, $frontname, $router_id, $route_id)
{
    $module = $module_info->name;
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
    output($path);
             
    return $xml;
}

function createControllerClassForRoute($module, $area, $controller, $action, $acl)
{
    $class = createControllerClassName($module, $area, $controller, $action, $acl);
    $controllerClass = createControllerClass(
        $class, 
        $area,
        $controller,
        $action
    );    
    $path_controller = getPathFromClass($class);    
    writeStringToFile($path_controller, $controllerClass);
    
    output($path_controller);
}

/**
* Creates a Route XML
* generate_route module area id 
* @command generate-route
* @argument module_name Which Module? [Pulsestorm_HelloWorld]
* @argument area Which Area (frontend, adminhtml)? [frontend]
* @argument frontname Frontname/Route ID? [pulsestorm_helloworld]
* @argument controller Controller name? [Index]
* @argument action action? [Index]
*/
function pestle_cli($argv)
{    
    $module      = $argv['module_name'];
    $area        = $argv['area'];    
    $frontname   = $argv['frontname'];
    $controller  = $argv['controller'];
    $action      = $argv['action'];    
    
    $module_info = getModuleInformation($module);        
    $router_id   = getRouterIdFromArea($area);
    $route_id    = $frontname;

    $xml = createRoutesXmlFile(
        $module_info, $area, $frontname, $router_id, $route_id
    );        
    
    $acl = $module . '::' . $frontname . '_menu';
    createControllerClassForRoute($module, $area, $controller, $action, $acl);
    
    if($area === 'adminhtml')
    {
        output("    Don't forget your menu.xml and acl.xml");
        output('    action="'.$frontname.'/index/index"');
        output('    id="' . $acl);
    }
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}