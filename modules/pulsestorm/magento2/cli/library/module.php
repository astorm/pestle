<?php
namespace Pulsestorm\Magento2\Cli\Library;
use ReflectionFunction;
use Exception;
use DomDocument;

function createClassTemplate($class, $extends=false, $implements=false)
{
    $class = trim($class, '\\');
    $parts = explode('\\',$class);
    $name  = array_pop($parts);
    
    $template = '<' . '?' . 'php' . "\n" .
    'namespace ' . implode('\\',$parts) . ";\n" . 
    "class $name";
    if($extends)
    {
        $template .= " extends $extends";
    }
    if($implements)
    {
        $template .= " implements $implements";
    }    
    $template .= "\n" . 
    '{' . '<$body$>' . '}' . "\n";

    return $template;
}

function getXmlNamespaceFromPrefix($xml, $prefix)
{
    $namespaces = $xml->getDocNamespaces();
    if(array_key_exists($prefix, $namespaces))
    {
        return $namespaces[$prefix];
    }

    throw new Exception("Unkonwn namespace in " . __FILE__);
}

function simpleXmlAddNodesXpath($xml, $path)
{
    $path = trim($path,'/');
    $node = $xml;
    foreach(explode('/',$path) as $part)
    {
        $parts = explode('[', $part);
        $node_name = array_shift($parts);
        $is_new_node = true;
        if(isset($node->{$node_name}))
        {
            $is_new_node = false;
            $node = $node->{$node_name};        
        }
        else
        {
            $node = $node->addChild($node_name);
        }
        
        
        $attribute_string = trim(array_pop($parts),']');
        if(!$attribute_string) { continue; }
        $pairs = explode(',',$attribute_string);
        foreach($pairs as $pair)
        {
            if(!$is_new_node) { continue; }
            list($key,$value) = explode('=',$pair);
            if(strpos($key, '@') !== 0)
            {
                throw new Exception("Invalid Attribute Key");
            }
            $key = trim($key, '@');
            if(strpos($key, ':') !== false)
            {                
                list($namespace_prefix, $rest) = explode(':', $key);
                $namespace = getXmlNamespaceFromPrefix($xml, $namespace_prefix);
                $node->addAttribute($key, $value, $namespace);
            }
            else
            {
                $node->addAttribute($key, $value);
            }
            
        }
//         exit;
    }
    return $node;
}

function formatXmlString($string)
{
    $dom = new DomDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;        
    $dom->loadXml($string);
    $string = $dom->saveXml();
    
    $string = preg_replace('%(^\s*)%m', '$1$1', $string);
    
    return $string;
}

function askForModuleAndReturnInfo($argv, $index=0)
{
    $module_name = inputOrIndex(
        "Which module?", 
        'Magento_Catalog', $argv, $index);
    list($vendor, $name) = explode('_', $module_name);        
    return (object) [
        'vendor'        => $vendor,
        'short_name'    => $name,
        'name'          => $module_name,
        'folder'        => getBaseMagentoDir() . "/app/code/$vendor/$name",
    ];
}

function askForModuleAndReturnFolder($argv)
{
    $module_folder = inputOrIndex(
        "Which module?", 
        'Magento_Catalog', $argv, 0);
    list($package, $vendor) = explode('_', $module_folder);        
    return getBaseMagentoDir() . "/app/code/$package/$vendor";
}


function output($string)
{
    foreach(func_get_args() as $arg)
    {
        if(is_object($string) || is_array($string))
        {
            var_dump($string);
            continue;
        }
        echo $arg;
    }    
    echo "\n";
}

function getAtCommandFromDocComment($r)
{
    $props = parseDocCommentAts($r);
    if(array_key_exists('command', $props))
    {
        return $props['command'];
    }
    return null;
}

function inputOrIndex($question, $default, $argv, $index)
{
    if(array_key_exists($index, $argv))
    {
        return $argv[$index];
    }
    
    return input($question, $default);
}

function input($string, $default='')
{
    echo $string . " (".$default.")] ";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);        
    fclose($handle);
    if(trim($line))
    {
        return trim($line);
    }
    return $default;
}

function addSchemaToXmlString($xmlString, $schema=false)
{
    $schema = $schema ? $schema : 
        '../../../../../lib/internal/Magento/Framework/Module/etc/module.xsd';
        
    $xml = str_replace(
        '<config>',
        '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="'.$schema.'">', 
        $xmlString);
    return $xml;
}

function notEmpty($item)
{
    return (boolean) $item;
}

