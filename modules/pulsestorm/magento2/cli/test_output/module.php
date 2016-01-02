<?php
namespace Pulsestorm\Magento2\Cli\Test_Output;
use function Pulsestorm\Pestle\Importer\pestle_import;

/**
* One Line Description
*
* @command test_output
*/
function pestle_cli($argv)
{
    output("Hello Sailor");
}

function output()
{
    echo "I am hard coded and here for a test.";
}