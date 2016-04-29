<?php
namespace Pulsestorm\Magento2\Cli\Generate\Menu;
use function Pulsestorm\Pestle\Importer\pestle_import;
use stdClass;

pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');
pestle_import('Pulsestorm\PhpDotNet\glob_recursive');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');

function getMenuXmlFiles()
{
    $base = getBaseMagentoDir();
    // $results = `find $base/vendor -name menu.xml`;
    // $results = explode("\n", $results);
    $results = glob_recursive("$base/vendor/menu.xml");            
    $results = array_filter($results);    
    return $results;
}

function inputFromArray($string='Please select the item:',$array)
{
    $num = 0;
    $end = '] ';
    $array = array_map(function($value) use (&$num,$end){        
        $num++;
        $value = '[' . $num . $end . $value;        
        return $value;
    }, $array);
    array_unshift($array, $string);
    
    $sentinal = true;
    while($sentinal)
    {        
        $choice = input(implode("\n",$array) . "\n");
        $choice = (int) $choice;
        if(array_key_exists($choice, $array))
        {            
            $value    = $array[$choice];
            $parts   = explode($end, $value);
            array_shift($parts);
            $value   = array_shift($parts);
            $sentinal = false;
        }
    }
    return $value;
}

function getMenusWithValue($raw, $value)
{
    //get top level menus
    $parents = [];
    $raw = array_filter($raw, function($item) use (&$parents, $value){
        if(trim($item->parent) === $value)
        {
            $parents[] = $item->title . "\t(" . $item->id . ')';
            return false;
        }
        return true;
    });
    return $parents;
}

function choseMenuFromTop()
{
    $files  = getMenuXmlFiles();    
    $raw    = [];             
    foreach($files as $file)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file);    
        libxml_clear_errors();
        libxml_use_internal_errors(false);                                               
        if(!$xml) { continue; }
        foreach($xml->menu->children() as $add)
        {
            $tmp         = new stdClass;
            $tmp->id     = (string) $add['id'];
            $tmp->parent = (string) $add['parent'];
            $tmp->title  = (string) $add['title'];
            $raw[]       = $tmp;
        }
    }
    
    $parents = getMenusWithValue($raw, '');
        
    $value       = parseIdentifierFromLabel(
                    inputFromArray("Select Parent Menu: ", $parents, 1));
    $continue    = input("Use [$value] as parent? (Y/N)",'N');
    if(strToLower($continue) === 'y')
    {
        return $value;
    }
        
    $sections    = getMenusWithValue($raw, $value); 
    $value       = parseIdentifierFromLabel(inputFromArray("Select Parent Menu: ", $sections, 1));    
    return $value;
}

function parseIdentifierFromLabel($string)
{
    $parts = explode("\t", $string);
    $id    = array_pop($parts);
    return trim($id, '()');
}

function selectParentMenu($arguments, $index)
{
    if(array_key_exists($index, $arguments))
    {
        return $arguments[$index];
    }
        
    $parent     = '';
    $continue   = input('Is this a new top level menu? (Y/N)','N');
    if(strToLower($continue) === 'n')
    {
        $parent = choseMenuFromTop();
    }
    return $parent;
}

function loadOrCreateMenuXml($path)
{
    if(!file_exists($path))
    {
        $xml    = simplexml_load_string(getBlankXml('menu'));
        writeStringToFile($path, $xml->asXml());
    }
    $xml    = simplexml_load_file($path);
    return $xml;
}

function addAttributesToXml($argv, $xml)
{
    extract($argv);

    $add    = $xml->menu->addChild('add');    
    $add->addAttribute('id'              , $id);
    $add->addAttribute('resource'        , $resource);        
    $add->addAttribute('title'           , $title); 
    $add->addAttribute('action'          , $action);
    $add->addAttribute('module'          , $module_name);         
    $add->addAttribute('sortOrder'       , $sortOrder);             
    if($parent)
    {
        $parts   = explode('::', $parent);
        $depends = array_shift($parts);
        $add->addAttribute('parent'          , $parent); 
        $add->addAttribute('dependsOnModule' , $depends); 
    }
    return $xml;
}
/**
* One Line Description
*
* @command generate_menu
* @argument module_name Module Name? [Pulsestorm_HelloGenerate]
* @argument parent @callback selectParentMenu
* @argument id Menu Link ID [<$module_name$>::unique_identifier]
* @argument resource ACL Resource [<$id$>]
* @argument title Link Title [My Link Title]
* @argument action Three Segment Action [frontname/index/index]
* @argument sortOrder Sort Order? [10]
*/
function pestle_cli($argv)
{
    extract($argv);
        
    $path = getModuleInformation($module_name)->folder . '/etc/adminhtml/menu.xml';
    $xml  = loadOrCreateMenuXml($path);
    $xml  = addAttributesToXml($argv, $xml);
             
    writeStringToFile($path, $xml->asXml());         
    output("Writing: $path");
    output("Done.");
}
