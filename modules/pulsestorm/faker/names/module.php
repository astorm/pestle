<?php
namespace Pulsestorm\Faker\Names;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

use Faker;
/**
* Creates some Fake Name
*
* @command faker:names
* @argument how_many How many names? [10]
* @option domain Domain name for email address 
*/
function pestle_cli($argv, $options)
{
    $domain = isset($options['domain']) ? $options['domain'] : 'example.com';
    $faker = Faker\Factory::create();

    for($i=0;$i<$argv['how_many'];$i++)
    {
        // $name = $faker->name;
        $name   = $faker->name;
        $email  = preg_replace('%[^a-zA-Z0-9_-]%','',$name) . '@' . $domain;
        output($name . "\t" . $email);
    }
}
