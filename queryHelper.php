

	<?php 


$mapHTML = '';

function getQueryResults($query){
	
    $db = sparql_connect( "http://hxl.humanitarianresponse.info/sparql" );
	//$db = sparql_connect( "http://83.169.33.54:8080/parliament/sparql" );
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
		
	$query = "SELECT ?feature ?wkt WHERE { GRAPH ?g1 {<$resource> <http://www.opengis.net/ont/geosparql#hasGeometry> ?feature . ";
	$query .= "?feature	<http://www.opengis.net/ont/geosparql#hasSerialization>	?wkt}}";
	/*
	echo "<br>";
	echo htmlspecialchars($query);
	echo "<br>";
	*/
	$result = getQueryResults($query);
	while( $row = $result->fetch_array( $result ) ){		
		$mapHTML .= $row['wkt'];
	}
}

// if there is a result field "Predicate", this function will look for a result field "Label" in the same row and try to display the label
function getResultsAndShowTable($highlight , $query , $showHeaders, $group, $uri){	

    $namespaces = array(
        "http://xmlns.com/foaf/0.1/" => "foaf:",
        "http://purl.org/dc/terms/" => "dc:",
        "http://hxl.humanitarianresponse.info/ns/#" => "hxl:",
        "http://www.w3.org/2004/02/skos/core#" => "skos:",
        "http://www.w3.org/1999/02/22-rdf-syntax-ns#" => "rdf:",
        "http://www.opengis.net/ont/geosparql#" => "geo:"
    );

   /* echo '<br />';
	echo 'Query: ' . htmlspecialchars($query);
*/
	$result = getQueryResults($query);
	

	$fields = $result->field_array( $result );
	


    echo '<a href="http://sparql.carsten.io/?query=' . urlencode($query) . '&endpoint=http%3A//hxl.humanitarianresponse.info/sparql" target="_blank">Query link</a><br />';
    echo '<br />';
    echo '<table class="table table-striped table-hover" style="width:100%;" >';
    
	print "<thead>";
	print "<tr>";
	
	//if($showHeaders){
			print "<th style=\"width: 10px;\" >#</th>";
	foreach( $fields as $field ){
		if($field == "Graph"){
			print "<th style=\"width: 30px;\" >Container</th>";
		}else if($field == "Label"){
			// nada
		}else{
			print "<th>$field</th>";
		}
	}//}
	
	print "</tr>";
	print "</thead>";
	print "<tbody>";
	$lastsubject = "";
	$value = '';
	$display = '';
    $i = 1;
	while( $row = $result->fetch_array( $result ) )
	{
		$isDate = false;
		print "<tr>";
			echo '<td>';
        	echo $i;
			echo '</td>';
		foreach( $fields as $field )
		{
			//if($row[$field] === 'http://hxl.humanitarianresponse.info/ns/#atLocation'){
			if($row[$field] === 'http://www.opengis.net/ont/geosparql#hasGeometry'){
				//getMapData($row["Object"]);		
				getMapData($uri);											
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



			/*$high = "";
			if($value === $highlight){
				$high = " class='highlight'";
			}
*/


			if(substr($link,0,7) == 'http://'){
				
				if(isDateProp($link)){
					$isDate = true;
				}
				
				if($field == "Graph"){
					$value = "<a href='$value' class='btn btn-mini' >Container</a>";
				}else{

		        $display = $row[$field];
		        foreach ($namespaces as $namespaceUri => $prefix ) {
		            if (stristr($value, $namespaceUri)) {
		                $display = str_replace($namespaceUri, $prefix, $value);
		            }
		        }

				$value = "<a href='$link' class='btn btn-small' >$display</a>";
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
			echo '<td>';
        	echo $value;
			echo '</td>';

		}
		print "</tr>";
        $i++;
	}
	print "</tbody>";
	print "</table>";
}




?>