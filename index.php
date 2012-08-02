<?php 

require_once( "sparqllib.php" );
require_once( "queryHelper.php" );

echo'<?xml version="1.0" encoding="UTF-8"?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="http://code.leafletjs.com/leaflet-0.3.1/leaflet.css" />
    <!--[if lte IE 8]>
        <link rel="stylesheet" href="http://code.leafletjs.com/leaflet-0.3.1/leaflet.ie.css" />
    <![endif]-->
    <link rel="stylesheet" type="text/css" href="http://hxl.carsten.io/style.css" />
    <title>HXL Data Browser</title>

    <link rel="stylesheet" href="css/ui-lightness/jquery-ui-1.8.18.custom.css" type="text/css" media="screen" title="no title" charset="utf-8">
    <script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.8.18.custom.min.js"></script> 
    <script>
    $(function() {
                    $( "#search" ).autocomplete({
                            source: "search.php",
                            minLength: 2,
                            select: function( event, ui ) {
                                    window.location.href = ui.item.value;
                            }
                    });
            });
    </script>
</head>

<body>

<h1><a href="http://hxl.carsten.io">HXL Data Browser</a></h1>

<p>This browser shows data annotated with the <a href="http://hxl.humanitarianresponse.info">Humanitarian eXchange Language</a>. This is a <b>test setup</b> and some of the data shown here may be inaccurate, outdated, or even entirely made up.</p>

<?php 

    $host = $_SERVER['HTTP_HOST'];
    $req = $_SERVER['REQUEST_URI'];
    
    $uri = buildUri ($host, $req);
	
    echo "<h3>Result for $uri:</h3>";
    
    if (!displayQueryResults($uri)){
        echo "<p>The URL doesn't provide with any result. Please, see <a href='/data/emergencies/16107' >a working example</a>.</p>";
    }
    
    
        /*
    if($req === "/"){
        echo "<p>No specific data have been requested; These are the last HXL data containers that have been submitted to get started:</p>";

        getResultsAndShowTable("", "SELECT ?container ?submitted " .
"WHERE { ".
"	GRAPH ?metadata { ".
"		?container a <http://hxl.humanitarianresponse.info/#DataContainer> ; ".
"                  <http://purl.org/dc/terms/created> ?submitted . " .
"	} } ORDER BY DESC(?submitted) LIMIT 10", true, false);
 
 
 
?>
    <p>Your can also search the data we have:</p>
    <div class="demo">
        <div class="ui-widget">
            <label for="search">Search term: </label>
            <input id="search" />
        </div>
    </div>
<?php          
    } else {
        
        /*
		$container = false;
		$result = getQueryResults("SELECT DISTINCT ?type ?label	WHERE { GRAPH ?g {<" . $uri . "> a ?type} GRAPH <http://hxl.carsten.io/graph/hxlvocab> { OPTIONAL { ?type <http://www.w3.org/2004/02/skos/core#prefLabel> ?label } } }");
		while( $row = $result->fetch_array( $result ) ){
			$type = $row["type"];
			$label = $row["label"];
			if($type === "http://parliament.semwebcentral.org/parliament#NamedGraph"){
				// ignore the interals
			}else{
				if($label){
					echo "<h2>Data for <a href='" . $type. "'>" . $label . "</a> with ID <a href='".$uri."'>".$uri."</a></h2>" ; 
				}else{
					echo "<h2>Data for ID <a href='".$uri."'>".$uri."</a></h2>" ; 
				}
				if($type === "http://hxl.humanitarianresponse.info/#DataContainer"){
					// for special handling of data containers:
					$container = true;
				}
			} 
		}
				
		if($container){
			
			echo '<h3>Metadata for this data container:</h3>';

			getResultsAndShowTable($uri, "SELECT ?Predicate ?Label ?Object WHERE {
			  GRAPH ?Graph { <$uri> ?Predicate ?Object . } FILTER (?Predicate != <http://parliament.semwebcentral.org/parliament#graphDirectory> && ?Object != <http://parliament.semwebcentral.org/parliament#NamedGraph>) GRAPH <http://hxl.carsten.io/graph/hxlvocab>{OPTIONAL { ?Predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?Label . }}}", false, false);
			
			echo '<h3>Data in this container:</h3>';
			
			// get all triples in this container (aka. named graph), except those ABOUT the named graph because we already show those metadata above.
			getResultsAndShowTable($uri, "SELECT ?Subject ?Predicate ?Label ?Object WHERE { GRAPH <$uri> { ?Subject ?Predicate ?Object . } GRAPH <http://hxl.carsten.io/graph/hxlvocab>{OPTIONAL { ?Predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?Label.}} FILTER (?Subject != <$uri>) } ORDER BY ?Subject", false, true);
			
		}else{
			echo '<h3>Data about this ID:</h3>';
			
			getResultsAndShowTable($uri, "SELECT ?Predicate ?Label ?Object ?Graph WHERE { GRAPH ?Graph { <$uri> ?Predicate ?Object . } GRAPH <http://hxl.carsten.io/graph/hxlvocab>{OPTIONAL { ?Predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?Label.}}} ORDER BY ?Subject", false, true);
		}
         */

/*
if($mapHTML != ''){
	
	include_once('geoPHP.inc');
	
	echo '<h2>Map</h2>';
	echo '<p>Overview of the geodata attached to the data shown on this page.</p>';
	echo '<div id="map" style="height: 400px"></div>';
	
	// quick and dirty function to convert KML to WKT
	function kml_to_wkt($kml) {
	  $geom = geoPHP::load($kml,'kml');
	  return $geom->out('wkt');
	}
	
//	echo kml_to_wkt(file_get_contents("NC-3815-R21-001.kml"));

	$wkt_reader = new WKT();
	$geometry = $wkt_reader->read($mapHTML,TRUE);
	$centroid = $geometry->centroid();
	$x = $centroid->x();
	$y = $centroid->y();
	$json_writer = new GeoJSON();
	$json_geometry = $json_writer->write($geometry);
	
?>
	<script src="http://code.leafletjs.com/leaflet-0.3.1/leaflet.js"></script>
	<script>
		var map = new L.Map('map');
		var cloudmade = new 	L.TileLayer('http://{s}.tile.cloudmade.com/f6fcfa0ce3c948d683a64fb3fa7833b5/997/256/{z}/{x}/{y}.png', {
		    attribution: 'Map data &copy; <a href=\"http://openstreetmap.org\">OpenStreetMap</a> contributors, <a href=\"http://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>, Imagery © <a href=\"http://cloudmade.com\">CloudMade</a>',
		    maxZoom: 18
		});
		var center = new L.LatLng(<?php echo $y; ?>, <?php echo $x; ?>); // geographical point (longitude and latitude)
		map.setView(center, 6).addLayer(cloudmade);
		var geojsonLayer = new L.GeoJSON();
		geojsonLayer.addGeoJSON(<?php echo $json_geometry; ?>);
		map.addLayer(geojsonLayer);
		
	</script>

<?php
}
 */
?>

</body>
</html>
