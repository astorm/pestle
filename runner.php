<?php
function main($argv)
{
    include 'modules/pulsestorm/pestle/runner/module.php';
    \Pulsestorm\Pestle\Runner\main($argv);
}

function phpVersionCheck($argv) {
    $passedVersionCheck = true;

    $composerJson = json_decode(file_get_contents("composer.json"), true);
    if(array_key_exists("require", $composerJson) && array_key_exists("php", $composerJson["require"])) {
        $minimumPhpVersion = str_replace(">=", "", $composerJson["require"]["php"]);
        $currentPhpVersion = phpversion();

        if($currentPhpVersion < $minimumPhpVersion) {
            $passedVersionCheck = false;
            echo("Error: You must be running PHP version ". $minimumPhpVersion . " or later to use Pestle.");
        }
    }

    if($passedVersionCheck) {
        main($argv);
    }
}
phpVersionCheck($argv);