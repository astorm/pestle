<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\View;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\View\exported_pestle_cli');

/**
* One Line Description
*
* @command magento2:generate:view
* @argument module_name Which Module? [Pulsestorm_HelloGenerate]
* @argument area Which Area? [frontend]
* @argument handle Which Handle? [<$module_name$>_index_index]
* @argument block_name Block Name? [Main]
* @argument template Template File? [content.phtml]
* @argument layout Layout (ignored for adminhtml) ? [1column]
*/
function pestle_cli($argv)
{
    return exported_pestle_cli($argv);
}
