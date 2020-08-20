<?php
namespace Pulsestorm\Magento1\Generate\Library;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\isAboveRoot');
pestle_import('Pulsestorm\Pestle\Library\getBaseDir');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplate');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');

function getBaseMagentoDir($path=false)
{
    return getBaseDir($path, 'app/Mage.php');
}

function getModuleDeclarationFile($fullName) {
    return getBaseMagentoDir() . '/app/etc/modules/' . $fullName . '.xml';
}

function getModuleCodePool($fullName) {
    $pathDeclare = getModuleDeclarationFile($fullName);
    $contents = simplexml_load_file($pathDeclare);
    return $contents->modules->{$fullName}->codePool;
}

function getBaseModuleDir($fullName) {
    list($package, $module) = explode('_', $fullName);
    $codePool = getModuleCodePool($fullName);
    return getBaseMagentoDir() . '/app/code/' . $codePool . '/' .
        $package . '/' . $module;
}

function getPathModuleConfigFile($fullName, $file='config.xml') {
    return getBaseModuleDir($fullName) . '/etc/' . $file;
}

function getPathModule($fullName, $folder='') {
    return getBaseModuleDir($fullName) . '/' . $folder;
}

function m1CreateClassTemplate($class, $extends=false, $implements=false, $includeUse=false) {
    $contents = createClassTemplate($class, $extends, $implements, $includeUse);
    return str_replace("namespace ;\n\n", '', $contents);
}

function createFrontendController($fullName, $path) {
    $parts = explode('/',$path);
    if(count($parts) !== 3) {
        exitWithErrorMessage('please use full/three/part URL path');
    }
    list($module, $controller, $action) = $parts;

    $parts = explode('_',$controller);
    $parts = array_map(function($part){
        return ucwords($part);
    }, $parts);
    $camelCasedController = implode('', $parts);
    $classContents = m1CreateClassTemplate(
        $fullName . "_${camelCasedController}Controller",
        'Mage_Core_Controller_Front_Action'
    );

    $classContents = str_replace(
        '<$body$>',
        "\n
    public function ${action}Action()
    {
        \$this->loadLayout();
        \$this->renderLayout();
    }
",
        $classContents
    );
    $pathControllers = getPathModule($fullName, 'controllers');
    writeStringToFile(
        $pathControllers . "/${camelCasedController}Controller.php",
        $classContents,
        false
    );
};

/**
* Not a command, just library functions
* @command library
*/
function pestle_cli($argv)
{
}
