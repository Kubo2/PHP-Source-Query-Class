<?php

require dirname(__FILE__) . '/SourceQuery/SourceQuery.class.php';

// For the sake of this example
header('Content-Type: text/plain');

// Edit this ->
{
	/** The domain name or IP address of your Source Query game server. */
	define('SQ_SERVER_ADDR', 'localhost');

	/** The port which your Source Query game server is running on. */
	define('SQ_SERVER_PORT', 27015);

	/** Number of seconds SourceQuery instance will wait for server's response. You can keep default.  */
	define('SQ_TIMEOUT', 1);

	/** The Engine server is running on. Can be one of SourceQuery class constants - also can keep default, as long as it works. */
	define('SQ_ENGINE', SourceQuery::SOURCE);
}
// Edit this <-

$sourceQuery = new SourceQuery;
	
try {
	$sourceQuery->connect( SQ_SERVER_ADDR, SQ_SERVER_PORT, SQ_TIMEOUT, SQ_ENGINE );
	
	// an error occured if either one of following outputs "bool(false)"
	var_dump($sourceQuery->getInfo());
	var_dump($sourceQuery->getPlayers());
	var_dump($sourceQuery->getRules());

} catch(Exception $ex) {
	printf("\nAn error occured, %s thrown: %s", get_class($ex), $ex->getMessage());
}

$sourceQuery->Disconnect( );
