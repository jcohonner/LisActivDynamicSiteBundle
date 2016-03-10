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
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Symfony\Component\Yaml\Dumper;

/**
 * Helper for places
 */
class DynamicSiteGeneratorService
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
        $config = array( 'siteaccess' =>
                            array(
                                'list' => array(),
                                'groups' => array('ezdemo_site_clean_group'=>array()),
                                'match'=> array('Map\Host'=>array())),
                         'system'=> array()
                       );

        //Get all site settings documents
        $query = new Query();
        $query->filter = new Criterion\ContentTypeIdentifier( 'site_settings' );
        $searchResults = $searchService->findContent( $query );

        foreach ($searchResults->searchHits as $site) {

            $settings = $this->siteSettings($site->valueObject);

            $config['siteaccess']['list'][] = $settings['siteaccess'];
            $config['siteaccess']['groups'][$siteaccessgroup][] = $settings['siteaccess'];
            $config['siteaccess']['match']['Map\Host'][$settings['domain']] = $settings['siteaccess'] ;
            $config['system'][$settings['siteaccess']] = array(
                                                            'content' => array (
                                                                'tree_root' => array(
                                                                    'location_id' => intval($settings['root_location_id']),
                                                                    'excluded_uri_prefixes' => array( '/media' )
                                                                )
                                                            )
                                                        );
        }

        //Dump file
        $dumper = new Dumper();
        $yaml = $dumper->dump($config);
        file_put_contents(__DIR__ . '/../../../../../web/var/dynamicsite/config.yml', $yaml);


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

        //Get settings
        $siteAccess = $this->siteAccessUniquekey( $content );
        $domain = $content->getFieldValue( 'domain' )->text;
        $rootContentInfo = $contentService->loadContentInfo( $content->getFieldValue( 'root_node' )->destinationContentId );
        $rootMainLocationId = $rootContentInfo->mainLocationId;

        return array(   'siteaccess'=>$siteAccess,
                        'domain'=>$domain,
                        'root_location_id'=>$rootMainLocationId);
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
        $urlAlias = $urlAliasService->reverseLookup( $mainLocation );

        //Path - remove first /, replace all / by _
        return str_replace(array('/','-'),'_',substr($urlAlias->path,1));
    }

}
