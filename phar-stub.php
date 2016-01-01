#!/usr/bin/env php
<?php
function main($argv)
{
    Phar::mapPhar('pestle.phar');
    include 'phar://pestle.phar/modules/pulsestorm/pestle/runner/module.php';
    \Pulsestorm\Pestle\Runner\main($argv);
}
main($argv);
__HALT_COMPILER();