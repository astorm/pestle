<?php
namespace Pulsestorm\Nexmo\Storecredentials;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
use stdClass;

function getClient()
{
    $data = readFromCredentialFile();
    $key = $data->key;
    $secret = $data->secret;    
    $client = new \Nexmo\Client(new \Nexmo\Client\Credentials\Basic($key, $secret));    
    return $client;
}

function getCredentialFilePath()
{
    return '/tmp/.nexmo';
}

function readFromCredentialFile()
{
    $path = getCredentialFilePath();
    $o = false;
    if(file_exists(getCredentialFilePath()))
    {
        $o = json_decode(
            file_get_contents(
                $path
            )
        );
    }
    if(!$o)
    {
        $o = new stdClass;
    }
    
    return $o;
}

function writeToCredentialFile($data)
{
    $path = getCredentialFilePath();
    $result = file_put_contents($path, json_encode($data));
    chmod($path, 0600);
    return $result;
}

/**
* Stores Nexmo API in temp file
*
* @command nexmo:store-credentials
* @argument key Key? []
* @argument password Secret/Password? []
*/
function pestle_cli($argv)
{
    $data           = readFromCredentialFile();
    $data->key      = $argv['key'];
    $data->secret   = $argv['password'];
    writeToCredentialFile($data);    
}
