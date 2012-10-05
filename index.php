
    <?php 

require_once( "lib/sparqllib.php" );
require_once( "queryHelper.php" );

//echo'<?xml version="1.0" encoding="UTF-8" ? > ';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="shortcut icon" href="http://hxl.humanitarianresponse.info/data/img/ochaonline_theme_favicon.ico">

    <!-- jQuery -->
    <script type='text/javascript' src="http://hxl.humanitarianresponse.info/data/lib/jquery-1.8.2.min.js"></script>

    <!-- Bootstrap -->
    <link href="http://hxl.humanitarianresponse.info/data/lib/bootstrap/css/bootstrap.css" rel="stylesheet">
    <script type='text/javascript' src="http://hxl.humanitarianresponse.info/data/lib/bootstrap/js/bootstrap.js"></script>

    <!-- Leaflet --> 
    <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.4/leaflet.css" />
 <!--[if lte IE 8]>
     <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.4/leaflet.ie.css" />
 <![endif]-->
  <script src="http://cdn.leafletjs.com/leaflet-0.4/leaflet.js"></script>

    <!--<script type='text/javascript' src='lib/leaflet/leaflet-google.js'></script>
    <script type='text/javascript' src='lib/leaflet/Google.js'></script>-->
    <!--<script type='text/javascript' src='http://maps.google.com/maps/api/js?sensor=false&amp;v=3.2'></script>-->


	<script src="http://hxl.humanitarianresponse.info/data/js/browserDetection.js"></script>

    <link href="http://hxl.humanitarianresponse.info/data/css/style.css" rel="stylesheet"> 

    <title>HXL URI Browser</title>

</head>

<body>

<a href="http://hxl.humanitarianresponse.info/data/" class="btn btn-large" ><h1>HXL URI Browser</h1></a>
<br />
<br />
<br />
<p>
	This browser shows data annotated with the <a href="http://hxl.humanitarianresponse.info">Humanitarian eXchange Language</a>.<br />
	This is a <b>test setup</b> and some of the data shown here may be inaccurate, outdated, or even entirely made up.
</p>

<?php 

$host = $_SERVER['HTTP_HOST'];
$req = $_SERVER['REQUEST_URI'];
$uri = "http://".$host.$req;

if ($req === "/data/") {
	echo "<p>No specific data have been requested; These are the last HXL data containers that have been submitted to get started:</p>";
	
	getResultsAndShowTable("", "SELECT ?container " .
	"WHERE { ".
	"	GRAPH ?metadata { ".
	"		?container a <http://hxl.humanitarianresponse.info/ns/#DataContainer> ; ".
	"	} } ORDER BY DESC(?submitted) LIMIT 10", true, false, $uri);
	?>

<?php 
} else {
	$container = false;
	$result = getQueryResults("SELECT DISTINCT ?type ?label	WHERE { GRAPH ?g {<" . $uri . "> a ?type} GRAPH <http://hxl.carsten.io/graph/hxlvocab> { OPTIONAL { ?type <http://www.w3.org/2004/02/skos/core#prefLabel> ?label } } }");

	while ( $row = $result->fetch_array( $result ) ) {
		$type = $row["type"];
		$label = $row["label"];

		if ($label) {
			echo "<h4>Data for <a href='" . $type. "'>" . $label . "</a> with ID <a href='".$uri."'>".$uri."</a></h4>" ; 
		} else if ($type === "http://hxl.humanitarianresponse.info/ns/#DataContainer") {
			// for special handling of data containers:
			$container = true;
		} else {
			echo "<h3>Result for ID <a href='".$uri."'>".$uri."</a></h3>" ; 
		}
	}
?>

<h4 id="mapTitle" style="display:none;" >Map</h4>
<div id="map" style="display:none; width: 500px; height: 320px"></div>

<?php
	if ($container) {
		
		echo '<h4>Metadata for this data container:</h4>';

		getResultsAndShowTable($uri, "SELECT ?Predicate ?Label ?Object WHERE {
		  GRAPH ?Graph { <$uri> ?Predicate ?Object . } GRAPH <http://hxl.carsten.io/graph/hxlvocab>{OPTIONAL { ?Predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?Label . }}}", false, false, $uri);
		
		echo '<h4>Data in this container:</h4>';
		
		// get all triples in this container (aka. named graph), except those ABOUT the named graph because we already show those metadata above.
		getResultsAndShowTable($uri, "SELECT ?Subject ?Predicate ?Label ?Object WHERE { GRAPH <$uri> { ?Subject ?Predicate ?Object . } GRAPH <http://hxl.carsten.io/graph/hxlvocab>{OPTIONAL { ?Predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?Label.}} FILTER (?Subject != <$uri>) } ORDER BY ?Subject", false, true, $uri);
		
	} else {
		
		echo '<h4>Data:</h4>';
		getResultsAndShowTable($uri, "SELECT ?Predicate ?Label ?Object ?Graph WHERE { GRAPH ?Graph { <$uri> ?Predicate ?Object . } GRAPH <http://hxl.carsten.io/graph/hxlvocab>{OPTIONAL { ?Predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?Label.}}} ORDER BY ?Subject", false, true, $uri);
	}
}


if ($mapHTML != '') {
	include_once('lib/geoPHP.inc');
/*		
	// quick and dirty function to convert KML to WKT
	function kml_to_wkt($kml) {
	  $geom = geoPHP::load($kml,'kml');
	  return $geom->out('wkt');
	}
*/	
//	echo kml_to_wkt(file_get_contents("NC-3815-R21-001.kml"));

	$wkt_reader = new WKT();
	$geometry = $wkt_reader->read($mapHTML,TRUE);
	$centroid = $geometry->centroid();
	$x = $centroid->x();
	$y = $centroid->y();
	$json_writer = new GeoJSON();
	$json_geometry = $json_writer->write($geometry);
	
?>
<script>
	document.getElementById('map').style.display = 'block';
	document.getElementById('mapTitle').style.display = "block";

/*
        googleLayer = new L.Google('ROADMAP');
        map.addLayer(googleLayer);

    map.setView([12.367838, -1.530247], 6);
*/
var map = new L.map('map');
	var cloudmade = new L.TileLayer('http://{s}.tile.cloudmade.com/{key}/997/256/{z}/{x}/{y}.png', {
	    attribution: 'Map data &copy; 2011 OpenStreetMap contributors, Imagery &copy; 2012 CloudMade',
		key: 'f6fcfa0ce3c948d683a64fb3fa7833b5',
	    maxZoom: 18
	});
	var center = new L.LatLng(<?php echo $y; ?>, <?php echo $x; ?>); // geographical point (longitude and latitude)
	map.setView(center, 6).addLayer(cloudmade);
	var geojsonLayer = new L.GeoJSON();

	var myLayer = [<?php echo $json_geometry; ?>];

var myStyle = {
    "weight": 5,
    "opacity": 0.65
};
L.geoJson(myLayer, {
    style: myStyle
}).addTo(map);

</script>

<?php
}
?>

</body>
</html>
