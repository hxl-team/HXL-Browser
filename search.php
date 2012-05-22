<?php 

require_once( "sparqllib.php" );

$db = sparql_connect( "http://83.169.33.54:8080/parliament/sparql" );
if( !$db ) { print $db->errno() . ": " . $db->error(). "\n"; exit; }
	
$result = $db->query( 'SELECT * WHERE {
  GRAPH ?g{
	?subject ?predicate ?object .
	FILTER regex(?object, "' .$_GET['term']. '", "i" ) 
  }
} LIMIT 30' ); 

if( !$result ) { 
	print $db->errno() . ": " . $db->error(). "\n"; exit; 
}else{
	$json = '[ 
  ';
	while( $row = $result->fetch_array( $result ) ){
		$json .= '{ "label": "' . $row["object"].'", "value": "'.$row["subject"].'" }, 
  ';
	}
	
	$json = substr($json, 0, -5);
	
	echo $json . '
]';
}