<?php
namespace Pulsestorm\Magento2\Cli\Generate\Observer;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\createClassTemplate');
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\initilizeModuleConfig');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Pestle\Library\createBasicClassContents');

/**
* Generates Magento 2 Observer
* This command generates the necessary files and configuration to add 
* an event observer to a Magento 2 system.
*
*    pestle.phar generate_observer Pulsestorm_Generate controller_action_predispatch pulsestorm_generate_listener3 'Pulsestorm\Generate\Model\Observer3'
*
* @command generate_observer
* @argument module Full Module Name? [Pulsestorm_Generate]
* @argument event_name Event Name? [controller_action_predispatch]
* @argument observer_name Observer Name? [<$module$>_listener]
* @argument model_name Class Name? [<$module$>\Model\Observer]
*/
function pestle_cli($argv)
{
    $module         = $argv['module'];
    $event_name     = $argv['event_name'];
    $observer_name  = $argv['observer_name'];
    $model_name     = $argv['model_name'];
    $method_name    = 'execute';

    $path_xml_event = initilizeModuleConfig(
        $module, 
        'events.xml', 
        '../../../../../lib/internal/Magento/Framework/Event/etc/events.xsd'
    );
                    
    $xml = simplexml_load_file($path_xml_event);
    $nodes = $xml->xpath('//event[@name="' . $event_name . '"]');
    $node  = array_shift($nodes);
    $event = $node;
    if(!$node)
    {
        $event = $node ? $node : $xml->addChild('event');
        $event->addAttribute('name', $event_name);    
    }
    $observer = $event->addChild('observer');
    $observer->addAttribute('name',     $observer_name);
    $observer->addAttribute('instance', $model_name);
    // $observer->addAttribute('method',   $method_name);

    output("Creating: $path_xml_event");
    $path = writeStringToFile($path_xml_event, $xml->asXml());
    
    output("Creating: $model_name");
    $contents = createClassTemplate($model_name, false, '\Magento\Framework\Event\ObserverInterface');
    $contents = str_replace('<$body$>', 
    "\n" . 
    '    public function execute(\Magento\Framework\Event\Observer $observer){exit(__FILE__);}' .
    "\n" , $contents);
    // $contents = createBasicClassContents($model_name, $method_name);
    createClassFile($model_name, $contents);    
}
