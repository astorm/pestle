<?php
namespace Pulsestorm\Magento2\Cli\Search_Controllers;
use function Pulsestorm\Pestle\Importer\pestle_import;

pestle_import('Pulsestorm\Magento2\Cli\Library\inputOrIndex');
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\glob_recursive');
pestle_import('Pulsestorm\Cli\Token_Parse\getFunctionFromClass');



function getAllControllerFiles($base)
{
    $files = glob($base . '/*');
    $controllers = array_filter($files, function($item){
        return is_dir($item . '/Controller/');
    });
    $controllers = array_map(function($item){
        return glob_recursive($item . '/Controller/*.php');
    }, $files);    
    
    return $controllers;
}

function getControllersWithExecuteMethod($controllers)
{
    $return = [];
    foreach($controllers as $key=>$items)
    {
        foreach($items as $item)
        {
            $contents = file_get_contents($item);
            if(strpos($contents, 'execute') !== false)
            {
                $return[$item] = $contents;
            }
        }
    }
    
    return $return;

}

function getExecuteMethods($controllers)
{
    foreach($controllers as $file=>$contents)
    {
        $execute = getFunctionFromClass($contents, 'execute');
        output($file);
        output('--------------------------------------------------');
        output($execute);
        output('');
        
    }
}

/**
* Searches controllers
* @command search_controllers
*/
function pestle_cli($argv)
{
    $base = inputOrIndex("Which folder to search?",'vendor/magento',$argv,0);
    $controllers = getAllControllerFiles($base);
    $controllers = getControllersWithExecuteMethod($controllers);
    $controllers = getExecuteMethods($controllers);
}
