<?php
	/**
	 * Class written by xPaw
	 *
	 * Website: http://xpaw.me
	 * GitHub: https://github.com/xPaw/PHP-Source-Query-Class
	 */
	
	class SourceQuerySocket
	{
		public $Socket;
		public $Engine;
		
		public $Ip;
		public $Port;
		public $Timeout;
		
		/**
		 * Points to buffer class
		 * 
		 * @var SourceQueryBuffer
		 */
		private $Buffer;
		
		public function __construct( SourceQueryBuffer $Buffer )
		{
			$this->Buffer = $Buffer;
		}
		
		public function Close( )
		{
			if( $this->Socket )
			{
				FClose( $this->Socket );
				
				$this->Socket = null;
			}
		}
		
		/**
		 * Opens a socket connection to the server specified by an $ip address
		 * on the specified $port with specific $timeout and an $engine-specific
		 * behavior.
		 *
		 * @param string $ip 		A valid IPv4 address of the server
		 * @param int $port 			Port to use when opening connection
		 * @param int $timeout 		A time period (in seconds) for a server to accept connection
		 * @param int $engine 		An engine protocol to use for a communication (either 
		 *                     			SourceQuery::GOLDSOURCE or SourceQuery::SOURCE constant)
		 * @return boolean 			always TRUE
		 * @throws \Exception 		if it is not possible to connect
		 */
		public function Open( $ip, $port, $timeout, $engine )
		{
			// internal connection attributes
			$this->Ip = $ip;
			$this->Port = $port;
			$this->Timeout = $timeout;
			$this->Engine = $engine;
			
			// socket held for a next session
			$this->Socket = @FSockOpen( "udp://{$this->Ip}", $this->Port, $ErrNo, $ErrStr, $this->Timeout );
			
			// socket could not be opened
			if( $ErrNo || $this->Socket === false )
			{
				throw new Exception( 'Could not create socket: ' . $ErrStr );
			}
			
			Stream_Set_Timeout( $this->Socket, $this->Timeout );
			Stream_Set_Blocking( $this->Socket, true );
			
			return true;
		}
		
		/**
		 * @return boolean whether sending the data to a server succeeded
		 */
		public function Write( $Header, $String = '' )
		{
			$Command = Pack( 'ccccca*', 0xFF, 0xFF, 0xFF, 0xFF, $Header, $String );
			$Length  = StrLen( $Command );
			
			return $Length === FWrite( $this->Socket, $Command, $Length );
		}
		
		/**
		 * @throws SourceQueryException
		 */
		public function Read( $Length = 1400 )
		{
			$this->Buffer->Set( FRead( $this->Socket, $Length ) );
			
			if( $this->Buffer->Remaining( ) === 0 )
			{
				// TODO: Should we throw an exception here?
				return;
			}
			
			$Header = $this->Buffer->GetLong( );
			
			if( $Header === -1 ) // Single packet
			{
				 // We don't have to do anything
			}
			else if( $Header === -2 ) // Split packet
			{
				$Packets      = Array( );
				$IsCompressed = false;
				$ReadMore     = false;
				
				do
				{
					$RequestID = $this->Buffer->GetLong( );
					
					switch( $this->Engine )
					{
						case SourceQuery :: GOLDSOURCE:
						{
							$PacketCountAndNumber = $this->Buffer->GetByte( );
							$PacketCount          = $PacketCountAndNumber & 0xF;
							$PacketNumber         = $PacketCountAndNumber >> 4;
							
							break;
						}
						case SourceQuery :: SOURCE:
						{
							$IsCompressed         = ( $RequestID & 0x80000000 ) !== 0;
							$PacketCount          = $this->Buffer->GetByte( );
							$PacketNumber         = $this->Buffer->GetByte( ) + 1;
							
							if( $IsCompressed )
							{
								$this->Buffer->GetLong( ); // Split size
								
								$PacketChecksum = $this->Buffer->GetUnsignedLong( );
							}
							else
							{
								$this->Buffer->GetShort( ); // Split size
							}
							
							break;
						}
					}
					
					$Packets[ $PacketNumber ] = $this->Buffer->Get( );
					
					$ReadMore = $PacketCount > sizeof( $Packets );
				}
				while( $ReadMore && $this->Sherlock( $Length ) );
				
				$Buffer = Implode( $Packets );
				
				// TODO: Test this
				if( $IsCompressed )
				{
					// Let's make sure this function exists, it's not included in PHP by default
					if( !Function_Exists( 'bzdecompress' ) )
					{
						throw new RuntimeException( 'Received compressed packet, PHP doesn\'t have Bzip2 library installed, can\'t decompress.' );
					}
					
					$Data = bzdecompress( $Data );
					
					if( CRC32( $Data ) !== $PacketChecksum )
					{
						throw new SourceQueryException( 'CRC32 checksum mismatch of uncompressed packet data.' );
					}
				}
				
				$this->Buffer->Set( SubStr( $Buffer, 4 ) );
			}
			else
			{
				throw new SourceQueryException( 'Socket read: Raw packet header mismatch. (0x' . DecHex( $Header ) . ')' );
			}
		}
		
		/**
		 * @return boolean
		 */
		private function Sherlock( $Length )
		{
			$Data = FRead( $this->Socket, $Length );
			
			if( StrLen( $Data ) < 4 )
			{
				return false;
			}
			
			$this->Buffer->Set( $Data );
			
			return $this->Buffer->GetLong( ) === -2;
		}
	}
