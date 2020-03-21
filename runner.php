<?php
function main($argv)
{

    if(!file_exists( __DIR__ . '/vendor/autoload.php')){
        echo("\033[31mDid you forget to run 'composer install'?\n\033[0m");
    }
    include 'modules/pulsestorm/pestle/runner/module.php';
    \Pulsestorm\Pestle\Runner\main($argv);
}
main($argv);
