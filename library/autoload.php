<?php
//this files pulls in the pestle PHP library, unless we're running in 
//test mode, from travis, or via the phar.  It is ugly and we appologize.

//travis run
if(isset($_SERVER['PULSESTORM_COMPOSER_REPOSITORY_TO_TEST']))    //should the autoloader bail?
{
    return;
}

//local vendor/bin/phpunit run
//phpunit.phar run
$backtrace = debug_backtrace();
$top       = array_pop($backtrace);
$file      = '';
if(isset($top['file']))
{ 
    $file = strToLower($top['file']);
}
$parts = explode(DIRECTORY_SEPARATOR, $file);
$last  = array_pop($parts);
if(strpos($last, 'phpunit') !== false)
{
    return;
}

//running as pestle_dev, pestle.phar, or pestle
global $argv;
if(isset($argv[0]))
{    
    $parts = explode(DIRECTORY_SEPARATOR, $argv[0]);
    $last = strToLower(array_pop($parts));
    if(in_array($last, ['pestle', 'pestle_dev', 'pestle.phar']))
    {
        return;
    }
}

include __DIR__ . '/all.php';
