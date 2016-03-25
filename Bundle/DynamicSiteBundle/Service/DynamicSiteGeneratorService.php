<?php
/**
 * File containing the PlaceHelper class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace LisActiv\Bundle\DynamicSiteBundle\Service;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\SearchService;
//use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Helper for places
 */
class DynamicSiteGeneratorService extends ContainerAware
{
    /**
     * @var  \eZ\Publish\API\Repository\LocationService
     */
    private $locationService;

    /**
     * @var  \eZ\Publish\API\Repository\SearchService
     */
    private $searchService;

    public function __construct( Repository $repository )
    {
        $this->repository = $repository;
    }

    /**
     * Returns all places contained in a place_list
     *
     * @param int|string $locationId id of a place_list
     * @param string|string[] $contentTypes to be retrieved
     * @param string|string[] $languages to be retrieved
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content[]
     */
    public function dumpConfig(  )
    {

        //Services
        $searchService = $this->repository->getSearchService();
        $userService = $this->repository->getUserService();

        //Log Admin User to be able to browse full data
        $this->repository->setCurrentUser( $userService->loadUser( 14 ) );

        //Initialise config array
        $siteaccessgroup = 'ezdemo_site_clean_group';
        $config = array( 'ezpublish' => array(   'siteaccess' =>
                                                    array(
                                                        'list' => array(),
                                                        'groups' => array('ezdemo_site_clean_group'=>array()),
                                                        'match'=> array('Map\Host'=>array())),
                                                 'system'=> array()),
                         'parameters' => array()
                       );

        //Get all site settings documents
        $query = new Query();
        $query->filter = new Criterion\ContentTypeIdentifier( $this->container->getParameter('dynamicsite.content_type') );
        $searchResults = $searchService->findContent( $query );

        foreach ($searchResults->searchHits as $site) {

            $settings = $this->siteSettings($site->valueObject);

            $config['ezpublish']['siteaccess']['list'][] = $settings['siteaccess'];
            $config['ezpublish']['siteaccess']['groups'][$siteaccessgroup][] = $settings['siteaccess'];
            $config['ezpublish']['siteaccess']['match']['Map\Host'][$settings['domain']] = $settings['siteaccess'] ;
            $config['ezpublish']['system'][$settings['siteaccess']] = array(
                                                            'content' => array (
                                                                'tree_root' => array(
                                                                    'location_id' => intval($settings['root_location_id']),
                                                                    'excluded_uri_prefixes' => array( '/media' )
                                                                    )
                                                                ),
                                                            'languages' => $settings['languages']
                                                        );

            $config['parameters']['ezsettings.'.$settings['siteaccess'].'.site_name'] = $settings['site_name'];
            $config['parameters']['ezsettings.'.$settings['siteaccess'].'.default_user_placement'] = intval($settings['default_user_placement']);
            $config['parameters']['ezsettings.'.$settings['siteaccess'].'.user_content_type_id'] = intval($settings['user_content_type_id']);

        }



        //Dump  Config file
        $dumper = new Dumper();
        $yaml = $dumper->dump($config['ezpublish'],6);

        $configFile = __DIR__ . '/../../../../..' .$this->container->getParameter('dynamicsite.config_file');
        $configFileDir = dirname($configFile);

        //@todo better implementation
        if (!file_exists($configFileDir)) {
            mkdir($configFileDir, 0777, true);
        }
        file_put_contents( $configFile, $yaml);


        //Dump Parameters file
        $yaml = $dumper->dump(array('parameters'=>$config['parameters']),6);

        $configFile = __DIR__ . '/../../../../..' .$this->container->getParameter('dynamicsite.parameters_file');
        $configFileDir = dirname($configFile);

        //@todo better implementation
        if (!file_exists($configFileDir)) {
            mkdir($configFileDir, 0777, true);
        }
        file_put_contents( $configFile, $yaml);

        return true;
    }

    /**
     * returns array of settings from content
     * @param SearchResult $content
     * @returns Array
     */
    private function siteSettings( $content ) {
        //Services
        $contentService = $this->repository->getContentService();
        $locationService = $this->repository->getLocationService();
        $urlAliasService = $this->repository->getUrlAliasService();

        //Parameters
        $domainField = $this->container->getParameter('dynamicsite.fields.domain');
        $rootField = $this->container->getParameter('dynamicsite.fields.root');
        $userPlacementField = $this->container->getParameter('dynamicsite.fields.default_user_placement');
        $userContentTypeIdField = $this->container->getParameter('dynamicsite.fields.user_content_type_id');


        //Get settings
        $siteAccess = $this->siteAccessUniquekey( $content );
        $domain = $content->getFieldValue( $domainField )->text;
        $userContentTypeId = $content->getFieldValue( $userContentTypeIdField )->text;
        $rootContentInfo = $contentService->loadContentInfo( $content->getFieldValue( $rootField )->destinationContentId );
        $defaultUserPlacementInfo = $contentService->loadContentInfo( $content->getFieldValue( $userPlacementField )->destinationContentId );
        $languages = $this->getContentLanguages( $rootContentInfo );
        $rootMainLocationId = $rootContentInfo->mainLocationId;
        $defaultUserPlacementId = $defaultUserPlacementInfo->mainLocationId;
        //Note as site is used for User Hash we need to get it stable so we use the site settings content Id
        $siteName = 'site_' . $content->id;

        return array(   'siteaccess'=>$siteAccess,
                        'domain'=>$domain,
                        'root_location_id'=>$rootMainLocationId,
                        'languages'=>$languages,
                        'default_user_placement' => $defaultUserPlacementId,
                        'site_name' => $siteName,
                        'user_content_type_id' => $userContentTypeId);
    }


    /**
     * returns the unique siteaccess name from content
     * @param SearchResult $content
     * @returns string
     */
    private function siteAccessUniquekey ( $content ) {
        //Services
        $contentService = $this->repository->getContentService();
        $locationService = $this->repository->getLocationService();
        $urlAliasService = $this->repository->getUrlAliasService();

        //Get UrlAlias
        $contentInfo = $contentService->loadContentInfo( $content->id );
        $mainLocation = $locationService->loadLocation($contentInfo->mainLocationId);
        $urlAlias = $urlAliasService->reverseLookup( $mainLocation, $contentInfo->mainLanguageCode, true );

        //Path - remove first /, replace all / by _
        return strtolower(str_replace(array('/','-'),'_',substr($urlAlias->path,1)));
    }

    private function getContentLanguages( $contentInfo ) {
        $contentService = $this->repository->getContentService();
        return $contentService->loadVersionInfo( $contentInfo )->languageCodes;
    }

}
