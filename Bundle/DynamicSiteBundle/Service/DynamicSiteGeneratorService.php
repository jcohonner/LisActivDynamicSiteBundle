<?php
/**
 * File containing the PlaceHelper class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace LisActiv\Bundle\DynamicSiteBundle\Service;

use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;

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

    public function __construct(
        LocationService $locationService,
        SearchService $searchService
    )
    {
        $this->locationService = $locationService;
        $this->searchService = $searchService;
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


        $location = $this->locationService->loadLocation( 2 );

        $query = new Query();
        $query->filter = new Criterion\ContentTypeIdentifier( 'site' );

        /*new Criterion\LogicalAnd(
            array(
                new Criterion\ContentTypeIdentifier( 'site' ),
                new Criterion\Subtree( $location->pathString ),
            )
        );*/



        $searchResults = $this->searchService->findContent( $query );
//var_dump($searchResults);
        foreach ($searchResults->searchHits as $site) {
            var_dump($site->valueObject->id);
        }

        return '';
    }


}
