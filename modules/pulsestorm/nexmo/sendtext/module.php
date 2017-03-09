<?php
namespace Pulsestorm\Nexmo\Sendtext;
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
/**
* Sends a text message
*
* @command nexmo:send-text
* @argument to Send to phone number? 
* @argument from From phone number? [12155167753]
* @argument text Text to send? [You are the best!]
*/
function pestle_cli($argv)
{
    $client     = getClient();
    $message    = false;
    try
    {
        $message = $client->message()->send([
            'to'   => $argv['to'],
            'from' => $argv['from'],
            'text' => $argv['text']
        ]);    
    }
    catch(Exception $e)
    {
        handleException($e);
    }        
    if($message)
    {
        output($message->getResponseData());
    }
}
