<?php
namespace Pulsestorm\Magento2\Cli\Library;
use ReflectionFunction;
use Exception;
use DomDocument;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\isAboveRoot');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\bail');
pestle_import('Pulsestorm\Pestle\Library\getClassFromDeclaration');
pestle_import('Pulsestorm\Pestle\Library\getExtendsFromDeclaration');
pestle_import('Pulsestorm\Pestle\Library\getNewClassDeclaration');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplate');
pestle_import('Pulsestorm\Xml_Library\addSchemaToXmlString');

function getModuleInformation($module_name)
{
    list($vendor, $name) = explode('_', $module_name);        
    return (object) [
        'vendor'        => $vendor,
        'short_name'    => $name,
        'name'          => $module_name,
        'folder'        => getBaseMagentoDir() . "/app/code/$vendor/$name",
    ];
}

function getBaseModuleDir($module_name)
{
    $path = getModuleInformation($module_name)->folder;
    if(!file_exists($path))
    {
        throw new Exception("No such path: $path");
    }
    return $path;
}

function askForModuleAndReturnInfo($argv, $index=0)
{
    $module_name = inputOrIndex(
        "Which module?", 
        'Magento_Catalog', $argv, $index);
    return getModuleInformation($module_name);        
}

function askForModuleAndReturnFolder($argv)
{
    $module_folder = inputOrIndex(
        "Which module?", 
        'Magento_Catalog', $argv, 0);
    list($package, $vendor) = explode('_', $module_folder);        
    return getBaseMagentoDir() . "/app/code/$package/$vendor";
}

function getBaseMagentoDir($path=false)
{
    if($path && isAboveRoot($path))
    {
        output("Could not find base Magento directory");
        exit;
    }

    $path = $path ? $path : getcwd();
    if(file_exists($path . '/app/etc/di.xml'))
    {
        return realpath($path);
    }
    return getBaseMagentoDir($path . '/..');
    // return $path;
}

function getModuleBaseDir($module)
{
    $path = implode('/', [
        getBaseMagentoDir(),
        'app/code',
        str_replace('_', '/', $module)]
    );
    
    return $path;
}

function getModuleConfigDir($module)
{
    return implode('/', [
        getModuleBaseDir($module), 
        'etc']);
}

function initilizeModuleConfig($module, $file, $xsd)
{
    $path = implode('/', [
        getModuleConfigDir($module),
        $file]);
        
    if(file_exists($path))
    {
        return $path;
    }        
    
    $xml = addSchemaToXmlString('<config></config>', $xsd);
    $xml = simplexml_load_string($xml);
            
    if(!is_dir(dirname($path)))
    {
        mkdir(dirname($path), 0777, true);
    }
    writeStringToFile($path, $xml->asXml());

    return $path;
}

function getSimpleTreeFromSystemXmlFile($path)
{
    $tree = [];
    $xml = simplexml_load_file($path);
    foreach($xml->system->section as $section)
    {
        $section_name        = (string) $section['id'];
        $tree[$section_name] = [];

        foreach($section->group as $group)
        {               
            $group_name = (string) $group['id']; 
            $tree[$section_name][$group_name] = [];
            foreach($group->field as $field)
            {
                $tree[$section_name][$group_name][] = (string) $field['id'];
            }
        }
    }
    return $tree;
}



function createClassFile($model_name, $contents)
{
    $path = getBaseMagentoDir() . '/app/code/' .
        str_replace('\\','/',$model_name) . '.php';
    
    if(file_exists($path))
    {
        output($path, "\n" . 'File already exists, skipping');
        return;
    }
    if(!is_dir(dirname($path)))
    {
        mkdir(dirname($path), 0777, true);
    }
    file_put_contents($path, $contents);
}

function resolveAlias($alias, $config, $type='models')
{
    if($type[strlen($type)-1] !== 's')
    {
        $type .='s';
    }
    if(strpos($alias, '/') === false)
    {
        return $alias;
    }
    list($group, $model) = explode('/', $alias);
    $prefix = (string)$config->global->{$type}->{$group}->class;

    $model = str_replace('_', ' ', $model);
    $model = ucwords($model);
    $model = str_replace(' ', '_', $model);

    $mage1 = $prefix . '_' . $model;
    return str_replace('_','\\',$mage1);        
}

function convertObserverTreeScoped($config, $xml)
{        
    $xml_new = simplexml_load_string('<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Event/etc/events.xsd"></config>');
    if(!$config->events)
    {
        return $xml_new;
    }

    foreach($config->events->children() as $event)
    {
        $event_name = modifyEventNameToConvertFromMage1ToMage2($event->getName());
        $event_xml  = $xml_new->addChild('event');
        $event_xml->addAttribute('name',$event_name);
        
        foreach($event->observers->children() as $observer)
        {
            //<observer name="check_theme_is_assigned" instance="Magento\Theme\Model\Observer" method="checkThemeIsAssigned" />
            //shared = false
            $observer_xml = $event_xml->addChild('observer');
            $observer_xml->addAttribute('name', $observer->getName());
            $observer_xml->addAttribute('instance', resolveAlias((string) $observer->{'class'}, $xml));
            $observer_xml->addAttribute('method', (string) $observer->method);
            if( (string) $observer->type === 'model')
            {
                $observer_xml->addAttribute('shared','false');
            }
        }
    }
    
    return $xml_new;
}

