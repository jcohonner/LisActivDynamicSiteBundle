<?php
/**
 * File containing the RepositoryFactory class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace LisActiv\Bundle\DynamicSiteBundle\Service;

use eZ\Bundle\EzPublishCoreBundle\ApiLoader\RepositoryFactory;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\SPI\Persistence\Handler as PersistenceHandler;
use eZ\Publish\SPI\Limitation\Type as SPILimitationType;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\Base\Container\ApiLoader\FieldTypeCollectionFactory;
use Symfony\Component\DependencyInjection\ContainerAware;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;

class LisActivRepositoryFactory extends RepositoryFactory
{

    /**
     * Constructor
     *
     * Construct repository object with provided storage engine
     *
     * @param \eZ\Publish\SPI\Persistence\Handler $persistenceHandler
     * @param array $serviceSettings
     * @param \eZ\Publish\API\Repository\Values\User\User|null $user
     */
    public function __construct(
        ConfigResolverInterface $configResolver,
        FieldTypeCollectionFactory $fieldTypeCollectionFactory,
        $repositoryClass) {
        $this->configResolver = $configResolver;
        $this->fieldTypeCollectionFactory = $fieldTypeCollectionFactory;
        $this->repositoryClass = $repositoryClass;
    }

    /**
     * Builds the main repository, heart of eZ Publish API
     *
     * This always returns the true inner Repository, please depend on ezpublish.api.repository and not this method
     * directly to make sure you get an instance wrapped inside Signal / Cache / * functionality.
     *
     * @param \eZ\Publish\SPI\Persistence\Handler $persistenceHandler
     *
     * @return \eZ\Publish\API\Repository\Repository
     */
    public function buildRepository( PersistenceHandler $persistenceHandler )
    {
        $repository = new $this->repositoryClass(
            $persistenceHandler,
            array(
                'fieldType'     => $this->fieldTypeCollectionFactory->getFieldTypes(),
                'role'          => array(
                    'limitationTypes'   => $this->roleLimitations
                ),
                'languages'     => $this->configResolver->getParameter( 'languages' ),
                'user' => array(
                    'siteName' => $this->configResolver->getParameter( 'site_name')
                )
            )
        );


        /** @var \eZ\Publish\API\Repository\Repository $repository */
        $anonymousUser = $repository->getUserService()->loadUser(
            $this->configResolver->getParameter( "anonymous_user_id" )
        );
        $repository->setCurrentUser( $anonymousUser );

        return $repository;
    }

}
