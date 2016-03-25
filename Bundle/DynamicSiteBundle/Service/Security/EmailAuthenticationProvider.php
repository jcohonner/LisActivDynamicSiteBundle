<?php


namespace LisActiv\Bundle\DynamicSiteBundle\Service\Security;

use eZ\Publish\Core\MVC\Symfony\Security\Authentication\RepositoryAuthenticationProvider;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\Symfony\Security\User as EzUser;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

/**
 * This provider is responsible for user authentication
 * eZ functionality is overridden here to be able to load user additionally via email address
 * or later the load user from different trees
 *
 * additionally the session id of anonymous user is stored in the session here
 *
 * Class AuthenticationProvider
 */
class EmailAuthenticationProvider extends RepositoryAuthenticationProvider
{

    /**
     * @var \eZ\Publish\API\Repository\Repository $repository
     */
    protected $repository;

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
    /**
     * set the dependency to the repository
     *
     * @param Repository $repository
     */
    public function setRepository( Repository $repository )
    {
        $this->repository = $repository;
    }


    /**
     * override the eZ functionality to fetch user additionally by email address
     * if the user was authenticated successfully the session id of anonymous user
     * is stored in the session for later purposes
     *
     * @param UserInterface $user
     * @param UsernamePasswordToken $token
     * @return bool|void
     * @throws \Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        if (!$user instanceof EzUser) {
            return parent::checkAuthentication($user, $token);
        }


        // $currentUser can either be an instance of UserInterface or just the username/email (e.g. during form login).
        /** @var EzUser|string $currentUser */
        $currentUser = $token->getUser();
        if ($currentUser instanceof UserInterface) {
            if ($currentUser->getPassword() !== $user->getPassword()) {
                throw new BadCredentialsException( 'The credentials were changed from another session.' );
            }

            $apiUser = $currentUser->getAPIUser();

        } else  {
            //Get users by e-mail
            $users = $this->repository->getUserService()->loadUsersByEmail($token->getUsername());
            $apiUser = false;
            $userContentTypeId = $this->userContentTypeId();

            foreach ($users as $userObj) {
                //We will check User Class
                if ($userObj->contentInfo->contentTypeId==$userContentTypeId) {
                    try {
                        $apiUser = $this->repository->getUserService()->loadUserByCredentials($userObj->login, $token->getCredentials());
                    } catch(NotFoundException $e) {
                        //Nothing, we just continue with next user
                    }
                }

                //Stop as soon as we have one
                if ($apiUser) break;
            }


            if (!$apiUser) {
                throw new BadCredentialsException('Invalid credentials');
            }
        }

        // Finally inject current user in the Repository
        $this->repository->setCurrentUser($apiUser);

        return true;
    }

    /**
     * Returns user content type in current Siteaccess
     * @returns int
     */
    private function userContentTypeId() {
        return $this->configResolver->getParameter( 'user_content_type_id');
    }






}
