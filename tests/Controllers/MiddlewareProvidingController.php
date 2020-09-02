<?php

namespace Qubus\Router\Test\Controllers;

use Qubus\Router\Controller;

class MiddlewareProvidingController extends Controller
{
    public function returnOne()
    {
        return 'One';
    }

    public function returnTwo()
    {
        return 'Two';
    }

    public function returnThree()
    {
        return 'Three';
    }
}
