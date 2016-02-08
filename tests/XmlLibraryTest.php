<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Xml_Library\simpleXmlAddNodesXpath;

class LibraryTest extends PestleBaseTest
{
    public function setup()
    {
        $path = $this->getPathToModuleFileUnderTest(
            'modules/pulsestorm/xml_library/module.php');
        require_once $path;
    }

    /**
     * @test
     * @dataProvider providerAddSimpleXmlNodesByXPath
     *
     * @param string $xml
     * @param string $path
     * @param string $expected
     */
    public function addSimpleXmlNodesByXPath($xml, $path, $expected)
    {
        $xml = simplexml_load_string($xml);
        $xml = simpleXmlAddNodesXpath($xml, $path);
        $this->assertContains($expected, $xml->asXML());
    }

    /**
     * @return array
     */
    public function providerAddSimpleXmlNodesByXPath()
    {
        return [
            'simple_path' => [
                '<config></config>',
                'title',
                '<config><title/></config>'
            ],
            'simple_path_with_leading_slash' => [
                '<config></config>',
                '/title',
                '<config><title/></config>'
            ],
            'simple_path_with_attribute' => [
                '<config></config>',
                'title[@name=foo]',
                '<config><title name="foo"/></config>'
            ],
            'simple_path_with_namespace_attribute' => [
                '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd"></config>',
                'title[@xsi:type=string]',
                '<title xsi:type="string"/>'
            ],
            'simple_path_with_two_attributes' => [
                '<config></config>',
                'title[@name=foo,@name2=bar]',
                '<config><title name="foo" name2="bar"/></config>'
            ],
        ];
    }
}