function modifyEventNameToConvertFromMage1ToMage2NoAdminhtml($name)
{
    $parts = explode('_', $name);
    $parts = array_filter($parts, function($part){
        return $part !== 'adminhtml';
    });
    return implode('_', $parts);
}

function modifyEventNameToConvertFromMage1ToMage2($name)
{
    $name = modifyEventNameToConvertFromMage1ToMage2NoAdminhtml($name);
    return $name;
}

function getMage1ClassPathFromConfigPathAndMage2ClassName($path, $class)
{
    $path_from_pool = $path;
    $pools = ['community','core','local'];
    foreach($pools as $pool)
    {
        $path_from_pool = preg_replace('%^.*app/code/'.$pool.'/%','',$path_from_pool);
    }
    
    $parts_mage_2 = explode('\\',$class);
    $mage2_vendor = $parts_mage_2[0];
    $mage2_module = $parts_mage_2[1];
    
    $parts_mage_1 = explode('/', $path_from_pool);
    $mage1_vendor = $parts_mage_1[0];
    $mage1_module = $parts_mage_1[1];
    
    if( ($mage1_vendor !== $mage2_vendor) || $mage1_module !== $mage2_module)
    {
        throw new Exception('Config and alias do not appear to match');
    }
    
    $path_from_pool_parts = explode('/',$path);
    $new = [];
    for($i=0;$i<count($path_from_pool_parts);$i++)
    {
        $part = $path_from_pool_parts[$i];
        
        if($part === $mage1_vendor && $path_from_pool_parts[$i+1] == $mage1_module)
        {
            $new[] = str_replace('\\','/',$class) . '.php';
            break;
        }        
        $new[] = $part;
    }
    
    return implode('/',$new);
}

function getVariableNameFromNamespacedClass($class)
{
    $parts = explode('\\', $class);
    $parts = array_slice($parts, 2);
    
    $var = implode('', $parts);
    $var[0] = strToLower($var);
    
    return '$' . $var;
}

function getDiLinesFromMage2ClassName($class)
{
    $var  = getVariableNameFromNamespacedClass($class);
    $parameter  = '\\' . trim($class,'\\') . ' ' . $var . ',';
    $property   = 'protected ' . $var . ';';
    $assignment = '$this->' . ltrim($var, '$') . ' = ' . $var . ';';
    
    $lines = $parameter;
    
    return [
        'property' =>$property,
        'parameter'=>$parameter,
        'assignment'=>$assignment
    ];
}

function getKnownClassMap()
{
    return ['Mage\Core\Helper\Abstract'=>'Magento\Framework\App\Helper\AbstractHelper'];
}

function getKnownClassesMappedToNewClass($return)
{
    $full_class = $return['namespace'] . '\\' . $return['class'];
    $map = getKnownClassMap();
    // echo $full_class,"\n";
    if(!array_key_exists($full_class, $map))
    {
        return $return;
    }
    
    $parts = explode('\\', $map[$full_class]);

    $return = [        
        'class'     =>array_pop($parts),  
        'namespace' =>implode('\\',$parts),

    ];  
    return $return;    
}

function getNamespaceAndClassDeclarationFromMage1Class($class, $extends='')
{
    $parts = explode('_', $class);      
    $return = [        
        'class'     =>array_pop($parts),  
        'namespace' =>implode('\\',$parts),

    ];    
    
    $return = getKnownClassesMappedToNewClass($return);
    
    $return['full_class'] = $return['namespace'] . '\\' . $return['class'];
    return $return;
}

function convertMageOneClassIntoNamespacedClass($path_mage1)
{
    $text = file_get_contents($path_mage1);
    preg_match('%class.+?(extends)?.+?\{%', $text, $m);
    if(count($m) === 0)
    {
        throw new Exception("Could not extract class declaration");
    }
    $declaration = $m[0];
    if(strpos($declaration, 'implements'))
    {
        throw new Exception("Can't handle implements yet, but should be easy to add");
    }
    $class   = getNamespaceAndClassDeclarationFromMage1Class(
        getClassFromDeclaration($declaration));
    $extends = getNamespaceAndClassDeclarationFromMage1Class(
        getExtendsFromDeclaration($declaration)); 
        
    $declaration_new = getNewClassDeclaration($class, $extends);
        
    $text = str_replace($declaration, $declaration_new, $text);
    return $text;
}

function inputModuleName()
{
    return input("Which module?", 'Packagename_Vendorname');
}

/**
* Not a command, just library functions
* @command library
*/
function pestle_cli($argv)
{
}