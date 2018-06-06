
eZLeafletLocation Datatype Extension
Version 1.x by eZ Systems AS

Cloned and adapted from [EZGmapLocation Extension](https://github.com/ezsystems/ezgmaplocation-ls-extension)


Version 0.6 developed by [Smile](https://smile.eu)

------------------------------------------------------------------

The LeafletLocation datatype extension provides a handy way to store
latitude/longitude points (as decimal degrees) on an object by using
Leafletjs to identify and mark positions using their address.


Installation
---------------

1.) Upload the ezleafletlocation folder to the extensions folder in your
eZ Publish installation.

2.) Activate the extension from the 'Extensions' portion of the
'Setup' tab in the eZ publish admin interface.
And update the autoload array by clicking "Regenerate autoload arrays for extensions"

**Project configuration editing via BO interface is discouraged :**
- in your "settings/override/site.ini.append.php" add those lines :
```
ActiveExtensions[]=ezleafletlocation #under [ExtensionSettings]
...
TranslationExtensions[]=ezleafletlocation #under [RegionalSettings]
```
- clear caches ("ini" mainly)
- and run ```php bin/php/ezpgenerateautoloads.php```


3.) Apply the ezleafletlocation table to your database with the included sql file:
```extension/ezleafletlocation/sql/mysql/mysql.sql```
Using either phpmyadmin (easiest) or shell/console commands.

Sql files are also provided for postgressql and oracle - refer to the
database documentation on how to execute queries from a command-line clients

4.) Now you can add the ezleafletlocation datatype like any other datatype when editing classes.

5.) If you wish to quickly replace your current eZGmapLocation class and object attributes, you can run this script :
``` extension/ezleafletlocation/bin/updateleafletfromgmaps.php ```

**Backup your data before using it**

Use (editing)
---------------
1. Type in the address you want to find beneath the map.
2. Click 'Find address'
or
Type in longitude / latitude input
3. Click 'Update Values' to grab the coordinates.

Repeat to change.
Unless you change the marker on the map, the address will be saved as you typed it.
This address is searchable! (see bellow for filtering / sorting on coordinates as well)

![ezleafletdemo](https://j.gifs.com/nrWv35.gif)


Use (fetching)
---------------

For fetching multiple nodes based on location, you can use the included ezlfltLocationFilter.
Example fetches users in a distance of roughly 30-50km from Oslo to Norway. And sorts the
results based on how close the nodes are to the given coordinate.

```smarty
{def $users_close_by = fetch( 'content', 'tree', hash(
                              'parent_node_id', 12,
                              'limit', 3,
                              'sort_by', array( 'distance', true() ),
                              'class_filter_type', 'include',
                              'class_filter_array', array( 'user' ),
                              'extended_attribute_filter', hash( 'id', 'ezlfltLocationFilter', 'params', hash( 'latitude', 59.917,
                                                                                                              'longitude', 10.729,
                                                                                                              'distance', 0.5 ) )
                              ) )}
```

 Note that the distance filter is using a 'bounding box' for sql speed, see classes/ezlfltlocationfilter.php for more info
 and paramerters to be able to get true ('arccosine') or closer to true ('pythagorean') circular distance filter accuracy.
 The sort on the other hand is accurate, so if your main concerne is to show closets node, then the filter is ok by default.
 
 Also see 'arccosine' parameter in combination with 'as_object', false() to be able to get value to easily calculate distance.
 Example use: user x is 2,5 km away from you. Or Oslo, Norway is 416.8km from Stockholm, Sweden.

