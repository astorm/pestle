<?php

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
$parts = explode('/', $file);
$last  = array_pop($parts);
if(strpos($last, 'phpunit') !== false)
{
    return;
}

include __DIR__ . '/all.php';