<?php
namespace Pulsestorm\Magento2\Cli\Generate\Registration;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Cli\Code_Generation\templateRegistrationPhp');
pestle_import('Pulsestorm\Pestle\Library\inputModuleName');
pestle_import('Pulsestorm\Magento2\Cli\Library\inputModuleName');

/**
* Generates registration.php
* This command generates the PHP code for a 
* Magento module registration.php file.
* 
*     $ pestle.phar generate_registration Foo_Bar
*     <?php
*         \Magento\Framework\Component\ComponentRegistrar::register(
*             \Magento\Framework\Component\ComponentRegistrar::MODULE,
*             'Foo_Bar',
*             __DIR__
*         );
* 
* @command generate_registration
* @argument module_name Which Module? [Vendor_Module] 
*/
function pestle_cli($argv)
{
    $module_name = $argv['module_name'];
    
    output(templateRegistrationPhp($module_name));
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}