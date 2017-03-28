<?php
namespace Pulsestorm\Magento2\Generate\Ui\Addcolumntext;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\addSpecificChild');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');

function getColumnsNodes($xml)
{
    $columns = $xml->xpath('//columns');
    return $columns;
}

function validateXml($xml, $argv)
{
    if($xml->getName() !== 'listing')
    {
        exitWithErrorMessage('ERROR: This does not look like a <listing/> file.');
    }
    
    $columns = getColumnsNodes($xml);
    if(count($columns) !== 1)
    {
        exitWithErrorMessage('ERROR: File must have exactly one <columns/> node.');
    }
    
    $name = $argv['column_name'];
    $nodes = $xml->xpath('//*[self::column or self::actionsColumn or self::selectionsColumn]');
    $column = array_filter($nodes, function($item) use ($name){
        return (string) $item['name'] === $name;
    });
    
    if(count($column) > 0)
    {
        exitWithErrorMessage("We already have a {$name} column.");
    }
}

function getParentNode($node, $times=1)
{
    for($i=0;$i<$times;$i++)
    {
        $results = $node->xpath('parent::*');
        $parent  = array_shift($results);    
        if(!$parent){ break; } //reached top
        $node    = $parent;
    }
    
    return $parent;
}

function getSortOrder($xml)
{
    //grab all sortOrder nodes
    $sortOrderNodes = $xml->xpath('//*[@name="sortOrder"]');
    if(count($sortOrderNodes) === 0)
    {
        return 10; //default
    }
    
    //make sure sort order nodes are for our columns node and not something else
    $numbers = array_map(function($node){
        $parent = getParentNode($node,4);
        if($parent->getName() !== 'columns')
        {
            return null;
        }
        return (int)$node;
    }, $sortOrderNodes);
    $numbers = array_filter($numbers);
    
    //If no sortOrder nodes, start with 10
    if(count($numbers) === 0)
    {
        return 10;
    }
    
    //if only one sort order node, take 1 off the max
    if(count($numbers) === 1)
    {
        return max($numbers) - 1;
    }
    
    //Find number between highest two numbers to slide our column in
    //right before the last one
    sort($numbers);
    $max = array_pop($numbers);
    $min = array_pop($numbers);
    $numbers = range($min, $max);
    $count   = count($numbers);
    //if there's less than three numbers in the array, numbers
    //are too close for a middle number
    if($count < 3)
    {
        return max($numbers) - 1;
    }
    
    $index = (int) $count / 2;
    return $numbers[$index];
}

/**
* Adds a simple text column to a UI Component Grid
*
* @command magento2:generate:ui:add-column-text
* @argument listing_file Which Listing XML File?
* @argument column_name New Column Field? [title]
* @argument column_label New Column Label? [Title]
*/
function pestle_cli($argv)
{
    $xml = simplexml_load_file($argv['listing_file']);
    validateXml($xml, $argv);
    
    $columns     = getColumnsNodes($xml);
    $columnsNode = array_shift($columns);
    
    $column      = $columnsNode->addChild('column');
    $column->addAttribute('name', $argv['column_name']);
    
    $argument           = addSpecificChild('argument',$column, 'data', 'array');
    $itemNamedConfig    = addSpecificChild('item', $argument, 'config', 'array');           
    $label              = addSpecificChild('item', $itemNamedConfig, 'label','string', $argv['column_label']);    
    $sortOrder          = addSpecificChild('item', $itemNamedConfig, 'sortOrder','number', getSortOrder($xml));    
    
    output("Adding to {$argv['listing_file']}");
    writeStringToFile($argv['listing_file'], formatXmlString($xml->asXml()));
}
