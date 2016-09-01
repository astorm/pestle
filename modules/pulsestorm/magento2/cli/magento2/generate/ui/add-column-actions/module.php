<?php
namespace Pulsestorm\Magento2\Cli\Magento2_Generate_Ui_Add_Column_Actions;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Magento2\Cli\Library\addArgument');
pestle_import('Pulsestorm\Magento2\Cli\Library\addItem');
pestle_import('Pulsestorm\Magento2\Cli\Library\validateAsListing');
pestle_import('Pulsestorm\Magento2\Cli\Library\getOrCreateColumnsNode');

function getPackageAndModuleNameFromListingXmlFile($file)
{
    if(strpos($file, 'app/code') === false)
    {
        output("At the time this command was written, pestle assumed app/code as a working directory");
        output("That file isn't in app/code, so we need to bail :(");
        exit;
    }
    $parts = explode('app/code/', $file);
    $parts = explode('/', array_pop($parts));
    
    return [$parts[0], $parts[1]];
}

function getGridIdFromListingXmlFile($xml)
{
    $stuff = pathinfo($xml);
    return $stuff['filename'];    
}

function generatePageActionsClassFromListingXmlFileAndXml($file, $xml)
{
    list($package, $moduleName) = getPackageAndModuleNameFromListingXmlFile($file);
    $gridId                     = getGridIdFromListingXmlFile($file);
    
    $pageActionsClassName = $package . '\\' . $moduleName . '\\' . 
        'Ui\Component\Listing\Column\\' . 
        ucwords(preg_replace('%[^a-zA-Z0-9]%', '', $gridId)) . '\\' .
        'PageActions';
        
    var_dump($pageActionsClassName);        
    exit;
    return $actionsClass = 'Foo\Baz\Bar\Actions';
}

/**
* Generates a Magento 2.1 ui grid listing and support classes.
*
* @command magento2:generate:ui:add-column-actions
* @argument listing_file Which Listing File? []
* @argument index_field Index Field/Primary Key? [entity_id]
*/
function pestle_cli($argv)
{
    $xml = simplexml_load_file($argv['listing_file']);
    validateAsListing($xml);
    
    $actionsClass = generatePageActionsClassFromListingXmlFileAndXml($argv['listing_file'], $xml);
    
    $columns = getOrCreateColumnsNode($xml);            
    $actionsColumn = $columns->addChild('actionsColumn');
    $actionsColumn->addAttribute('name', 'actions');    
    $actionsColumn->addAttribute('class', $actionsClass);    
    $argument = addArgument($actionsColumn, 'data', 'array');    
    $configItem = addItem($argument, 'config', 'array');
    $indexField = addItem($configItem, 'indexField', 'string', $argv['index_field']);
    
    output(
        formatXmlString($xml->asXml())
    );
    
// <actionsColumn name="actions" class="Pulsestorm\ToDoCrud\Ui\Component\Listing\Column\Pulsestormtodolisting\PageActions">
//     <argument name="data" xsi:type="array">
//         <item name="config" xsi:type="array">
//             <item name="resizeEnabled" xsi:type="boolean">false</item>
//             <item name="resizeDefaultWidth" xsi:type="string">107</item>
//             <item name="indexField" xsi:type="string">pulsestorm_todocrud_todoitem_id</item>
//         </item>
//     </argument>
// </actionsColumn>

}
