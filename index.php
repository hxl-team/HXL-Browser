<?php 

require_once( "lib/sparqllib.php" );
require_once( "queryHelper.php" );

// SWAP THESE FOR PRODUCTION / TESTING!
//$host = "hxl.humanitarianresponse.info";
$host = $_SERVER['HTTP_HOST'];
$req = $_SERVER['REQUEST_URI'];

$uri = "http://".$host.$req;

if(endsWith($uri, ".html")){
	returnHTML(substr($uri, 0, -5));
} else if (endsWith($uri, ".rdf")){
	returnRDF(substr($uri, 0, -4), 'application/rdf+xml');
} else if (endsWith($uri, ".ttl")){
	returnRDF(substr($uri, 0, -4), 'text/turtle');
} else if (endsWith($uri, ".nt")){
	returnRDF(substr($uri, 0, -3), 'text/plain');
} else {

	// Content negotiation:
	// figure out what mime type the client would like to get:
	$mime = getBestSupportedMimeType(Array ('application/xhtml+xml', 'text/html', 'application/rdf+xml', 'text/turtle', 'text/plain'));
	$redirect = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

	if($mime == 'application/xhtml+xml' || $mime == 'text/html'){
		header("HTTP/1.1 303 See Other");
		header("Location: $redirect.html");
	} else if($mime == 'text/turtle'){
		header("HTTP/1.1 303 See Other");
		header("Location: $redirect.ttl");
	} else if($mime == 'text/plain'){
		header("HTTP/1.1 303 See Other");
		header("Location: $redirect.nt");
	} else { // default: RDF/XML
		header("HTTP/1.1 303 See Other");
		header("Location: $redirect.rdf");
	}
}

function returnRDF($uri, $contentType){

	header("Content-Type: $contentType");
	
	if(strpos($uri, "datacontainer") !== false){
		// return the whole contents of the datacontainer via graph store protocol:
		$ch = curl_init("http://hxl.humanitarianresponse.info/graphstore?graph=".$uri);	

		// the graphstore endpoint is password protected:
		$login = file_get_contents('../../store.txt');
		curl_setopt($ch, CURLOPT_USERPWD, $login);
	
	}else{
		// we simply fire a DESCRIBE query against the SPARQL endpoint with the corresponding content type and return the result to the client:
		$ch = curl_init("http://hxl.humanitarianresponse.info/sparql?query=".urlencode("DESCRIBE <".$uri.">"));		
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, array ("Accept: $contentType" ));
	curl_exec($ch);
	curl_close($ch);
}


