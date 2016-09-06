<?php
if(isset($_SERVER['PULSESTORM_COMPOSER_REPOSITORY_TO_TEST'])    //should the autoloader bail?
{
    return;
}
include __DIR__ . '/all.php';