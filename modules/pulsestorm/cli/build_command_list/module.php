<?php
namespace Pulsestorm\Cli\Build_Command_List;
use function Pulsestorm\Pestle\Runner\getCacheDir;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ReflectionFunction;
use function Pulsestorm\Pestle\Runner\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getDocCommentAsString');

function getListOfFilesInModuleFolder()
{
    $path = realpath(__DIR__ . '/../../../../modules/');
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
            include_once $name;
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
        $doc_comment = getDocCommentAsString($function);
        $parts       = explode('@command ', $doc_comment);
        $command     = array_pop($parts);        
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

