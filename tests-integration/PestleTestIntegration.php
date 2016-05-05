<?php

namespace Pulsestorm\Pestle\TestsIntegration;

use Exception;
use PHPUnit_Framework_TestCase;

class PestleTestIntegration extends PHPUnit_Framework_TestCase
{

    const COMMAND = '';

    /**
     * @var string[]
     */
    protected $packageNames = ['pestle.phar', 'pestle', 'pestle_dev'];

    /**
     * @var $string;
     */
    protected $packageName;

    /**
     * Setup the integration tests.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->createFakeMagentoInstance();
    }

    /**
     * Tear down the integration tests.
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->deleteDirectoryTree('app');
    }

    /**
     * Create `app/etc/di.xml` so pestle.phar can work.
     *
     * @return $this
     */
    protected function createFakeMagentoInstance()
    {
        mkdir('app');
        mkdir('app/etc');
        touch('app/etc/di.xml');

        return $this;
    }

    /**
     * Delete a directory tree: http://php.net/manual/en/function.rmdir.php
     *
     * @param $directory
     *
     * @return bool
     */
    protected function deleteDirectoryTree($directory) {
        $files = array_diff(scandir($directory), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$directory/$file")) ? $this->deleteDirectoryTree("$directory/$file") : unlink("$directory/$file");
        }

        return rmdir($directory);
    }

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
    protected function runCommand($cmd = false)
    {
        if (!$cmd) {
            $cmd = static::COMMAND;
        }

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
