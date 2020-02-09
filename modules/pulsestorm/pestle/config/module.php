<?php
namespace Pulsestorm\Pestle\Config;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Importer\getHomeDirectory');
use stdClass,Exception;

function storageMethod($value=null) {
    static $method='file';
    if(null !== $value) {
        $method=$value;
    }

    return $method;
}

function loadConfig($configType) {
    $type = storageMethod();
    $function = __NAMESPACE__ . '\\' . 'loadConfig' . ucWords($type);
    return call_user_func($function, $configType);
    // return loadConfigFile($configType);
}

function saveConfig($configType, $config) {
    $type = storageMethod();
    $function = __NAMESPACE__ . '\\' . 'saveConfig' . ucWords($type);
    return call_user_func($function, $configType, $config);
}

function saveConfigFile($configType, $config) {
    $path = getPathConfig($configType);
    return file_put_contents($path, json_encode($config));
}

function loadConfigFile($configType) {
    $path = getPathConfig($configType);
    if(!file_exists($path)) {
        file_put_contents($path,'{}');
    }
    $string = file_get_contents($path);
    $object = json_decode($string);
    if(!$object) {
        throw new Exception("Could not load config -- invalid json: $configType");
    }
    return $object;
}

function getOrSetConfigBase($value=null) {
    static $base=null;
    if(null === $value) {
        if(null === $base) {
            $home = getHomeDirectory();
            $base = $home . '/.pestle';
        }
        if(!is_dir($base)) {
            mkdir($base, 0755, true);
        }
        return $base;
    }
    $base = $value;
    return $value;
}

function getPathConfig($file=false) {
    $pathConfig = getOrSetConfigBase();

    if(!$file) {
        return $pathConfig;
    }

    $pathConfig = $pathConfig . '/' . $file . '.json';

    return $pathConfig;
}

function storeOrFetchMemoryBasedConfig($key, $value=null) {
    static $config=[];
    if(null != $value) {
        $config[$key] = $value;
        return $value;
    }
    // set a default if its not there
    if(!isset($config[$key])) {
        $config[$key] = new stdClass;
    }
    return $config[$key];
}

function loadConfigMemory($configType) {
    return storeOrFetchMemoryBasedConfig($configType);

}

function saveConfigMemory($configType, $config) {
    storeOrFetchMemoryBasedConfig($configType, $config);
    return true;
}
/**
* @command library
*/
function pestle_cli($argv)
{

}
