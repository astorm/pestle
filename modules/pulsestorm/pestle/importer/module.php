<?php
namespace Pulsestorm\Pestle\Importer;
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
    return include_once('modules/' . $file);
}

function includeCode($namespace, $code)
{    
    $cache_dir = getCacheDir();
    $full_dir  = $cache_dir . '/' . str_replace('\\','/',strToLower($namespace));
    $full_path = $full_dir . '/'  . md5($code) . '.php';
    if(file_exists($full_path))
    {
        include_once $full_path;
        return;
    }
    
    if(!is_dir($full_dir))
    {
        mkdir($full_dir, 0755, true);
    }
    
    file_put_contents($full_path,
        '<' . '?' . 'php' . "\n" . $code);        
    include_once $full_path;  
}

function getCacheDir()
{
    $cache_dir = realpath(__DIR__) . '/../../../../cache';
    if(!is_dir($cache_dir)){ exit("No cache Dir"); }
    return $cache_dir;
}

/**
* @command library
*/
function pestle_cli($argv)
{
}