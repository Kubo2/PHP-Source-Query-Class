<?php

require dirname(__FILE__) . '/SourceQuery/SourceQuery.class.php';

// For the sake of this example
header('Content-Type: text/plain');

// Edit this ->
{
	/** The domain name or IP address of your Source Query game server. */
	define('SQ_SERVER_ADDR', 'localhost');

	/** The port which your SQ server is running on. */
	define('SQ_SERVER_PORT', 27015);

	/** Number of seconds SourceQuery instance will wait for server's response. You can keep default.  */
	define('SQ_TIMEOUT', 1);

	/** The Engine server is running on. Can be one of SourceQuery class constants - also can keep default, as long as it works. */
	define('SQ_ENGINE', SourceQuery::SOURCE);
}
// Edit this <-

	$Query = new SourceQuery( );
	
	try
	{
		$Query->Connect( SQ_SERVER_ADDR, SQ_SERVER_PORT, SQ_TIMEOUT, SQ_ENGINE );
		
		print_r( $Query->GetInfo( ) );
		print_r( $Query->GetPlayers( ) );
		print_r( $Query->GetRules( ) );
	}
	catch( Exception $e )
	{
		echo $e->getMessage( );
	}
	
	$Query->Disconnect( );
