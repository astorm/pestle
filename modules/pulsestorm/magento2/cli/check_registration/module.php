<?php
namespace Pulsestorm\Magento2\Cli\Check_Registration;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Cli\Code_Generation\templateRegistrationPhp');
/**
* Short Description
* Long
* Description
* @command check_registration
*/
function pestle_cli($argv)
{
    $path = 'app/code';
    if(count($argv) > 0)
    {
        $Path = $argv[0];
    }
    
    foreach(glob($path . '/*/*') as $file)
    {
        $parts = explode('/', $file);
        $module = implode('_', array_slice($parts, count($parts) - 2));
        
        $file = $file . '/' . 'registration.php';
        if(file_exists($file))
        {
            output("Registration Exists");
            $contents = file_get_contents($file);
            if(strpos($contents, "'" . $module . "'") !== false)
            {
                output("Registration contains $module string");
                continue;
            }
            output("However, it's missing single quoted '$module' string");
            output("");
            continue;            
        }
        output("No $file");
        $answer = input("Create? [Y/n]", 'n');
        if($answer !== 'Y')
        {
            continue;
        }
        file_put_contents($file, templateRegistrationPhp($module));
        output("Created $file");
        output("");
    }
}
