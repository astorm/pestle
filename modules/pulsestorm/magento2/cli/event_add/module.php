<?php
namespace Pulsestorm\Magento2\Cli\Event_Add;
use function Pulsestorm\Pestle\Runner\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\input');
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
// pestle_import('Pulsestorm\Magento2\Cli\Library');
pestle_import('Pulsestorm\Magento2\Cli\Library\initilizeModuleConfig');
pestle_import('Pulsestorm\Magento2\Cli\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\createBasicClassContents');
pestle_import('Pulsestorm\Magento2\Cli\Library\createClassFile');

/**
* Short Description
* Long
* Description
* @command event_add
*/
function pestle_cli($argv)
{
    $module = input("Full Module Name?", 'Pulsestorm_Helloworld');
    $path_xml_event = initilizeModuleConfig(
        $module, 
        'events.xml', 
        '../../../../../lib/internal/Magento/Framework/Event/etc/events.xsd'
    );
        
    //$event_name = 'EVENT_NAME_HERE';
    $event_name     = input('Event Name?', 'controller_action_predispatch');
    $observer_name  = input('Observer Name?', strToLower($module . '_listener'));
    $model_name     = input('Class Name?', str_replace('_', '\\', $module) . '\\Model\\Observer');
    $method_name    = input('Method Name?', 'myMethodName');
                
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
    $observer->addAttribute('method',   $method_name);

    $path = writeStringToFile($path_xml_event, $xml->asXml());


    $contents = createBasicClassContents($model_name, $method_name);
    createClassFile($model_name, $contents);
}