function returnHTML($uri){	

	global $mapHTML;

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>HXL URI Browser</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- disable cache -->
    <meta http-equiv="expires" content="0"> 
    <meta http-equiv="pragma" content="no-cache"> 
    
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    
    <!-- Leaflet --> 
    <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.4/leaflet.css" />
 <!--[if lte IE 8]>
     <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.4/leaflet.ie.css" />
 <![endif]-->
  <script src="http://cdn.leafletjs.com/leaflet-0.4/leaflet.js"></script>

    <!--<script type='text/javascript' src='lib/leaflet/leaflet-google.js'></script>
    <script type='text/javascript' src='lib/leaflet/Google.js'></script>-->
    <!--<script type='text/javascript' src='http://maps.google.com/maps/api/js?sensor=false&amp;v=3.2'></script>-->

    <link href="http://hxl.humanitarianresponse.info/docs/css/hxl.css" rel="stylesheet"> 
    <style type="text/css">
    	div.uberflow{
    		max-height: 200px;
    		overflow: auto;
    	}

    	h1{
    		margin-top: 30px;
    	}
    </style>


    <title>HXL URI Browser</title>
    <link rel="shortcut icon" href="http://hxl.humanitarianresponse.info/docs/img/favicon.ico">
  </head>

  <body>
	<div class="container">
	<div class="navbar">
        <div class="container">
          <div class="nav-hxlator">
          <span class="brand" style="padding-top: 0"><img src="http://hxl.humanitarianresponse.info/docs/img/logo.png"></span>        
            <ul class="nav" id="topnav">
            	<li><a href="http://hxl.humanitarianresponse.info/docs/">HXL Documentation</a></li>  
            	<li><a href="http://hxl.humanitarianresponse.info/ns/">HXL Vocabulary</a></li>  
            </ul>
          </div>
      </div>
    </div>    
    <div class="container start">	    
    	<div class="row-fluid">
	     	<div class="span12">	     		



<?php 

$dataAvailable = false;
$noQuery = false;
$typeLabel = "";
$label = "";

if ($_SERVER['REQUEST_URI'] === "/data/") {
	$noQuery = true;
} else {
	$container = false;
	$result = getQueryResults("prefix dc: <http://purl.org/dc/terms/> 
prefix skos: <http://www.w3.org/2004/02/skos/core#> 
prefix foaf: <http://xmlns.com/foaf/0.1/> 
prefix hxl: <http://hxl.humanitarianresponse.info/ns/#>

SELECT * WHERE { 
  <" . $uri . "> a ?type .
  
  OPTIONAL {
    ?type skos:prefLabel ?typeLabel .   	
  }

  OPTIONAL { 
    { <" . $uri . "> skos:prefLabel ?label }
    UNION
    { <" . $uri . "> hxl:featureName ?label } 
    UNION
    { <" . $uri . "> foaf:Name ?label } 
    UNION
    { <" . $uri . "> dc:title ?label } 
  } 		          
} LIMIT 1");

	while ( $row = $result->fetch_array( $result ) ){
		$dataAvailable = true;
		$type = $row["type"];

		if ($type === "http://hxl.humanitarianresponse.info/ns/#DataContainer") {
			$container = true;
		}

		if(isset($row["typeLabel"])){
			$typeLabel = $row["typeLabel"];
		}		

		if(isset($row["label"])){
			$label = $row["label"];
		}
	}
}

if ($container) {

	echo '<h4 style="font-weight: bold;" >HXL URI Browser for <a href="'.$type.'">'.$typeLabel.'</a> with URI</h4>
			  <h3><a href="'.$uri.'">'.$uri.'</a></h3>';

	explain($uri);

	echo '<h4>Metadata for this data container:</h4>';

	getResultsAndShowTable("SELECT ?Predicate ?Label ?Object WHERE {
	  GRAPH ?Graph { <$uri> ?Predicate ?Object . } GRAPH <http://hxl.carsten.io/graph/hxlvocab>{OPTIONAL { ?Predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?Label . }}}", false, $uri);
	
	echo '<h4>Data in this container:</h4>';
	
	// get all triples in this container (aka. named graph), except those ABOUT the named graph because we already show those metadata above.
	getResultsAndShowTable("SELECT ?Subject ?Predicate ?Label ?Object WHERE { GRAPH <$uri> { ?Subject ?Predicate ?Object . } GRAPH <http://hxl.carsten.io/graph/hxlvocab>{OPTIONAL { ?Predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?Label.}} FILTER (?Subject != <$uri>) } ORDER BY ?Subject", true, $uri);

} else if ($dataAvailable) {

?> 



<?php 

	if ($label != ""){
		echo '<h4 style="font-weight: bold;" >HXL URI Browser for <a href="'.$type.'">'.$typeLabel.'</a></h4>
			  <h1>'.$label.'</h1>
			  <p><strong>URI: <a href="'.$uri.'">'.$uri.'</a></strong></p>';
	} else{
		echo '<h4 style="font-weight: bold;" >HXL URI Browser for <a href="'.$type.'">'.$typeLabel.'</a> with URI</h4>
			  <h3><a href="'.$uri.'">'.$uri.'</a></h3>';
	}

explain($uri);

	echo "<h4 id=\"mapTitle\" style=\"display:none;\" >Map</h4>";
	echo "<div id=\"map\" style=\"display:none; width: 500px; height: 320px; border:1px solid black;\" ></div>";

	getResultsAndShowTable("SELECT ?Predicate ?Label ?Object ?Graph WHERE { GRAPH ?Graph { <$uri> ?Predicate ?Object . } GRAPH <http://hxl.carsten.io/graph/hxlvocab>{OPTIONAL { ?Predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?Label.}}} ORDER BY ?Subject", true, $uri);

} else if (!$dataAvailable) {

	echo "<br />";
	if ($noQuery) {
		echo "<div class=\"alert alert-info\"><p>No specific data has been requested.<br />To get you started, these are the last HXL data containers that have been submitted:</p></div>";
	} else {
		echo "<div class=\"alert alert-error\"><p>The URI didn't match any successful query or there is no description for it.<br /> You may want to search throught the most recent HXL data containers:</p></div>";
	}

	getResultsAndShowTable("SELECT ?container " .
	"WHERE { ".
	"	GRAPH ?metadata { ".
	"		?container a <http://hxl.humanitarianresponse.info/ns/#DataContainer> ; ".
	"	} } ORDER BY DESC(?submitted) LIMIT 10", false, $uri);
} else {
	
	echo "<br />";
	echo "<p><div class=\"alert alert-error\">An unexpected result occurred.<br />You may want to search throught the most recent HXL data containers:</p></div>";

	getResultsAndShowTable("SELECT ?container " .
	"WHERE { ".
	"	GRAPH ?metadata { ".
	"		?container a <http://hxl.humanitarianresponse.info/ns/#DataContainer> ; ".
	"	} } ORDER BY DESC(?submitted) LIMIT 10", false, $uri);

}


if ($mapHTML != '') {

	include_once('lib/geoPHP.inc');

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
	document.getElementById('map').style.width = '100%';
	//document.getElementById('mapTitle').style.display = "block";

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
</div>
</div>
</div>
<div class="container footer">
		<div class="row">
			<div class="span3"><strong>Contact</strong><br />
		  	This site is part of the HumanitarianResponse network. Write to 
		  	<a href="mailto:info@humanitarianresponse.info">info@humanitarianresponse.info</a> for more information.</div>		  
      		<div class="span3"><strong>Elsewhere</strong><br />
      		The entire code for HXL and the tools we are building around the standard is available on <a href="https://github.com/hxl-team">GitHub</a>.</div>      
		  	<div class="span3"><strong>Legal</strong><br />
		  	&copy; 2012 UNOCHA</div>
		  	<div class="span3"></div>
		</div>
	</div>	
  </body>
</html>

<?php 

	} // end getHTML 
?>