function isAboveRoot($path)
{
    $parts = explode('..', $path);
    $real  = array_shift($parts);
    $parts_real = explode('/',$real);
    array_unshift($parts, '/');
    $parts      = array_filter($parts,      __NAMESPACE__ . '\notEmpty');
    $parts_real = array_filter($parts_real, __NAMESPACE__ . '\notEmpty');
    
    return count($parts) > count($parts_real);
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

function getDocCommentAsString($function)
{
    $r = new ReflectionFunction($function);
    $lines = explode("\n", $r->getDocComment());
    
    if(count($lines) > 2)
    {
        array_shift($lines);
        array_pop($lines);
        $lines = array_map(function($line){
            return trim(trim($line),"* ");
        }, $lines);
    }
    
    return trim( implode("\n", $lines) );
}

/**
* Lists available commands
*/


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




function createBasicClassContents($full_model_name, $method_name, $extends=false)
{
    $parts = explode('\\', $full_model_name);
    $name = array_pop($parts);
    $namespace = implode('\\', $parts);
    $contents =  '<' . '?' . 'php' . "\n";
    $contents .= 'namespace ' . $namespace . ";\n";
    $contents .= 'class ' . $name ;
    $contents .= "\n" . '{' . "\n";
    $contents .= '    public function ' . $method_name . '($parameters)' . "\n";
    $contents .= '    {' . "\n"; 
    $contents .= '        var_dump(__METHOD__); exit;' . "\n";
    $contents .= '    }' . "\n";
    $contents .= '}' . "\n";
    return $contents;
}

function bail($message)
{
    output($message);
    exit(1);
}

function createClassFile($model_name, $contents)
{
    $path = getBaseMagentoDir() . '/app/code/' .
        str_replace('\\','/',$model_name) . '.php';
    
    if(file_exists($path))
    {
        output($path, "\n" . 'File already exists');
        $results = trim(strToLower(input("Continue without overwriting?",'Yes')));
        if(!$results || $results[0] != 'y')
        {
            bail("Bailing");
        }
        return;
    }
    if(!is_dir(dirname($path)))
    {
        mkdir(dirname($path), 0777, true);
    }
    file_put_contents($path, $contents);
}

function writeStringToFile($path, $contents)
{
    if(!is_dir(dirname($path)))
    {
        mkdir(dirname($path),0755,true);
    }
    $path_backup = $path . '.' . uniqid() . '.bak.php';
    if(file_exists($path))
    {
        copy($path, $path_backup);
    }
    file_put_contents($path, $contents);
    return $path;
}




/*
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../lib/internal/Magento/Framework/Module/etc/module.xsd">
    <module name="Pulsestorm_Helloworld" setup_version="0.0.1"></module>
</config>
*/




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
    $xml_new = simplexml_load_string('<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../lib/internal/Magento/Framework/Event/etc/events.xsd"></config>');
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

function getPartFromDeclaration($class, $part)
{
    $flag = false;
    foreach(explode(' ', $class) as $item)
    {
        if($flag)
        {
            return $item;
        }
        if($item === $part)
        {
            $flag = true;
        }
    }
    return null;
}

function getClassFromDeclaration($class)
{
    return getPartFromDeclaration($class, 'class');
}

function getExtendsFromDeclaration($class)
{
    return getPartFromDeclaration($class, 'extends');
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

function getNewClassDeclaration($class, $extends, $include_start_bracket=true)
{
    $parts = [];
    $parts[] = 'namespace';
    $parts[] = $class['namespace'] . ';';
    $parts[] = "\n";

    if($extends['class'])
    {
        $parts[] = 'use';
        if($extends['class'] === 'Abstract')
        {
            $parts[] = $extends['full_class'] . ' as AbstractClass;';
            $extends['class'] = 'AbstractClass';
        }
        else
        {
            $parts[] = $extends['full_class'] .';';
        }
    }
        
    $parts[] = "\n";    
    
    $parts[] = 'class';
    $parts[] = $class['class'];
    if($extends['class'])
    {
        $parts[] = 'extends';    
        $parts[] = $extends['class'];      
    }
    $parts[] = "\n";    
    if($include_start_bracket)
    {
        $parts[] = "{";       
    }
    
    return preg_replace('%^ *%m', '', implode(' ', $parts));
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

function parseDocCommentAts($r)
{
    $comment = $r->getDocComment();
    $comment = preg_replace(['%^\*/%m', '%^/\*\*%m','%^\* %m','%^\*%m'], '', $comment);    
    $parts   = explode('@', $comment);
    array_shift($parts);
    $parsed  = [];
    foreach($parts as $part)
    {
        $part = trim($part);
        $parts2 = preg_split('%\s%', $part);
        $name   = array_shift($parts2);
        $parsed[$name] = implode('',$parts2);
    }
    $parsed = array_map(function($thing){
        return trim($thing);
    }, $parsed);
    
    return $parsed;
}

function getZendPsrLogLevelMap()
{
    return [
        'Zend_Log::EMERG'   => 'Psr\Log\LogLevel::EMERGENCY',   // Emergency: system is unusable
        'Zend_Log::ALERT'   => 'Psr\Log\LogLevel::ALERT',       // Alert: action must be taken immediately
        'Zend_Log::CRIT'    => 'Psr\Log\LogLevel::CRITICAL',    // Critical: critical conditions
        'Zend_Log::ERR'     => 'Psr\Log\LogLevel::ERROR',       // Error: error conditions
        'Zend_Log::WARN'    => 'Psr\Log\LogLevel::WARNING',     // Warning: warning conditions
        'Zend_Log::NOTICE'  => 'Psr\Log\LogLevel::NOTICE',      // Notice: normal but significant condition
        'Zend_Log::INFO'    => 'Psr\Log\LogLevel::INFO',        // Informational: informational messages
        'Zend_Log::DEBUG'   => 'Psr\Log\LogLevel::DEBUG',       // Debug: debug messages    
    ];
}








function inputModuleName()
{
    return input("Which module?", 'Packagename_Vendorname');
}

function templateRegistrationPhp($module_name)
{
    return '<?php
    \Magento\Framework\Component\ComponentRegistrar::register(
        \Magento\Framework\Component\ComponentRegistrar::MODULE,
        \''.$module_name.'\',
        __DIR__
    );';    
}

if ( ! function_exists('glob_recursive'))
{
    // Does not support flag GLOB_BRACE
    
    function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
        {
            $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
        }
        
        return $files;
    }
}

/**
* Not a command, just library functions
* @command library
*/
function pestle_cli($argv)
{
}