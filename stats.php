<?php 

require_once( "sparqllib.php" );

$namespaces = array(
    "foaf" => "http://xmlns.com/foaf/0.1/",
    "dct" => "http://purl.org/dc/terms/",
    "hxl" => "http://hxl.humanitarianresponse.info/#",
    "skos" => "http://www.w3.org/2004/02/skos/core#"
);

function getQueryResults($query){
	
	$db = sparql_connect( "http://83.169.33.54:8080/parliament/sparql" );
	if( !$db ) { print $db->errno() . ": " . $db->error(). "\n"; exit; }
	
	$result = $db->query( $query ); 
	if( !$result ) { print $db->errno() . ": " . $db->error(). "\n"; exit; }

	return $result;
	
}


echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
 	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  	<link rel="stylesheet" type="text/css" href="http://hxl.carsten.io/style.css" />
  	<title>HXL Statistics</title>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">

      // Load the Visualization API and the piechart package.
      google.load('visualization', '1.0', {'packages':['corechart']});

      // Set a callback to run when the Google Visualization API is loaded.
      google.setOnLoadCallback(drawChart);

      // Callback that creates and populates a data table,
      // instantiates the pie chart, passes in the data and
      // draws it.
      function drawChart() {
		var data = new google.visualization.DataTable();
	
		data.addColumn('string', 'Organisation');
		data.addColumn('number', 'HXL Reports');

<?php

$query = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> 
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
PREFIX hxl: <http://hxl.humanitarianresponse.info/#>
SELECT (COUNT(DISTINCT ?report) as ?count) ?name 
WHERE {    
	GRAPH ?g1 {
		  ?org rdf:type/rdfs:subClassOf* hxl:Organisation . }    
	GRAPH ?g2 {         
		  ?org hxl:orgDisplayName ?name . }   
	GRAPH ?g3 {         
		  ?report hxl:reportedBy ?org . } 
} GROUP BY ?name';

$result = getQueryResults($query);

$fields = $result->field_array( $result );

	$html = 'data.addRows([
		';
while( $row = $result->fetch_array( $result ) ){
	$html .= '[\''.$row['name'].'\', '.$row['count'].' ], 
';		
}

$html = substr($html, 0, -3) . '
	        ]);' ;

echo $html;

?>

	var options = {'title':'Reports by organisation',
                       'width':600,
                       'height':500};

	var chart = new google.visualization.PieChart(document.getElementById('org_div'));
	chart.draw(data, options);
	
	
	
	// next chart:
	
	
	data = new google.visualization.DataTable();
	
	        data.addColumn('string', 'Project');
	        data.addColumn('number', 'Number of Beneficiaries');
	
			<?php

			$query = 'PREFIX hxl: <http://hxl.humanitarianresponse.info/#>
			SELECT * WHERE { 
			   GRAPH ?g1 {
			         ?act a hxl:Activity . } 
			   GRAPH ?g2 {
			         ?act hxl:numberOfBeneficiaries ?beneficiaries . } 
			   GRAPH ?g3 {
			         ?act hxl:activityTitle ?project . } 
			}';

			$result = getQueryResults($query);

			$fields = $result->field_array( $result );

			while( $row = $result->fetch_array( $result ) ){
				echo 'data.addRow([\''.$row['project'].'\', '.$row['beneficiaries'].' ]); 
			';		
			}

			?>

	        // Create and draw the visualization.
	        new google.visualization.BarChart(document.getElementById('date_div')).
	            draw(data, {'title':'Beneficiaries by Project',
							width: 600, height: 500}
	                );
   }
  </script>
  </head>

  <body>
	<h1><a href="http://hxl.carsten.io/stats.php">HXL Statistics</a></h1>

	<p>This page shows some sample statistics generated on the fly from data in the <a href="http://hxl.humanitarianresponse.info">Humanitarian eXchange Language</a> using the <a href="http://code.google.com/apis/chart/interactive/docs/index.html">Google Chart Tools</a>.</p> <p>This is a <b>test setup</b> and some of the data shown here may be inaccurate, outdated, or even entirely made up.</p>
	
    <div id="org_div" style="float: left"></div>
	<div id="date_div"></div>
  </body>
</html>