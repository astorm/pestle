<?php
namespace Pulsestorm\Magento2\Cli\Base_Directory;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

/**
* Output the base magento2 directory
*
* @command magento2:base-dir
*/
function pestle_cli($argv)
{
    output(getBaseMagentoDir());
}
