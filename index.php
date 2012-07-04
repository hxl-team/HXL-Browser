<?php 

require_once( "sparqllib.php" );

$namespaces = array(
    "foaf" => "http://xmlns.com/foaf/0.1/",
    "dct" => "http://purl.org/dc/terms/",
    "hxl" => "http://hxl.humanitarianresponse.info/#",
    "skos" => "http://www.w3.org/2004/02/skos/core#"
);

$mapHTML = '';

function getQueryResults($query){
	
	$db = sparql_connect( "http://83.169.33.54:8080/parliament/sparql" );
	if( !$db ) { print $db->errno() . ": " . $db->error(). "\n"; exit; }
	
//	foreach ($namespaces as $short => $long) {
//		$db->ns( $short, $long );
//	}

	$result = $db->query( $query ); 
	if( !$result ) { print $db->errno() . ": " . $db->error(). "\n"; exit; }

	return $result;
	
}

function isDateProp($prop){
	$query = "ASK { GRAPH <http://hxl.carsten.io/graph/hxlvocab>{<$prop> <http://www.w3.org/2000/01/rdf-schema#isDefinedBy> <http://hxl.humanitarianresponse.info/#datetimeSection>}}";
	$result = getQueryResults($query);
	echo($result->fetch_array( $result ));
}

function getMapData($resource){
	global $mapHTML;	
	
	
	$query = "SELECT ?feature ?wkt WHERE { GRAPH ?g1 {<$resource> <http://hxl.humanitarianresponse.info/#hasGeometry> ?feature} 
		GRAPH ?g2 {?feature <http://hxl.humanitarianresponse.info/#asWKT> ?wkt}}";
	
//	echo $query;
	
	$result = getQueryResults($query);
	while( $row = $result->fetch_array( $result ) ){		
//		$mapHTML .= $resource . " at feature " . $row['feature'] . "; WKT: ". $row['wkt'] .'<br />';
		$mapHTML .= $row['wkt'];
	}
	
}

// if there is a result field "Predicate", this function will look for a result field "Label" in the same row and try to display the label
function getResultsAndShowTable($highlight , $query , $showHeaders, $group){	


	$result = getQueryResults($query);
	
	$fields = $result->field_array( $result );
	

	print "<table>";
	print "<tr>";
	
	if($showHeaders){
	foreach( $fields as $field ){
		if($field == "Graph"){
			print "<th></th>";
		}else if($field == "Label"){
			// nada
		}else{
			print "<th>$field</th>";
		}
	}}
	
	print "</tr>";
	$lastsubject = "";
	
	while( $row = $result->fetch_array( $result ) )
	{
		$isDate = false;
		print "<tr>";
		foreach( $fields as $field )
		{			
			if($row[$field] === 'http://hxl.humanitarianresponse.info/#atLocation'){
				getMapData($row["Object"]);											
			}
			
			if($field == "Label"){
				//nada - skip this field; we'll show the Label for Predicate
				continue;
			}else if ($field == "Predicate"){
				// use the label if there is one
				if($row["Label"]){
					$value=$row["Label"];
					$link=$row["Predicate"];
				}else{
						$value=$row[$field];
						$link=$row[$field];
					}

			}else if ($field == "Subject"){
				// if grouping is on, only show the subject once:
				if($group){
					if($lastsubject != $row["Subject"]){
						$lastsubject = $row["Subject"];					
						$value=$row[$field];
						$link=$row[$field];					
					}else{
						$value="";
						$link="";
					}
				}
			}else{
				$value=$row[$field];
				$link=$row[$field];
			}
			$high = "";
			if($value === $highlight){
				$high = " class='highlight'";
			}
			if(substr($link,0,7) == 'http://'){
				
				if(isDateProp($link)){
					$isDate = true;
				}
				
				if($field == "Graph"){
					$value = "<small><a href='$value'$high>[Metadata]</a></small>";
				}else{
					if(($field == "Predicate") && (substr($value,0,7) == 'http://')){
						// strip URIs of external vocabularies
						$frags = explode("#", $value);
						if(count($frags) == 2){
							$value = $frags[1]; // hash uris
						}else if (count($frags) == 1){
							$frags = explode("/", $value);
							$value = $frags[count($frags)-1]; // slash URIs
						}
						
						$value = "<a href='$link'$high>$value</a>";
					}else{
						if(substr($value,0,22) == 'http://hxl.carsten.io/'){
							$value = str_replace('http://hxl.carsten.io/', '', $value);
							$value = str_replace('/', ':', $value);
						}else if(substr($value,0,38) == 'http://hxl.humanitarianresponse.info/#'){
							$value = str_replace('http://hxl.humanitarianresponse.info/#', 'hxl:', $value);
						}
						$value = "<a href='$link'$high>$value</a>";
					}
				}				
			}else{
				// try to format date string:
				if($value.length > 0 && $isDate){
					try {
				   		$date = new DateTime($value);
						$value = $date->format('M d, Y \a\t H:i:s');
					} catch (Exception $e) { }
				}				
			}
			print "<td>$value</td>";
		}
		print "</tr>";
	}
	print "</table>";
}

echo'<?xml version="1.0" encoding="UTF-8"?>'; ?>
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
	$uri = "http://".$host.$req;
	
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
	}else{
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
}

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
?>

</body>
</html>
