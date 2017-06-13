<?php
namespace Pulsestorm\Magento2\Generate\Classchild;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');

pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse');
pestle_import('Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass');
pestle_import('Pulsestorm\Cli\Token_Parse\getFunctionFromClass');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');
pestle_import('Pulsestorm\Cli\Token_Parse\pestle_token_get_all');
pestle_import('Pulsestorm\Cli\Token_Parse\extractClassInformationFromClassContents');
pestle_import('Pulsestorm\Cli\Token_Parse\extractVariablesFromConstructor');
pestle_import('Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass');

function getPathFromClassIncludingVendor($class)
{
    $path = getPathFromClass($class);
    if(file_exists($path))
    {
        return $path;
    }
    $psr4 = include (getBaseMagentoDir() . '/vendor/composer/autoload_psr4.php');

    $parts  = explode('\\', $class);        
    $prefix = ($parts[0] . '\\' . $parts[1] . '\\');
    $prefix = array_shift($parts) . '\\' . array_shift($parts) . '\\';        
    
    $paths = [];
    foreach($psr4 as $key=>$value)
    {    
        if(strpos($key, $prefix) !== false)
        {
            $paths[] = $value;
        }
    }

    foreach($paths as $key=>$value)
    {
        foreach($value as $key=>$file)
        {
            $path = $file . '/' . implode('/', $parts) . '.php';
            if(file_exists($path))
            {
                return $path;
            }
        }        
    }    

    // var_dump($class);
    // exit;
    throw new \Exception("Could not find path");
}

function getParentClassFromClassContents($contents)
{
    $information = extractClassInformationFromClassContents($contents);    
    return $information['full-extends'];
}

function getConstructorFromParentClass($class)
{
    $path     = getPathFromClassIncludingVendor($class);
    $path     = str_replace('app/code/Magento/Framework','vendor/magento/framework', $path);
    $contents = file_get_contents($path);
    $function = getFunctionFromClass($contents,'__construct');
    if(!$function)
    {
        $parentClass = getParentClassFromClassContents($contents);
        if(!$parentClass){ return '';}
        return getConstructorFromParentClass($parentClass);
    }

    $function = preg_replace('%{.*}%s','{<%body%>}', $function);
    
    $variables = extractVariablesFromConstructor($function);
    $parentCall = '    parent::__construct(' . implode(',', $variables) . ');';

    $function = str_replace('{<%body%>}',"{\n    $parentCall\n    }", $function);        
        
    return '    ' . $function;
}

/**
* Generates a child class, pulling in __constructor for easier di
*
* @command magento2:generate:class-child
* @argument class_child New Class Name? [Pulsestorm\Helloworld\Model\Something]
* @argument class_parent Parent Class? [Magento\Framework\Model\AbstractModel]
*/
function pestle_cli($argv)
{       
    $class = createClassTemplateWithUse($argv['class_child'], '\\' . $argv['class_parent']);
    
    $class = str_replace('<$use$>', '', $class);
    $class = str_replace(
        '<$body$>', 
        "\n" . getConstructorFromParentClass($argv['class_parent']) . "\n", 
        $class);

    $path = getPathFromClass($argv['class_child']);
    if(!file_exists($path))
    {
        writeStringToFile($path, $class);
        return;
    }
    output("Class File Already Exists, but here's the constructor");        
    output($class);
}
