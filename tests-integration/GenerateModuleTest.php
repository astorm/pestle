<?php

namespace Pulsestorm\Pestle\TestsIntegration;

require_once 'PestleTestIntegration.php';

class GenerateModuleTest extends PestleTestIntegration
{
    const COMMAND = 'generate_module Pulsestore Testbed 0.0.1 Index Index Index';

    /**
     * @var string
     */
    protected $result;

    /**
     * Setup the integration test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->result = $this->runCommand();
    }

    /**
     * Check module declaration file exists.
     *
     * @test
     */
//     public function xtestModuleFileExists()
//     {
//         $result = file_exists(__DIR__.'/../app/code/Pulsestore/Testbed/etc/module.xml');
//         $this->assertTrue($result);
//     }
// 
//     /**
//      * Check module registration file exists.
//      *
//      * @test
//      */
//     public function xtestRegistrationFileExists()
//     {
//         $result = file_exists(__DIR__.'/../app/code/Pulsestore/Testbed/registration.php');
//         $this->assertTrue($result);
//     }
}
