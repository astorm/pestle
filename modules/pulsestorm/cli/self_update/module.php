<?php
namespace Pulsestorm\Cli\Self_Update;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

define(
    'PESTLE_CURRENT_URL',
    'http://pestle.pulsestorm.net/pestle.phar'
);
function getLocalPharPath()
{
    global $argv;
    $path = realpath($argv[0]);
    return $path;
}

function isPhar($path)
{
    $contents = file_get_contents($path);
    return strpos($contents, '__HALT_COMPILER') !== false;
}
function validateLocalPharPath($path)
{    
    if(!isPhar($path))
    {
        output("$path doesn't look like a phar -- can't update.");
        exit(1);
    }
}

function fetchCurrentAndWriteToTemp()
{
    $contents = file_get_contents(PESTLE_CURRENT_URL);
    $file     = tempnam('/tmp','pestle_');
    file_put_contents($file,$contents);
    output("Downloaded to $file");
    return $file;
}

function backupCurrent($path)
{
    $pathBackup = $path . '.' . time();
    output("Backing up $path to $pathBackup");
    copy($path, $pathBackup);
    if(!file_exists($pathBackup) || !isPhar($pathBackup))
    {
        output("Could not backup to $pathBackup, bailing");
        exit(1);
    }
    output("Backed up current pestle to $pathBackup");
    return $pathBackup;    
}

/**
* Updates the pestle.phar file to the latest version
* @command selfupdate
*/
function pestle_cli()
{
    $localPharPath = getLocalPharPath();    
    $tmpFile       = fetchCurrentAndWriteToTemp();
    
    validateLocalPharPath($localPharPath);      
    backupCurrent($localPharPath);    
    
    //super gross -- thanks PHP
    $permissions = substr(sprintf('%o', fileperms($localPharPath)),-4);
    
    output("Replaced $localPharPath");
    rename($tmpFile, $localPharPath);
    
    chmod($localPharPath, octdec($permissions));
}
