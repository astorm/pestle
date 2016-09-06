<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Xml_Library\simpleXmlAddNodesXpath');
pestle_import('Pulsestorm\Xml_Library\simpleXmlAddNodesXpathReturnOriginal');

class SimpleXmlAddNodesXpathTest extends PestleBaseTest
{
    public function testSetup()
    {
        $this->assertEquals(-1, -1);
    }
    
    public function testBaseline()
    {
        $xml = simplexml_load_string('<root/>');
        $node = simpleXmlAddNodesXpath($xml, 'foo/bar[@baz=hello]/science');
        
        $this->assertTrue(
            strpos(
                $xml->asXml(), 
                '<root><foo><bar baz="hello"><science/></bar></foo></root>'
            ) !== false
        );
        
        $this->assertEquals($node->getName(), 'science');
    }
    
    public function testSimpleXmlAddNodesXpathReturnOriginal()
    {
        $xml = simplexml_load_string('<root/>');
        $node = simpleXmlAddNodesXpathReturnOriginal($xml, 'foo/bar[@baz=hello]/science');
        
        $this->assertTrue(
            strpos(
                $xml->asXml(), 
                '<root><foo><bar baz="hello"><science/></bar></foo></root>'
            ) !== false
        );
        
        $this->assertEquals($node->getName(), 'root');
    }    
}