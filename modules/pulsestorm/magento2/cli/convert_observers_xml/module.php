<?php
namespace Pulsestorm\Magento2\Cli\Convert_Observers_Xml;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\convertObserverTreeScoped');

/**
* ALPHA: Partiall converts Magento 1 config.xml to Magento 2
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