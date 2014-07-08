<?php

namespace Sympathy\Tests\Silex;

use TestTools\TestCase\UnitTestCase;
use Sympathy\Tests\Silex\App\App;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @package Sympathy
 * @license MIT
 */
class KernelTest extends UnitTestCase
{
    /**
     * @var App
     */
    protected $app;

    public function setUp()
    {
        $this->app = new App('sympathy_test');
    }

    public function testGetName()
    {
        $result = $this->app->getName();
        $this->assertEquals('App', $result);
    }

    public function testGetVersion()
    {
        $result = $this->app->getVersion();
        $this->assertEquals('1.0', $result);
    }

    public function testGetEnvironment()
    {
        $result = $this->app->getEnvironment();
        $this->assertEquals('sympathy_test', $result);
    }

    public function testGetCharset()
    {
        $result = $this->app->getCharset();
        $this->assertEquals('UTF-8', $result);
    }

    public function testGetKernelParameters()
    {
        $result = $this->app->getKernelParameters();
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('app.name', $result);
        $this->assertArrayHasKey('app.version', $result);
        $this->assertArrayHasKey('app.environment', $result);
        $this->assertArrayHasKey('app.debug', $result);
        $this->assertArrayHasKey('app.charset', $result);
        $this->assertArrayHasKey('app.root_dir', $result);
        $this->assertArrayHasKey('app.cache_dir', $result);
        $this->assertArrayHasKey('app.log_dir', $result);
        $this->assertArrayHasKey('app.config_dir', $result);
    }
}