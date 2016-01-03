<?php
namespace Pulsestorm\Magento2\Cli\Generate\Registration;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\templateRegistrationPhp');
pestle_import('Pulsestorm\Pestle\Library\inputModuleName');
pestle_import('Pulsestorm\Magento2\Cli\Library\inputModuleName');

/**
* Generates registration.php
* This command generates the PHP code for a Magento module registration.php file.
* 
*     $ pestle_dev generate_registration Foo_Bar
*     <?php
*         \Magento\Framework\Component\ComponentRegistrar::register(
*             \Magento\Framework\Component\ComponentRegistrar::MODULE,
*             'Foo_Bar',
*             __DIR__
*         );
* 
* @command generate_registration
*/
function pestle_cli($argv)
{
    if(count($argv) === 0)
    {
        $argv[] = inputModuleName();
    }
    $module_name = $argv[0];
    
    output(templateRegistrationPhp($module_name));
}
