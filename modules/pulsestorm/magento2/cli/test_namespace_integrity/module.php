<?php
namespace Pulsestorm\Magento2\Cli\Test_Namespace_Integrity;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
pestle_import('pulsestorm\cli\build_command_list\getListOfFilesInModuleFolder');

function getPhpModuleFiles()
{
    $files = getListOfFilesInModuleFolder();
    $items = [];
    foreach($files as $name=>$file)
    {
        $info = pathinfo($name);
        if($info['basename'] !== 'module.php') { continue; }
        $items[] = $name;
    } 
    return $items;
}

function parseNamespaceFromString($string)
{
    preg_match('%namespace (.+?);%six',$string, $matches);
    return trim($matches[1]);
}

function parseNamespaceFromFile($file)
{
    return parseNamespaceFromString(file_get_contents($file));
}

function parseCommandFromString($string)
{
    preg_match('%^\*.+?@command(.+?)[\r\n]%mix', $string, $matches);
    return trim($matches[1]);
}

function parseCommandFromFile($file)
{
    return parseCommandFromString(file_get_contents($file));
}

function reportOnNamespaceAndFilepath($namespace, $command, $file)
{
    $parts = explode('/modules/', $file);
    $file_path_ns = str_replace('/module.php', '', array_pop($parts));
    $file_path_ns = str_replace('/','\\',$file_path_ns);
    if(strToLower($file_path_ns) !== strToLower($namespace))
    {
        output('--------------------------------------------------');
        output($file_path_ns);
        output($file);
        output($namespace);
        output($command);
        output('--------------------------------------------------'); 
    }
}

function reportOnNamespaceAndCommandName($namespace, $command, $file)
{
    $parts = explode('\\', $namespace);
    $last_namespace = strToLower(array_pop($parts));
    $second_last_namespace = strToLower(array_pop($parts));
    
    if( ($last_namespace !== $command) && 
        (($second_last_namespace . '_' . $last_namespace) !== $command) &&
        $command !== 'library')
    {        
        output('--------------------------------------------------');
        output($file);
        output($namespace);        
        output($last_namespace);
        output($command);
        output('--------------------------------------------------');    
    }
}

function extractPestleImports($namespace, $command, $file)
{
    $contents = php_strip_whitespace(($file));
    preg_match_all('%pestle_import.*?\((.+?)\).*?;%',$contents, $matches);
    $namespaces_in_file = array_map(function($item) use ($file){        
        $item = str_replace(["'",'"'], '', $item);
        if($item === '$files as $file')
        {
            // exit($file);
        }
        return $item;
    }, $matches[1]);
    
    $namespaces_in_file = array_filter($namespaces_in_file, function($item){
//         return !in_array($item, ['(.+?', '$files as $file','$all_pestle_imports, extractPestleImports($namespace, $command, $file',
//             '$namespace, $command, $file]'
            return 
                (strpos($item, 'pulsestorm') === 0) || 
                (strpos($item, 'Pulsestorm') === 0);
    });
    return $namespaces_in_file;
    // exit($contents);
}

/**
* One Line Description
*
* @command test_namespace_integrity
*/
function pestle_cli($argv)
{
    $files              = getPhpModuleFiles(); 
    $all_pestle_imports = [];  
    foreach($files as $file)
    {
        include_once $file;
        $namespace  = parseNamespaceFromFile($file);
        $command    = parseCommandFromFile($file);
        reportOnNamespaceAndCommandName($namespace, $command, $file);
        reportOnNamespaceAndFilepath($namespace, $command, $file);
        $all_pestle_imports = array_merge($all_pestle_imports, 
            extractPestleImports($namespace, $command, $file));
    }
    $all_pestle_imports = array_unique($all_pestle_imports);
    foreach($all_pestle_imports as $import)
    {
        output($import);
        if(!function_exists($import))
        {
            output("No such function $import, used in pestle_import somewhere");
        }
        pestle_import($import);
    }

    output("Test Complete");
}
