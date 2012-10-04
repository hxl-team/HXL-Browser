<?php 

require_once( "sparqllib.php" );
require_once( "queryHelper.php" );

echo'<?xml version="1.0" encoding="UTF-8"?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="shortcut icon" href="http://hxl.humanitarianresponse.info/data/img/ochaonline_theme_favicon.ico">

    <!-- jQuery -->
    <script type='text/javascript' src="http://hxl.humanitarianresponse.info/data/lib/jquery-1.8.2.min.js"></script>

    <!-- Bootstrap -->
    <link href="http://hxl.humanitarianresponse.info/data/lib/bootstrap/css/bootstrap.css" rel="stylesheet">
    <script type='text/javascript' src="http://hxl.humanitarianresponse.info/data/lib/bootstrap/js/bootstrap.js"></script>

    <!-- Leaflet -->
    <link rel="stylesheet" href="http://hxl.humanitarianresponse.info/data/lib/leaflet/leaflet.css" />
	<!--[if lte IE 8]>
	    <link rel="stylesheet" href="http://hxl.humanitarianresponse.info/data/lib/leaflet/leaflet.ie.css" />
	<![endif]-->
	<script src="http://hxl.humanitarianresponse.info/data/lib/leaflet/leaflet.js"></script>


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
	"	} } ORDER BY DESC(?submitted) LIMIT 10", true, false);
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
			echo "<h4>Data for ID <a href='".$uri."'>".$uri."</a></h4>" ; 
		}
	}
			
	if ($container) {
		
		echo '<h4>Metadata for this data container:</h4>';

		getResultsAndShowTable($uri, "SELECT ?Predicate ?Label ?Object WHERE {
		  GRAPH ?Graph { <$uri> ?Predicate ?Object . } GRAPH <http://hxl.carsten.io/graph/hxlvocab>{OPTIONAL { ?Predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?Label . }}}", false, false);
		
		echo '<h4>Data in this container:</h4>';
		
		// get all triples in this container (aka. named graph), except those ABOUT the named graph because we already show those metadata above.
		getResultsAndShowTable($uri, "SELECT ?Subject ?Predicate ?Label ?Object WHERE { GRAPH <$uri> { ?Subject ?Predicate ?Object . } GRAPH <http://hxl.carsten.io/graph/hxlvocab>{OPTIONAL { ?Predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?Label.}} FILTER (?Subject != <$uri>) } ORDER BY ?Subject", false, true);
		
	} else {
		

		getResultsAndShowTable($uri, "SELECT ?Predicate ?Label ?Object ?Graph WHERE { GRAPH ?Graph { <$uri> ?Predicate ?Object . } GRAPH <http://hxl.carsten.io/graph/hxlvocab>{OPTIONAL { ?Predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?Label.}}} ORDER BY ?Subject", false, true);
	}
}

if ($mapHTML != '') {
	
	include_once('geoPHP.inc');
	
	echo '<h4>Map</h4>';
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
<script>
	var map = new L.map('map');
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
?>

</body>
</html>
