<?php
namespace Pulsestorm\Pestle\Importer;
use function Pulsestorm\Pestle\Runner\getBaseProjectDir;

function pestle_import($thing_to_import, $as=false)
{
    $thing_to_import = trim($thing_to_import, '\\');
    //include_once 'modules/pulsestorm/magento2/cli/library/module.php';
    $function = extractFunction($thing_to_import);    
    includeCode($thing_to_import, $function);                 
    return true;
}

function extractFunction($function_name)
{
    includeModule($function_name);
    $code = createFunctionForGlobalExport($function_name);
    return $code;
}

function createFunctionForGlobalExport($function_name)
{
    $info = extractFunctionNameAndNamespace($function_name);
    //include in the library
    // $code = '<' . '?php' . "\n" .
    $code = 
    'function ' . $info['short_name'] . '(){
        $args = func_get_args();
        return call_user_func_array(\'\\'.$function_name.'\', $args);
    }';
    return $code;
}

function extractFunctionNameAndNamespace($full)
{
    $full       = strToLower($full);
    $parts      = explode('\\', $full);
    $short_name = array_pop($parts);
    $namespace  = implode('/',$parts);
    
    return [
        'short_name'=>$short_name,
        'namespace' =>$namespace
    ];
}

function includeModule($function_name)
{
    $function_name = strToLower($function_name);
    $parts         = explode('\\', $function_name);
    $short_name    = array_pop($parts);
    $namespace     = implode('/',$parts);
    $file          = $namespace . '/module.php';
    return require_once(getBaseProjectDir() . '/modules/' . $file);
}

function functionCollidesWithPhpGlobalSpace($namespace)
{
    $parts      = explode('\\', $namespace);
    $short_name = array_pop($parts);
    
    $results = in_array($short_name, get_defined_functions()['internal']);
    if($results)
    {
        return $short_name;
    }
    return $results;
}

function includeCode($namespace, $code)
{    
    $cache_dir = getCacheDir();
    $full_dir  = $cache_dir . '/' . str_replace('\\','/',strToLower($namespace));
    $full_path = $full_dir . '/'  . md5($code) . '.php';
    if(file_exists($full_path))
    {
        require_once $full_path;
        return;
    }
    
    if(!is_dir($full_dir))
    {
        mkdir($full_dir, 0755, true);
    }

    if($short_name = functionCollidesWithPhpGlobalSpace($namespace))
    {
        //export with a pestle_prefix
        $code = replaceFirstInstanceOfFunctionName($code, $short_name);
    }
            
    file_put_contents($full_path,
        '<' . '?' . 'php' . "\n" . $code);        
    require_once $full_path;  
}

function replaceFirstInstanceOfFunctionName($code, $short_name)
{
    return str_replace('function ' . $short_name, 
    'function pestle_' . $short_name, $code);    
}

function getCacheDir()
{
    $cache_dir = '/tmp/pestle_cache';
    if(!is_dir($cache_dir)){ 
        mkdir($cache_dir, 0755);
    }
    return $cache_dir;
}

/**
* @command library
*/
function pestle_cli($argv)
{
}