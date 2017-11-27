<?php
namespace Pulsestorm\Alanstormdotcom\Rsync;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

function reduceFolders($folders, $pattern)
{
    $folders = array_filter($folders, function($item) use ($pattern){
        return preg_match('%^'.$pattern.'$%',$item);
    });    
    if(count($folders) == 0)
    {
        exitWithErrorMessage("No 20xx folders found.");
    }
    return array_values($folders);
}

function getMostRecentStaticFolder($staticFolders, $path)
{
    $lastTime = filemtime($path . '/' . $staticFolders[0]);
    $lastFolder = $staticFolders[0];
    foreach($staticFolders as $folder)
    {
        if($lastTime < filemtime($path . '/' . $folder))
        {
            $lastFolder = $folder;
            $lastTime = filemtime($path . '/' . $folder);
        }        
    }
    return $lastFolder;
}

function appendMax($string, $items)
{
    return $string . '/' . max($items);
}

/**
* One Line Description
*
* @command alanstormdotcom:rsync
* @argument base_folder Base Folder? [.]
* @argument remote_server Remote SSH Addres and Folder? [.]
*/
function pestle_cli($argv)
{
    if('/' != $argv['remote_server'][strlen($argv['remote_server'])-1])
    {
        exitWithErrorMessage("Remote Server must end in /");
    }
    $folders = reduceFolders(scandir($argv['base_folder']), '20\d\d');
        
    $path    = appendMax($argv['base_folder'], $folders);        
    $dayFolders = reduceFolders(scandir($path), '\d\d');
    
    $maxDay = max($dayFolders);
    $path   = $path . '/' . $maxDay;
    $staticFolders = reduceFolders(
        scandir($path), 'wp-static-html-output-1-\d{10}-');
    
    $lastFolder = getMostRecentStaticFolder($staticFolders, $path);
    $path       = $path . '/' . $lastFolder . '/';
    $cmd = ('rsync -r ' . 
        $path . ' ' .
        $argv['remote_server']);

    output($cmd);
    output(`$cmd`);                
}
