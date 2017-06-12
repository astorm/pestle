<?php
namespace Pulsestorm\Magento2\Generate\Ui\Addformfield;
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
    if(count($fieldsets) !== 1)
    {
        exitWithErrorMessage('ERROR: XML file should have exactly one name="'.$argv['fieldset'].'" fieldset.');
    }

    $fields = $xml->xpath('/form/fieldset/field[@name="'.$argv['field'].'"]');
    if(count($fields) > 0)
    {
        exitWithErrorMessage("ERROR: XML file already has a name=\"{$argv['field']}\" field.");
    }
}

function getNextSortOrderFromXml($xml)
{
    $nodes = $xml->xpath('//item[@name="sortOrder"]');
    $max   = array_reduce($nodes, function($carry, $item){
        $item = (int) $item;
        if($carry > $item)
        {
            return $carry;
        }
        return $item;
    }, 0);
    
    return $max + 10;
}


/**
* Adds a Form Field
*
* @command magento2:generate:ui:add-form-field
* @argument path_xml Path to Form XML File? 
* @argument field Field Name? [title]
* @argument label Label? [Title]
* @argument fieldset Fieldset Name? [general]
* @option is-required Is field required?
*/
function pestle_cli($argv, $options)
{    
    $xml = simplexml_load_file($argv['path_xml']);
    validateXml($xml, $argv);
    $fieldsets  = $xml->xpath('/form/fieldset[@name="'.$argv['fieldset'].'"]');        
    $fieldset   = array_shift($fieldsets);
    
    $dataType = 'text';
    $formElement = 'input';
    $sortOrder   = '25';
    
    $field      = $fieldset->addChild('field');
    $field->addAttribute('name', $argv['field']);
    // addSpecificChild('field', $fieldset,);
    $argument           = addSpecificChild('argument', $field, 'data', 'array');
    $itemConfig         = addSpecificChild('item', $argument, 'config', 'array');
    $itemDataType       = addSpecificChild('item', $itemConfig, 'dataType', 'string', $dataType);
    $itemLabel          = addSpecificChild('item', $itemConfig, 'label', 'string', $argv['label']);        
    $itemFormElement    = addSpecificChild('item', $itemConfig, 'formElement', 'string', $formElement);
    $itemSortOrder      = addSpecificChild('item', $itemConfig, 'sortOrder', 'string', getNextSortOrderFromXml($fieldset));
    $itemDataScope      = addSpecificChild('item', $itemConfig, 'dataScope', 'string', $argv['field']);    
        
    $itemValidation     = addSpecificChild('item', $itemConfig, 'validation', 'array');    
    $required           = is_null($options['is-required']) ? 'false' : 'true';
    $itemRequiredEntry  = addSpecificChild('item', $itemValidation, 'required-entry', 'boolean', $required);    
        
    writeStringToFile(
        $argv['path_xml'],
        formatXmlString($xml->asXml())
    );
}



