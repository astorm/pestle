<?php
namespace Pulsestorm\Cli\Build_Command_List;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ReflectionFunction;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\getDocCommentAsString');
pestle_import('Pulsestorm\Pestle\Importer\getCacheDir');
pestle_import('Pulsestorm\Pestle\Runner\getBaseProjectDir');
pestle_import('Pulsestorm\Pestle\Library\parseDocBlockIntoParts');

function getListOfFilesInModuleFolder()
{
    $path = getBaseProjectDir() . '/modules/';
    $objects = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path), 
        RecursiveIteratorIterator::SELF_FIRST
    );
    return $objects;
}

function includeAllModuleFiles()
{
    $objects = getListOfFilesInModuleFolder();
    // $path = realpath('modules/');
    // $objects = new RecursiveIteratorIterator(
    //     new RecursiveDirectoryIterator($path), 
    //     RecursiveIteratorIterator::SELF_FIRST
    // );
    foreach($objects as $name => $object){
        $info = pathinfo($name);        
        if($info['basename'] == 'module.php')
        {
            require_once $name;
        }
    }

}

function buildCommandList()
{
    includeAllModuleFiles();
    
    $functions = get_defined_functions();
    $lookup    = [];
    foreach($functions['user'] as $function)
    {
        if(strpos($function, 'pestle_cli') === false)
        {
            continue;
        }
        $r = new ReflectionFunction($function);
        // $doc_comment        = getDocCommentAsString($function);
        $parsed_doc_command = parseDocBlockIntoParts($r->getDocComment());
        
        $command = array_key_exists('command', $parsed_doc_command) 
            ? $parsed_doc_command['command'] : ['pestle-none-set'];

        $command = array_shift($command);            

        $lookup[$command] = $r->getFilename();
    }
    cacheCommandList($lookup);
    return $lookup;
}

function cacheCommandList($lookup)
{
    $cache_dir = getCacheDir();
    file_put_contents($cache_dir . '/command-list.ser', serialize($lookup));
}

/**
* Converts a markdown files to an aiff
* @command build_command_list
*/
function pestle_cli($argv)
{
    $lookup = buildCommandList();
    foreach($lookup as $command=>$file)
    {
        output($command);
    }
    
}

