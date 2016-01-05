<?php
namespace Pulsestorm\Magento2\Cli\Generate\View;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Pestle\Library\getShortClassNameFromClass');
pestle_import('Pulsestorm\Magento2\Cli\Library\createClassFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');
pestle_import('Pulsestorm\Xml_Library\simpleXmlAddNodesXpath');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplate');


function createTemplateFile($module_info, $area, $template)
{
    $path = $module_info->folder . '/view/' . 
                $area . '/templates/' .  $template;

    output("Creating $path");
    writeStringToFile($path, '<h1>This is my template, there are many like it, but this one is mine.</h1>');                
    
}

function createHandleFile($module_info, $area, $template, $class, $handle)
{
    $xml = simplexml_load_string(getBlankXml('layout_handle'));
    $name  = strToLower($module_info->name) . 
        '_block_' . 
        strToLower(getShortClassNameFromClass($class));
    
    simpleXmlAddNodesXpath($xml,
        'referenceBlock[@name=content]/block[' . 
        '@template=' . $template . ',' .
        '@class='    . $class    . ',' .
        '@name='     . $name     . ']'
    );
    
    $path = $module_info->folder . '/view/' . 
                $area . '/layout/' .  $handle . '.xml';

    writeStringToFile($path, $xml->asXml());                   
    // output($xml->asXml());
}

function createBlockClass($module_info, $block_name)
{
    $class_name = str_replace('_', '\\', $module_info->name) . 
        '\Block\\' . ucwords($block_name);
    
    output("Creating: " . $class_name);
    $contents = createClassTemplate($class_name, '\Magento\Framework\View\Element\Template');
    $contents = str_replace('<$body$>', "\n".'    function _prepareLayout(){}'."\n", $contents);
    createClassFile($class_name, $contents);
    return $class_name;
}

/**
* One Line Description
*
* @command generate_view
* @argument module_name Which Module? [Pulsestorm_HelloGenerate]
* @argument area Which Area? [frontend]
* @argument handle Which Handle? [<$module_name$>_index_index]
* @argument block_name Block Name? [Main]
* @argument template Template File? [content.phtml]
*/
function pestle_cli($argv)
{
    $module_name    = $argv['module_name'];
    $area           = $argv['area'];
    $handle         = $argv['handle'];
    $block_name     = $argv['block_name'];            
    $template       = $argv['template'];            
    
    $module_info    = getModuleInformation($module_name);

    createTemplateFile($module_info, $area, $template);    
    $class = createBlockClass($module_info, $block_name);
    createHandleFile($module_info, $area, $template, $class, $handle);
    
}
