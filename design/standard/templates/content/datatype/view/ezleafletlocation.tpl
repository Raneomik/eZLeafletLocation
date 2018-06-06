{* Make sure to normalize floats from db  *}
{def $latitude  = $attribute.content.latitude|explode(',')|implode('.')
     $longitude = $attribute.content.longitude|explode(',')|implode('.')}
{run-once}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css"
          integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ=="
          crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js"
            integrity="sha512-/Nsx9X4HebavoBvEBuyp3I7od5tA0UzAxs+j83KgC8PU0kgB4XiK4Lfe4y4cgBtaRJQEIFCW+oC506aPT2L1zw=="
            crossorigin=""></script>

<script type="text/javascript">
{literal}
function eZLeafletLocation_MapView( attributeId, latitude, longitude )
{
    var   zoom      = 1;

    if( latitude && longitude ) {
        zoom = 9;
    } else {
        latitude  = 0;
        longitude = 0;
    }

    var map = L.map('ezleaflet-map-' + attributeId, {
        zoomControl: false
    }).setView([latitude,longitude ], zoom);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        minZoom: 1,
        maxZoom: 100
    }).addTo(map);

    L.marker( [latitude, longitude] ).addTo( map );

}
{/literal}
</script>
{/run-once}

{if $attribute.has_content}
<script type="text/javascript">
<!--

if ( window.addEventListener )
    window.addEventListener('load', function(){ldelim} eZLeafletLocation_MapView( {$attribute.id}, {first_set( $latitude, '0.0')}, {first_set( $longitude, '0.0')} ) {rdelim}, false);
else if ( window.attachEvent )
    window.attachEvent('onload', function(){ldelim} eZLeafletLocation_MapView( {$attribute.id}, {first_set( $latitude, '0.0')}, {first_set( $longitude, '0.0')} ) {rdelim} );

-->
</script>

<div class="block">
<label>{'Latitude'|i18n('extension/ezleafletlocation/datatype')}:</label> {$latitude}
<label>{'Longitude'|i18n('extension/ezleafletlocation/datatype')}:</label> {$longitude}
  {if $attribute.content.address}
    <label>{'Address'|i18n('extension/ezleafletlocation/datatype')}:</label> {$attribute.content.address}
  {/if}
</div>

<label>{'Map'|i18n('extension/ezleafletlocation/datatype')}:</label>
<div id="ezleaflet-map-{$attribute.id}" style="width: 500px; height: 280px;"></div>
{/if}