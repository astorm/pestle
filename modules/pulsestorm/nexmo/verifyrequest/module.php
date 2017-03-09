<?php
namespace Pulsestorm\Nexmo\Verifyrequest;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Nexmo\Storecredentials\getClient');
use Exception;

function handleException($e)
{
    output(get_class($e));
    output($e->getMessage());
}

function sendVerifyRequest($client, $number, $brand)
{    
    $clientVerify   = $client->verify();    
    $verification   = [
        'number' => $number,
        'brand'  => $brand        
    ];        
    
    $response = false;
    try
    {
        $response = $clientVerify->start($verification);
    }
    catch(Exception $e)
    {
        handleException($e);
    }
    return $response;
}

/**
* Sends initial request to verify user's phone number
*
* @command nexmo:verify-request
* @argument to Phone number to verify?
* @argument brand Brand/Prefix string for code message? [MyApp]
*/
function pestle_cli($argv)
{
    $client = getClient();
    $verifyRequestResponse = sendVerifyRequest(
        $client, $argv['to'], $argv['brand']);    
    if($verifyRequestResponse)
    {
        $json = $verifyRequestResponse->getResponseData();    
        output($json);    
    }        
}
