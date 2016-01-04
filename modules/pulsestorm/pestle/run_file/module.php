<?php
namespace Pulsestorm\Pestle\Runfile\Run_File;
/**
* One Line Description
*
* @command pestle_run_file
* @argument file Run which file?
*/
function pestle_cli($argv)
{
    require_once($argv['file']);
}
