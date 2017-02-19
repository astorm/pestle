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

function createHandleFile($module_info, $area, $template, $class, $handle, $layout)
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
    
    $xml['layout'] = $layout;
    if($layout === '' || $area === 'adminhtml')
    {
        unset($xml['layout']);
    }

    $path = $module_info->folder . '/view/' . 
                $area . '/layout/' .  $handle . '.xml';

    output("Creating: $path");
    writeStringToFile($path, $xml->asXml());                   
    
}

function createBlockClass($module_info, $block_name, $area='frontname')
{
    $class_name = str_replace('_', '\\', $module_info->name) . 
        '\Block\\';
    if($area === 'adminhtml')
    {
        $class_name .= 'Adminhtml\\';
    }        
    $class_name .= ucwords($block_name);
    
    output("Creating: " . $class_name);
    $baseClass = '\Magento\Framework\View\Element\Template';
    if($area === 'adminhtml')
    {
        $baseClass = '\Magento\Backend\Block\Template';
    }
    $contents = createClassTemplate($class_name, $baseClass);
    $contents = str_replace('<$body$>', "\n".'    function _prepareLayout(){}'."\n", $contents);
    createClassFile($class_name, $contents);
    return $class_name;
}

/**
* Generates a Magento 2 view
*
* Wrapped by magento:... version of command
*
* @command generate_view
* @argument module_name Which Module? [Pulsestorm_HelloGenerate]
* @argument area Which Area? [frontend]
* @argument handle Which Handle? [<$module_name$>_index_index]
* @argument block_name Block Name? [Main]
* @argument template Template File? [content.phtml]
* @argument layout Layout (ignored for adminhtml) ? [1column]
*/
function pestle_cli($argv)
{
    $module_name    = $argv['module_name'];
    $area           = $argv['area'];
    $handle         = $argv['handle'];
    $block_name     = $argv['block_name'];            
    $template       = $argv['template'];            
    $layout         = $argv['layout'];
    
    $module_info    = getModuleInformation($module_name);

    createTemplateFile($module_info, $area, $template);    
    $class = createBlockClass($module_info, $block_name, $area);
    createHandleFile($module_info, $area, $template, $class, $handle, $layout);
    
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}