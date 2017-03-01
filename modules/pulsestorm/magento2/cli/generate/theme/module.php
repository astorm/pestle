<?php
namespace Pulsestorm\Magento2\Cli\Generate\Theme;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');
pestle_import('Pulsestorm\Xml_Library\simpleXmlAddNodesXpath');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Cli\Code_Generation\templateRegistrationPhp');

function createFrontendFolders($base_folder, $package, $theme, $area)
{
    //web/css/source
    //fonts
    //images
    //js
    
    $folders = [
        $base_folder . '/web/css/source',
        $base_folder . '/fonts',
        $base_folder . '/images',
        $base_folder . '/js',                        
    ];
    
    foreach($folders as $folder)
    {
        if(!is_dir($folder))
        {
            output("Creating: $folder");
            mkdir($folder,0755,true);
        }
        else
        {
            output("Exists: $folder");
        }
    }
}

function createThemeXmlFile($base_folder, $package, $theme, $area, $parent_name)
{
    $path = $base_folder . '/theme.xml';    
    $xml  = simplexml_load_string(getBlankXml('theme'));
    
    $title  = simpleXmlAddNodesXpath($xml, 'title');
    dom_import_simplexml($title)->nodeValue = ucwords($package . ' ' . $theme);
    
    if($parent_name)
    {
        $parent = simpleXmlAddNodesXpath($xml, 'parent');
        dom_import_simplexml($parent)->nodeValue = $parent_name;
    }
    $image  = simpleXmlAddNodesXpath($xml, 'media/preview_image');
    
    output("Creating: $path");
    writeStringToFile($path, formatXmlString($xml->asXml()));
}

function createRegistrationPhpFile($base_folder, $package, $theme, $area)
{
    $path = $base_folder . '/registration.php';    
    $registration_string = $area . '/' . $package . '/' . $theme;
    $registration = templateRegistrationPhp($registration_string, 'THEME');
    
    output("Creating: $path");
    writeStringToFile($path, $registration);

}

function createViewXmlFile($base_folder, $package, $theme, $area)
{
    $path  = $base_folder . '/etc/view.xml'; 
    $xml   = simplexml_load_string(getBlankXml('view')); 
    $media = simpleXmlAddNodesXpath($xml, 'media');
    output("Creating: $path");
    writeStringToFile($path, formatXmlString($xml->asXml()));    
}
/**
* Generates Magento 2 theme configuration
*
* Wrapped by magento:foo:baz ... version of command
*
* @command generate-theme
* @argument package Theme Package Name? [Pulsestorm]
* @argument theme Theme Name? [blank]
* @argument area Area? (frontend, adminhtml) [frontend]
* @argument parent Parent theme (enter 'null' for none) [Magento/blank]
*
*/
function pestle_cli($argv)
{
    $package = $argv['package'];
    $theme   = $argv['theme'];    
    $area    = $argv['area'];
    $parent  = $argv['parent'];
    if(strpos($parent, 'null') !== false)
    {
        $parent = '';
    }
    $base_folder = getBaseMagentoDir() . '/app/design' . '/' .
        $area . '/' . $package . '/' . $theme;

    createThemeXmlFile($base_folder, $package, $theme, $area, $parent);
    createRegistrationPhpFile($base_folder, $package, $theme, $area);
    createViewXmlFile($base_folder, $package, $theme, $area);
    createFrontendFolders($base_folder, $package, $theme, $area);
    //theme.xml
    //registration.php
    //view.xml

    
    
                
    output($base_folder);
    output("Done");
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}