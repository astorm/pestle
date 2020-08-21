<?php
namespace Pulsestorm\Magento1\Generate\Controller;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Magento1\Generate\Library\createFrontendController');

/**
* Creates a new controller in a module (presumes module is already configured)
*
* @command magento1:generate:controller
* @argument full_module_name Full Module Name [Pulsestorm_Helloworld]
* @argument url_path Url Path [pulsestorm_helloworld/index/index]
*/
function pestle_cli($argv)
{
    createFrontendController(
        $argv['full_module_name'],
        $argv['url_path']
    );
    output("Done");
}
