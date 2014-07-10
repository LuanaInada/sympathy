<?php

namespace Sympathy\Tests\Silex\Router;

use TestTools\TestCase\UnitTestCase;
use Sympathy\Silex\Router\ErrorRouter;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @package Sympathy
 * @license MIT
 */
class ErrorRouterTest extends UnitTestCase
{
    /**
     * @var ErrorRouter
     */
    protected $router;

    public function setUp()
    {
        $this->router = $this->get('router.error');
    }

    public function testRoute () {
        $this->router->route();
    }
}