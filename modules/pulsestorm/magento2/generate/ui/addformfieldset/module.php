<?php
namespace Pulsestorm\Magento2\Generate\Ui\Addformfieldset;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\addSpecificChild');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');

function validateXml($xml, $argv)
{
    if($xml->getName() !== 'form')
    {
        exitWithErrorMessage('ERROR: This does not look like a <form/> file.');
    }
    
    $fieldsets = $xml->xpath('/form/fieldset[@name="'.$argv['fieldset'].'"]');
    if(count($fieldsets) !== 0)
    {
        exitWithErrorMessage('ERROR: XML file already has one name="'.$argv['fieldset'].'" fieldset.');
    }

}
/**
* Add a Fieldset to a Form 
*
* @command magento2:generate:ui:add-form-fieldset
* @argument path_xml Path to Form XML File? 
* @argument fieldset Fieldset Name? [newfieldset]
* @argument label Label? [NewFieldset]
*/
function pestle_cli($argv)
{
    $xml = simplexml_load_file($argv['path_xml']);
    validateXml($xml, $argv);
    $formels = $xml->xpath('/form');
    $formel   = array_shift($formels);
    $fieldset = $formel->addChild('fieldset');
    $fieldset->addAttribute('name', $argv['fieldset']);
    $argument     = addSpecificChild('argument', $fieldset, 'data', 'array');
    $itemConfig   = addSpecificChild('item', $argument, 'config', 'array');
    $itemLabel    = addSpecificChild('item', $itemConfig, 'label', 'string', $argv['label']);        
    $itemCollaps  = addSpecificChild('item', $itemConfig, 'collapsible', 'boolean', 'true');
    
    //output(formatXmlString($xml->asXml()));
    writeStringToFile(
        $argv['path_xml'],
        formatXmlString($xml->asXml())
    );
}
