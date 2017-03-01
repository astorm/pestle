<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Crud_Model;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Crud\Model\exported_pestle_cli');

/**
* Generates a Magento 2 CRUD/AbstractModel class and support files
*
* @command magento2:generate:crud-model
* @argument module_name Which module? [Pulsestorm_HelloGenerate]
* @argument model_name  What model name? [Thing]
*/
function pestle_cli($argv)
{
    return exported_pestle_cli($argv);
}
