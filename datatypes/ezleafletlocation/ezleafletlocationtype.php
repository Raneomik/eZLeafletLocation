<?php
//
// Definition of eZLeafletLocationType class
//
// SOFTWARE NAME: Smile Leaflet Location Class
// SOFTWARE RELEASE: 1.3.1
// COPYRIGHT NOTICE: Copyright (C) 2006-2009 Smile
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//

/**
 * File containing the eZLeafletLocationType class.
 *
 * @package eZDatatype
 */

/**
 * Class providing the eZLeafletLocationType datatype.
 *
 * @package eZDatatype
 * @see eZLeafletLocation
 */
class eZLeafletLocationType extends eZDataType
{
    const DATA_TYPE_STRING = 'ezleafletlocation';

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct( self::DATA_TYPE_STRING, ezpI18n::tr( 'extension/ezleafletlocation/datatype', "Leaflet Location", 'Datatype name' ),
                           array( 'serialize_supported' => true ) );
    }

    /**
     * Validate post data, these are then used by
     * {@link eZLeafletLocationType::fetchObjectAttributeHTTPInput()}
     *
     * @param eZHTTPTool $http
     * @param string $base
     * @param eZContentObjectAttribute $contentObjectAttribute
     */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $latitude = '';
        $longitude = '';
        $classAttribute = $contentObjectAttribute->contentClassAttribute();
    	if ( $http->hasPostVariable( $base . '_data_leafletlocation_longitude_' . $contentObjectAttribute->attribute( 'id' ) ) &&
             $http->hasPostVariable( $base . '_data_leafletlocation_longitude_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {

            $latitude = $http->postVariable( $base . '_data_leafletlocation_longitude_' . $contentObjectAttribute->attribute( 'id' ) );
            $longitude = $http->postVariable( $base . '_data_leafletlocation_longitude_' . $contentObjectAttribute->attribute( 'id' ) );
        }

        if ( $latitude === '' || $longitude === '' )
        {
            if ( !$classAttribute->attribute( 'is_information_collector' ) && $contentObjectAttribute->validateIsRequired() )
            {
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'extension/ezleafletlocation/datatype',
                                                                     'Missing Latitude/Longitude input.' ) );
                return eZInputValidator::STATE_INVALID;
            }
        }
        else if ( !is_numeric( $latitude ) || !is_numeric( $longitude ) )
        {
            $contentObjectAttribute->setValidationError( ezpI18n::tr( 'extension/ezleafletlocation/datatype',
                                                                 'Invalid Latitude/Longitude input.' ) );
            return eZInputValidator::STATE_INVALID;
        }

        return eZInputValidator::STATE_ACCEPTED;
    }

    /**
     * Set parameters from post data, expects post data to be validated by
     * {@link eZLeafletLocationType::validateObjectAttributeHTTPInput()}
     *
     * @param eZHTTPTool $http
     * @param string $base
     * @param eZContentObjectAttribute $contentObjectAttribute
     */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $validPostData = false;

    	if ( $http->hasPostVariable( $base . '_data_leafletlocation_latitude_' . $contentObjectAttribute->attribute( 'id' ) )
          && $http->hasPostVariable( $base . '_data_leafletlocation_longitude_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
    	    $latitude = $http->postVariable( $base . '_data_leafletlocation_latitude_' . $contentObjectAttribute->attribute( 'id' ) );
            $longitude = $http->postVariable( $base . '_data_leafletlocation_longitude_' . $contentObjectAttribute->attribute( 'id' ) );
            $validPostData = $latitude !== '' && $longitude !== '' && is_numeric( $latitude ) && is_numeric( $longitude );
        }

        if ( $validPostData )
        {
            $address = '';
        	if ( $http->hasPostVariable( $base . '_data_leafletlocation_address_' . $contentObjectAttribute->attribute( 'id' ) ) )
            {
                $address = $http->postVariable( $base . '_data_leafletlocation_address_' . $contentObjectAttribute->attribute( 'id' ) );
                $address = htmlentities( $address, ENT_QUOTES , 'UTF-8' );
            }

            if ( $contentObjectAttribute->attribute( 'data_int' ) != 0 )
            {
            	$location = eZLeafletLocation::fetch( $contentObjectAttribute->attribute('id'), $contentObjectAttribute->attribute('version') );
            	$location->setAttribute( 'latitude', $latitude );
            	$location->setAttribute( 'longitude', $longitude );
            	$location->setAttribute( 'address', $address );
            }
            else
            {
		        $location = new eZLeafletLocation( array(
		                        'contentobject_attribute_id' => $contentObjectAttribute->attribute('id'),
		                        'contentobject_version' => $contentObjectAttribute->attribute('version'),
		                        'latitude' => $latitude,
		                        'longitude' => $longitude,
		                        'address' => $address
		                         ) );
		        $contentObjectAttribute->setAttribute( 'data_int', 1 );
            }
	       $contentObjectAttribute->setContent( $location );
        }
        else if ( $contentObjectAttribute->attribute( 'data_int' ) != 0 )
        {
        	$contentObjectAttribute->setAttribute( 'data_int', 0 );
        	eZLeafletLocation::removeById( $contentObjectAttribute->attribute('id'), $contentObjectAttribute->attribute('version') );
        }
        return true;
    }

    /**
     * Stores the content, as set by {@link eZLeafletLocationType::fetchObjectAttributeHTTPInput()}
     * or {@link eZLeafletLocationType::initializeObjectAttribute()}
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return bool
     */
    function storeObjectAttribute( $contentObjectAttribute )
    {
        if ( $contentObjectAttribute->attribute( 'data_int' ) != 0 )
        {
    	   $gp = $contentObjectAttribute->attribute( 'content' );
	        if ( $gp instanceof eZLeafletLocation )
	        {
	            $gp->store();
	            return true;
	        }
        }
        return false;
    }

    /**
     * Init attribute ( also handles version to version copy, and attribute to attribute copy )
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param int|null $currentVersion
     * @param eZContentObjectAttribute $originalContentObjectAttribute
     */
    function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
    {
        if ( $currentVersion == false )
        {
            $contentObjectAttribute->setAttribute( 'data_int', 0 );
        }
        else if (  $contentObjectAttribute->attribute( 'id' ) != 0  )
        {
            if ( $originalContentObjectAttribute->attribute( 'data_int' ) != 0 )
            {
                $lealfetObject = $originalContentObjectAttribute->attribute( 'content' );
                $lealfetObject->setAttribute( 'contentobject_attribute_id', $contentObjectAttribute->attribute( 'id' ) );//in case of copy
                $lealfetObject->setAttribute( 'contentobject_version', $contentObjectAttribute->attribute( 'version' ) );
                $contentObjectAttribute->setContent( $lealfetObject );
                $contentObjectAttribute->store();
            }
        }
    }

    /**
     * Return content (eZLeafletLocation object), either stored one or a new empty one based on
     * if attribute has data or not (as signaled by data_int)
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return eZLeafletLocation
     */
    function objectAttributeContent( $contentObjectAttribute )
    {
        if ( $contentObjectAttribute->attribute( 'data_int' ) != 0 )
        {
           $lealfetObject = eZLeafletLocation::fetch( $contentObjectAttribute->attribute( 'id' ), $contentObjectAttribute->attribute( 'version' ) );
           if ( !$lealfetObject instanceof eZLeafletLocation )
           {
               $lealfetObject = eZLeafletLocation::create( $contentObjectAttribute->attribute( 'id' ), $contentObjectAttribute->attribute( 'version' ), '', '' );
               // This happens when attribute is cloned, since sync() is called before init() on attribute.
               //eZDebug::writeWarning( 'eZLeafletLocation::fetch returned empty, even though data_int says there is content. Might be data corruption! ' . var_export(debug_backtrace( false ), true), __METHOD__ );
           }
        }
        else
        {
    	   $lealfetObject = eZLeafletLocation::create( $contentObjectAttribute->attribute( 'id' ), $contentObjectAttribute->attribute( 'version' ), '', '' );
        }

        return $lealfetObject;
    }

    /**
     * Indicates if attribute has content or not (data_int is used for this)
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return bool
     */
    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( 'data_int' ) != 0;
    }

    /**
     * Generate meta data of attribute
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return string
     */
    function metaData( $contentObjectAttribute )
    {
        $lealfetObject = $contentObjectAttribute->attribute( 'content' );
        return $lealfetObject->attribute( 'address' );
    }

    /**
     * Indicates that datatype is searchable {@link eZLeafletLocationType::metaData()}
     *
     * @return bool
     */
    function isIndexable()
    {
        return true;
    }

    /**
     * Returns sort value for attribute
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return string
     */
    function sortKey( $contentObjectAttribute )
    {
        $lealfetObject = $contentObjectAttribute->attribute( 'content' );
        return $lealfetObject->attribute( 'address' );
    }

    /**
     * Tells what kind of sort value is returned, see {@link eZLeafletLocationType::sortKey()}
     *
     * @return string
     */
    function sortKeyType()
    {
        return 'string';
    }

    /**
     * Return string data for cosumption by {@link eZLeafletLocationType::fromString()}
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return string
     */
    function toString( $contentObjectAttribute )
    {
        $lealfetObject = $contentObjectAttribute->attribute( 'content' );
        return $contentObjectAttribute->attribute( 'data_int' ) . '|#' . $lealfetObject->attribute( 'latitude' ) . '|#' . $lealfetObject->attribute( 'longitude' ) . '|#' . $lealfetObject->attribute( 'address' );
    }

    /**
     * Store data from string format as created in  {@link eZLeafletLocationType::toString()}
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param string $string
     */
    function fromString( $contentObjectAttribute, $string )
    {
    	$data = $string !== '' && strpos( $string, '|#' ) !== false ? explode( '|#', $string ) : array( 0 );

    	if ( $data[0] != 0 )
    	{
            $location = new eZLeafletLocation( array(
                            'contentobject_attribute_id' => $contentObjectAttribute->attribute('id'),
                            'contentobject_version' => $contentObjectAttribute->attribute('version'),
                            'latitude' => $data[1],
                            'longitude' => $data[2],
                            'address' => $data[3]
                         ));
            $contentObjectAttribute->setContent( $location );
    	}
    	$contentObjectAttribute->setAttribute( 'data_int', $data[0] );
    }

    /**
     * Generate title of attribute
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param string|null $name
     * @return string
     */
    function title( $contentObjectAttribute, $name = null )
    {
        $lealfetObject = $contentObjectAttribute->attribute( 'content' );
        return $lealfetObject->attribute( 'address' );
    }

    /**
     * Delete map location data when attribute (version) is removed
     *
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param int|null $version (Optional, deletes all versions if null)
     */
    function deleteStoredObjectAttribute( $contentObjectAttribute, $version = null )
    {
    	eZLeafletLocation::removeById( $contentObjectAttribute->attribute('id'), $version );
    }

    /**
     * @see eZDataType::serializeContentObjectAttribute
     */
    function serializeContentObjectAttribute( $package, $contentObjectAttribute )
    {
        $node = $this->createContentObjectAttributeDOMNode( $contentObjectAttribute );
        $node->setAttribute( 'ezgl_has_content', $contentObjectAttribute->attribute( 'data_int' ) );

        if ( $contentObjectAttribute->attribute( 'data_int' ) != 0 )
        {
        	$dom = $node->ownerDocument;
        	$lealfet = $contentObjectAttribute->attribute( 'content' );

        	$latitude = $dom->createElement( 'latitude' );
        	$latitude->appendChild( $dom->createTextNode( $lealfet->attribute('latitude') ) );
        	$node->appendChild( $latitude );

        	$longitude = $dom->createElement( 'longitude' );
            $longitude->appendChild( $dom->createTextNode( $lealfet->attribute('longitude') ) );
            $node->appendChild( $longitude );

            $address = $dom->createElement( 'address' );
            $address->appendChild( $dom->createTextNode( $lealfet->attribute('address') ) );
            $node->appendChild( $address );
        }

        return $node;
    }

    /**
     * @see eZDataType::unserializeContentObjectAttribute
     */
    function unserializeContentObjectAttribute( $package, $contentObjectAttribute, $attributeNode )
    {
        $contentObjectAttribute->setAttribute( 'data_int', $attributeNode->getAttribute( 'ezgl_has_content' ) );

    	if ( $attributeNode->getAttribute( 'ezgl_has_content' ) != 0 )
        {
            $location = new eZLeafletLocation( array(
                            'contentobject_attribute_id' => $contentObjectAttribute->attribute('id'),
                            'contentobject_version' => $contentObjectAttribute->attribute('version'),
                            'latitude' => $attributeNode->getElementsByTagName( 'latitude' )->item( 0 )->textContent,
                            'longitude' => $attributeNode->getElementsByTagName( 'longitude' )->item( 0 )->textContent,
                            'address' => $attributeNode->getElementsByTagName( 'address' )->item( 0 )->textContent
                         ));
            $contentObjectAttribute->setContent( $location );
        }
    }
}

eZDataType::register( eZLeafletLocationType::DATA_TYPE_STRING, 'eZLeafletLocationType' );

?>
