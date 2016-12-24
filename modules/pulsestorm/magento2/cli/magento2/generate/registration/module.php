<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Registration;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Registration\exported_pestle_cli');

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
* @command magento2:generate:registration
* @argument module_name Which Module? [Vendor_Module] 
*/
function pestle_cli($argv)
{
    return exported_pestle_cli($argv);
}
