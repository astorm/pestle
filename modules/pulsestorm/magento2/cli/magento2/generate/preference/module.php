<?php
namespace Pulsestorm\Magento2\Cli\Magento2_Generate_Preference;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplate');

function loadOrCreateDiXml($module_info)
{
    $path_di = $module_info->folder . '/etc/di.xml';
    if(!file_exists($path_di))
    {
        $xml =  simplexml_load_string(getBlankXml('di'));           
        writeStringToFile($path_di, $xml->asXml());
        output("Created new $path_di");
    }    
    $xml            =  simplexml_load_file($path_di);       
    return [
        'path'=>$path_di,
        'xml'=>$xml
    ];
}

function generateDiConfiguration($argv)
{
    $moduleInfo        = getModuleInformation($argv['module']);
    $pathAndXml        = loadOrCreateDiXml($moduleInfo);
    $path              = $pathAndXml['path'];
    $di_xml            = $pathAndXml['xml'];

    $preference         = $di_xml->addChild('preference');
    $preference['for']  = $argv['for'];
    $preference['type'] = $argv['type'];
    
    writeStringToFile($path, formatXmlString($di_xml->asXml()));    

}

function isTypeInterface($type)
{
    //string detection for now -- change to actually examine system?
    return strpos($type, 'Interface') !== false;
}

function generateNewClass($argv)
{    
    $pathType       = getPathFromClass($argv['type']);  
    
    $typeGlobalNs   = '\\' . trim($argv['for'],'\\');
    $classContents  = createClassTemplate($argv['type'], $typeGlobalNs);        
    if(isTypeInterface($typeGlobalNs))
    {
        $classContents  = createClassTemplate($argv['type'], null, $typeGlobalNs);
    }
    
    $classContents  = str_replace('<$body$>', '',$classContents);
    
    if(!file_exists($pathType))
    {
        output("Creating $pathType");
        writeStringToFile($pathType, $classContents);
    }
    else
    {
    output("$pathType already exists, skipping creation");
    }
}

/**
* Generates a Magento 2.1 ui grid listing and support classes.
*
* @command magento2:generate:preference
* @argument module Which Module? [Pulsestorm_Helloworld]
* @argument for For which Class/Interface/Type? [Pulsestorm\Helloworld\Model\FooInterface]
* @argument type New Concrete Class? [Pulsestorm\Helloworld\Model\NewModel]
*/
function pestle_cli($argv)
{    
    generateDiConfiguration($argv);
    generateNewClass($argv);

    // output("Created file $path_plugin");       
    // output("This command will add to di.xml (create if needed)");
    // output("This command will also generate a class");
    // output("If passed an interface, class will implement");
    // output("If passed a class, class will extend");
    // output("Simple text matching for interface detection?");
//     generateUiComponentXmlFile(
//         $argv['grid_id'], $argv['db_id_column'], $module_info);                                        
//         
//     generateDataProviderClass(
//         $module_info, $argv['grid_id'], $argv['collection_resource'] . 'Factory');
//         
//     generatePageActionClass(
//         $module_info, $argv['grid_id'], $argv['db_id_column']);                    
//         
//     output("Don't forget to add this to your layout XML with <uiComponent name=\"{$argv['grid_id']}\"/> ");        
}
