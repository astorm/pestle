<?php
namespace Pulsestorm\Magento2\Cli\Xml_Template;
use Exception;

function getBlankXmlModule()
{
    return '<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../lib/internal/Magento/Framework/Module/etc/module.xsd">
</config>';

}

function getBlankXmlView()
{
    return '<view xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Config/etc/view.xsd"></view>';
}

function getBlankXmlTheme()
{
    return '<theme xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Config/etc/theme.xsd"></theme>';
}

function getBlankXmlRoutes()
{
    $config_attributes = 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../../lib/internal/Magento/Framework/App/etc/routes.xsd"';
    return trim('<?xml version="1.0"?><config '.$config_attributes.'></config>');

}

function getBlankXmlDi()
{
    return '<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../lib/internal/Magento/Framework/ObjectManager/etc/config.xsd">
</config>';

}

function getBlankXml($type)
{
    $function = 'Pulsestorm\Magento2\Cli\Xml_Template';
    $function .= '\getBlankXml' . ucWords(strToLower($type));
    if(function_exists($function))
    {
        return call_user_func($function);
    }
    throw new Exception("No such type, $type");
}

function getBlankXmlLayout_handle()
{
    return '<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" layout="1column" xsi:noNamespaceSchemaLocation="../../../../../../../lib/internal/Magento/Framework/View/Layout/etc/page_configuration.xsd">
</page>';
}

/**
* Converts Zend Log Level into PSR Log Level
* @command library
*/
function pestle_cli($argv)
{
}    