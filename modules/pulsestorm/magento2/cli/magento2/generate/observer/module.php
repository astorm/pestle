<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Observer;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Observer\exported_pestle_cli');

/**
* Generates Magento 2 Observer
* This command generates the necessary files and configuration to add 
* an event observer to a Magento 2 system.
*
*    pestle.phar magento2:generate:observer Pulsestorm_Generate controller_action_predispatch pulsestorm_generate_listener3 'Pulsestorm\Generate\Model\Observer3'
*
* @command magento2:generate:observer
* @argument module Full Module Name? [Pulsestorm_Generate]
* @argument event_name Event Name? [controller_action_predispatch]
* @argument observer_name Observer Name? [<$module$>_listener]
* @argument model_name Class Name? [<$module$>\Model\Observer]
*/
function pestle_cli($argv)
{
    return exported_pestle_cli($argv);
}
