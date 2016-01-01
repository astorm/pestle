<?php
namespace Pulsestorm\Xml_Library;

/**
* @command library
*/
function pestle_cli($argv)
{
    
}

function getXmlNamespaceFromPrefix($xml, $prefix)
{
    $namespaces = $xml->getDocNamespaces();
    if(array_key_exists($prefix, $namespaces))
    {
        return $namespaces[$prefix];
    }

    throw new Exception("Unkonwn namespace in " . __FILE__);
}

function simpleXmlAddNodesXpath($xml, $path)
{
    $path = trim($path,'/');
    $node = $xml;
    foreach(explode('/',$path) as $part)
    {
        $parts = explode('[', $part);
        $node_name = array_shift($parts);
        $is_new_node = true;
        if(isset($node->{$node_name}))
        {
            $is_new_node = false;
            $node = $node->{$node_name};        
        }
        else
        {
            $node = $node->addChild($node_name);
        }
        
        
        $attribute_string = trim(array_pop($parts),']');
        if(!$attribute_string) { continue; }
        $pairs = explode(',',$attribute_string);
        foreach($pairs as $pair)
        {
            if(!$is_new_node) { continue; }
            list($key,$value) = explode('=',$pair);
            if(strpos($key, '@') !== 0)
            {
                throw new Exception("Invalid Attribute Key");
            }
            $key = trim($key, '@');
            if(strpos($key, ':') !== false)
            {                
                list($namespace_prefix, $rest) = explode(':', $key);
                $namespace = getXmlNamespaceFromPrefix($xml, $namespace_prefix);
                $node->addAttribute($key, $value, $namespace);
            }
            else
            {
                $node->addAttribute($key, $value);
            }
            
        }
//         exit;
    }
    return $node;
}

function formatXmlString($string)
{
    $dom = new DomDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;        
    $dom->loadXml($string);
    $string = $dom->saveXml();
    
    $string = preg_replace('%(^\s*)%m', '$1$1', $string);
    
    return $string;
}