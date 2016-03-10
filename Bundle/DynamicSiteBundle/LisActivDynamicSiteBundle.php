<?php

namespace LisActiv\Bundle\DynamicSiteBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class LisActivDynamicSiteBundle extends Bundle
{

    protected $name = 'LisActivDynamicSiteBundle';

    public function getParent()
    {
        return 'eZDemoBundle';
    }

}
