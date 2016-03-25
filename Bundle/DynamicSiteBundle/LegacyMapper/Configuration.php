<?php
/**
 * File containing the Configuration class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace LisActiv\Bundle\DynamicSiteBundle\LegacyMapper;

use eZ\Publish\Core\MVC\Legacy\LegacyEvents;
use eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelEvent;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use RuntimeException;

/**
 * Maps configuration parameters to the legacy parameters
 */
class Configuration extends ContainerAware implements EventSubscriberInterface
{
    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    private $configResolver;


    public function __construct(ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }



    public static function getSubscribedEvents()
    {
        return array(
            LegacyEvents::PRE_BUILD_LEGACY_KERNEL => array( "onBuildKernel", 128 )
        );
    }

    /**
     * Adds settings to the parameters that will be injected into the legacy kernel
     *
     * @param \eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelEvent $event
     */
    public function onBuildKernel( PreBuildKernelEvent $event )
    {

        // Set as defaul hash method md5_site
        $settings["site.ini/UserSettings/HashType"] = 'md5_site';
        $settings["site.ini/UserSettings/RequireUniqueEmail"] = 'false';

        //From dynamic site parameters
        $settings["site.ini/UserSettings/DefaultUserPlacement"] = $this->configResolver->getParameter( "default_user_placement" );
        $settings["site.ini/UserSettings/SiteName"] = $this->configResolver->getParameter( "site_name" );
        $settings["site.ini/UserSettings/UserClassID"] = $this->configResolver->getParameter( "user_content_type_id" );


        $event->getParameters()->set(
            "injected-settings",
            $settings + (array)$event->getParameters()->get( "injected-settings" )
        );

    }
}
