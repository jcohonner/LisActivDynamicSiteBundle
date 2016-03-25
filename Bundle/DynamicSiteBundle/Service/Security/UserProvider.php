<?php
namespace LisActiv\Bundle\DynamicSiteBundle\Service\Security;

use eZ\Publish\Core\MVC\Symfony\Security\User\Provider;
use eZ\Publish\Core\MVC\Symfony\Security\User;
use eZ\Publish\Core\MVC\Symfony\Security\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;


/**
 * This provider is responsible for loading the user from eZ
 * eZ functionality is overridden here to be able to load user additionally via email address
 *
 *
 * Class UserProvider
 */
class UserProvider extends Provider
{


    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    protected $configResolver;
    /**
     * set the dependency to config Resolver
     * @param ConfigResolverInterface $configResolver
     */
    public function setConfigResolver(ConfigResolverInterface $configResolver) {
        $this->configResolver = $configResolver;
    }


    public function __construct($repository) {
        $this->repository = $repository;
    }

    /**
     * override the eZ functionality to fetch user by email address
     * $user can be either the username/email or an instance of \eZ\Publish\Core\MVC\Symfony\Security\User
     *
     * @param string|\eZ\Publish\Core\MVC\Symfony\Security\User $user
     * Either the username/email to load an instance of User object.
     *
     * @return \eZ\Publish\Core\MVC\Symfony\Security\UserInterface
     *
     * @throws \Symfony\Component\Security\Core\Exception\UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($user)
    {

        $userService = $this->repository->getUserService();
        $users = $userService->loadUsersByEmail( $user );

        $userContentTypeId = $this->userContentTypeId();

        foreach($users as $user) {
            //It is mandatory to use one class per site to ensure unicity
            //we will return the first user with this e-mail + class
            if ($user->contentInfo->contentTypeId==$userContentTypeId) {
                return new User( $user, array( 'ROLE_USER' ) );
            }
        }
        throw new UsernameNotFoundException( $e->getMessage(), 0 );
    }

    /**
     * Returns user content type in current Siteaccess
     * @returns int
     */
    private function userContentTypeId() {
        return $this->configResolver->getParameter( 'user_content_type_id');
    }




}
