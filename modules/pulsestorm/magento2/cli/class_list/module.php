<?php
namespace Pulsestorm\Magento2\Cli\ClassList;
use function Pulsestorm\Pestle\Importer\pestle_import;

pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');

/*
 * TODO: move functions someplace more appropriate
 */
/**
 * Build list of magento module path regexps which should be excluded from compilation
 *
 * @param string[] $modulePaths
 * @return string[]
 */
function getMagentoExcludedModulePaths(array $modulePaths)
{
    $modulesByBasePath = [];
    foreach ($modulePaths as $modulePath) {
        $moduleDir = basename($modulePath);
        $vendorPath = dirname($modulePath);
        $vendorDir = basename($vendorPath);
        $basePath = dirname($vendorPath);
        $modulesByBasePath[$basePath][$vendorDir][] = $moduleDir;
    }

    $basePathsRegExps = [];
    foreach ($modulesByBasePath as $basePath => $vendorPaths) {
        $vendorPathsRegExps = [];
        foreach ($vendorPaths as $vendorDir => $vendorModules) {
            $vendorPathsRegExps[] = $vendorDir
                . '/(?:' . join('|', $vendorModules) . ')';
        }
        $basePathsRegExps[] = $basePath
            . '/(?:' . join('|', $vendorPathsRegExps) . ')';
    }

    $excludedModulePaths = [
        '#^(?:' . join('|', $basePathsRegExps) . ')/Test#',
    ];
    return $excludedModulePaths;
}

/**
 * Build list of magento library path regexps which should be excluded from compilation
 *
 * @param string[] $libraryPaths
 * @return string[]
 */
function getMagentoExcludedLibraryPaths(array $libraryPaths)
{
    $excludedLibraryPaths = [
        '#^(?:' . join('|', $libraryPaths) . ')/([\\w]+/)?Test#',
    ];
    return $excludedLibraryPaths;
}

/**
 * @param \Magento\Framework\ObjectManagerInterface $objectManager
 * @return string[]
 */
function getMagentoExtendableClassList($objectManager){
    $componentRegistrarClass = $objectManager->get('Magento\Framework\Component\ComponentRegistrar');
    $classScanner = $objectManager->get('Magento\Setup\Module\Di\Code\Reader\ClassesScanner');

    $modulePaths = $componentRegistrarClass->getPaths(\Magento\Framework\Component\ComponentRegistrar::MODULE);
    $libraryPaths = $componentRegistrarClass->getPaths(\Magento\Framework\Component\ComponentRegistrar::LIBRARY);
    //TODO: add this in the future
    //$generationPath = $this->directoryList->getPath(DirectoryList::GENERATION);

    $classScanner->addExcludePatterns([
        'application' => getMagentoExcludedModulePaths($modulePaths),
        'framework' => getMagentoExcludedLibraryPaths($libraryPaths),
    ]);

    // TODO: add generation path
    $paths = array_merge($modulePaths, $libraryPaths);

    $classList = [];
    foreach ($paths as $path){
        $classList = array_merge($classList, $classScanner->getList($path));
    }

    return $classList;
}

/**
 * Unregister an array of callable autoloaders
 *
 * @param callable[] $callables array of callables to unregister
 * @return callable[] the array of unregistered autoloaders
 */
function unregisterAutoloaders($callables){
    foreach ($callables as $callable) {
        spl_autoload_unregister($callable);
    }
    return $callables;
}

/**
 * Unregister all active autoloaders
 *
 * @return callable[] the array of unregistered autoloaders
 */
function unregisterAllAutoloaders(){
    return unregisterAutoloaders(spl_autoload_functions());
}

/**
 * Register an array of callable autoloaders
 *
 * @param callable[] $callables array of callables to register
 * @return callable[] the array of all registered autoloaders
 */
function registerAutoloaders($callables){
    foreach ($callables as $callable) {
        spl_autoload_register($callable);
    }
    return spl_autoload_functions();
}

/**
* Get a list of all of magento2's extensible classes
*
* @command magento2:class-list
*/
function pestle_cli($argv)
{

    $pestlesLoaders = unregisterAllAutoloaders();
    try {
        /*
         * Place magento's autoloaders higher up the queue over pestle's
         * TODO: the 'save/reorder loaders -> run blackbox -> restore' pattern can be templated
         */
        // Magento's autoloaders are loaded here
        require getBaseMagentoDir() . '/app/bootstrap.php';

        registerAutoloaders($pestlesLoaders);
        /*
         * TODO: wrap this logic in an application container
         * create application, and run it.
         * Will allow us to properly use magento2's EXTREMELY powerful constructor di.
         */
        $bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
        $objManager = $bootstrap->getObjectManager();
        $magentoClassList = getMagentoExtendableClassList($objManager);
        foreach ($magentoClassList as $item) {
            output($item);
        }
    } catch (\Exception $e) {
        output('Magento2 autoload/bootstrap/application creation/run error');
    } finally {
        // keep only pestle's loaders
        unregisterAutoloaders(spl_autoload_functions());
        registerAutoloaders($pestlesLoaders);
    }
}

