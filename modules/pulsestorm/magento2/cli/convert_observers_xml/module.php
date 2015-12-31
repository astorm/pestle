<?php
namespace Pulsestorm\Magento2\Cli\Convert_Observers_Xml;
use function Pulsestorm\Pestle\Runner\pestle_import;
pestle_import('Pulsestorm\Magento2\Cli\Library\input');
pestle_import('Pulsestorm\Magento2\Cli\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\convertObserverTreeScoped');

/**
* Short Description
* Long
* Description
* @command convert_observers_xml
*/
function pestle_cli($argv)
{
    $paths = $argv;
    if(count($argv) === 0)
    {
        $paths = [input("Which config.xml?", 'app/code/Mage/Core/etc/config.xml')];
    }
    foreach($paths as $path)
    {
        $xml = simplexml_load_file($path);
        $scopes = ['global','adminhtml','frontend'];
        foreach($scopes as $scope)
        {
            $xml_new = convertObserverTreeScoped($xml->{$scope}, $xml);
            output($scope);
            output($xml_new->asXml());
            output('--------------------------------------------------');            
        }
    }

}