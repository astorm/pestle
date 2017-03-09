<?php
namespace Pulsestorm\Nexmo\Verifysendcode;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Nexmo\Storecredentials\getClient');
use Exception;

function sendVerifyVerification($client, $verificationRequestId, $code)
{
    $clientVerify = $client->verify();
    $result = false;
    try
    {
        $result = $clientVerify->check(
            $verificationRequestId,
            $code
        );    
    }
    catch(\Exception $e)
    {
        output(get_class($e));
        output($e->getMessage());
    }
    return $result;
}

/**
* One Line Description
*
* @command nexmo:verify-sendcode
* @argument request_id Request ID? (from nexmo:verify-request) []
* @argument code The four or six digit code? []
*/
function pestle_cli($argv)
{
    $client = getClient();
    $result = sendVerifyVerification($client, $argv['request_id'], $argv['code']);
    if($result)
    {
        output($result->getResponseData());
    }
}
