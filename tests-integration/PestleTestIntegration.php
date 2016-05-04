<?php

namespace Pulsestorm\Pestle\TestsIntegration;

use Exception;
use PHPUnit_Framework_TestCase;

class PestleTestIntegration extends PHPUnit_Framework_TestCase
{

    /**
     * @var string[]
     */
    protected $packageNames = ['pestle.phar', 'pestle', 'pestle_dev'];

    /**
     * @var $string;
     */
    protected $packageName;

    /**
     * Check the pestle command is available in the current environment.
     *
     * @test
     */
    public function testPestleIsAvailable()
    {
        $this->assertNotFalse($this->getPestlePackage());
    }

    /**
     * Run a command from users pestle implementation.
     *
     * @param $cmd
     *
     * @return mixed
     */
    protected function runCommand($cmd)
    {
        $pestle = $this->getPestlePackage();
        return `$pestle $cmd`;
    }

    /**
     * Check if a package is available.
     *
     * @param $package
     *
     * @return bool
     */
    protected function isPackageAvailable($package) {
        return (empty(`which $package`) ? false : true);
    }

    /**
     * Get the correct pestle package name from the users system.
     *
     * @return string|bool
     */
    protected function getPestlePackage()
    {
        if(!$this->packageName === null) {
            return $this->packageName;
        }

        $this->packageName = false;

        foreach($this->packageNames as $packageName) {
            if ($this->isPackageAvailable($packageName)) {
                $this->packageName = $packageName;
                break;
            }
        }

        return $this->packageName;
    }
}
