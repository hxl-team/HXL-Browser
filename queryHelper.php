<?php
/*
 * Building the URI, taking into account a possible testing environment.
 */
function buildUri($host, $req) {
    $uri ='';
    
    try {
        if (stristr($host, 'humanitarianresponse')) {
            $uri = 'http://'.$host.$req;
        } else { // works only with a test case when the project is in the /HXL-Browser/ foldder
            $uri = 'http://hxl.humanitarianresponse.info/data/' . substr($req, strlen('/HXL-Browser/'));
        }
        $uri = rtrim($uri,"/");
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
	return $uri;
}

/*
 * Gives the result of the SPARQL query.
 */
function getQueryResults($query){
    try {
        $db = sparql_connect( "http://hxl.humanitarianresponse.info/sparql" );
        
        if( !$db ) {
            print $db->errno() . ": " . $db->error(). "\n"; exit;
        }
        $result = $db->query($query);
        if( !$result ) {
            print $db->errno() . ": " . $db->error(). "\n"; exit;
        }

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
	return $result;
}

function displayQueryResults($uri){
    
    // Make an updatable config file or an automatic mechanisme
    $namespaces = array(
        "http://xmlns.com/foaf/0.1/" => "foaf:",
        "http://purl.org/dc/terms/" => "dc:",
        "http://hxl.humanitarianresponse.info/ns/#" => "hxl:",
        "http://www.w3.org/2004/02/skos/core#" => "skos:",
        "http://www.w3.org/1999/02/22-rdf-syntax-ns#" => "rdf:"
    );

    // Normal URI http://hxl.humanitarianresponse.info/data/emergencies/16107
    // Project URI: http://localhost/HXL-Browser/emergencies/16107
    $query = "SELECT * { <$uri> ?predicate ?object . ";
    //$query .= "OPTIONAL { ?predicate <http://www.w3.org/2004/02/skos/core#prefLabel> ?predicateLabel } . ";
    $query .= "OPTIONAL { ?object <http://www.w3.org/2004/02/skos/core#prefLabel> ?objectLabel }";
    $query .= " }";
    $result = getQueryResults($query);
    
    if ($result->num_rows() == 0) return false;
    
    /*
    echo '<pre>';
    print_r($result);
    echo '</pre>';
     * */
        
    echo '<br />';
    echo '<table style="border: 1px solid #CACACA; width:100%;" >';
    echo '<tr><th>Property</th><th>Value</th></tr>';
    
    $i = 0;
    while( $row = $result->fetch_array() ){  
        $predicateDisplay = '';
        $objectDisplay = '';

        $predicate = $row["predicate"];
        $object = $row["object"];

        // PROPERTIES
        // Attempt to shorten the namespace
        $predicateDisplay = $predicate;
        foreach ($namespaces as $key => $value) {
            if (stristr($predicate, $key)) {
                $predicateDisplay = str_replace($key, $value, $predicate);
            }
        }
        
        if ($i % 2 == 0){
            echo '<tr><td style="background: #AAAAAA">';
        } else{
            echo '<tr><td style="background: #CCCCCC">';
        }
        echo "<a href='$predicate' target='_blank' >$predicateDisplay</a>" ;
        if ($i % 2 == 0){
            echo '</td><td style="background: #AAAAAA">';
        } else{
            echo '</td><td style="background: #CCCCCC">';
        }

        // VALUES
        // Attempt to use a label
        $objectDisplay = $object;
        if (array_key_exists('objectLabel', $row)) {
            $objectDisplay = $row['objectLabel'];
        }

        // Attempt to shorten the namespace
        if ($objectDisplay == $object) {
            foreach ($namespaces as $key => $value) {
                if (stristr($object, $key)) {
                    $objectDisplay = str_replace($key, $value, $object);
                }
            }    
        }    
        
        // Choosing link or litteral. Can be more generiby guessing if it is litteral
        if ($predicate == 'http://purl.org/dc/terms/date' ||
            $predicate == 'http://hxl.humanitarianresponse.info/ns/#commonTitle') {
            echo "$objectDisplay" ;
        } else {
            echo "<a href='$object' target='_blank' >$objectDisplay</a>" ;
        }

        echo '<br />';
        echo '</td></tr>';
        $i++;
    } 
    echo '<table>';
    echo '<span style="font-size:0.6em;" >Note: All links open in a new window.</span><br />';
    echo '<hr>';
    echo '<br />';        
    echo '<a href="http://sparql.carsten.io/?query=' . urlencode($query) . '&endpoint=http%3A//hxl.humanitarianresponse.info/sparql" target="_blank">Query link</a>';
    echo '<br />';
    echo 'Query: ' . htmlspecialchars($query);
    echo '<br />';
}

/* old code
$mapHTML = '';

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
*/
?>
