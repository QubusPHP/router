<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing\Controllers;

use Qubus\Routing\Controller\BaseController;

class MiddlewareProvidingController extends BaseController
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
