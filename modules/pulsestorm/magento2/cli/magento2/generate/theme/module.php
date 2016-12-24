<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Theme;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Theme\exported_pestle_cli');

/**
* Generates Theme Configuration
*
* @command magento2:generate:theme
* @argument package Theme Package Name? [Pulsestorm]
* @argument theme Theme Name? [blank]
* @argument area Area? (frontend, adminhtml) [frontend]
* @argument parent Parent theme (enter 'null' for none) [Magento/blank]
*
*/
function pestle_cli($argv)
{
    return exported_pestle_cli($argv);
}
