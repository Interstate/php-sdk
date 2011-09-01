<?php

class Interstate {

	const INTERSTATE_HOST	= 'interstateapp.com';
	const API_HOST			= 'api.interstateapp.com';
	const API_VERSION		= 2;
	private $_clientId;
	private $_secret;
	private $_redirectUri;
	private $_oauthToken;
	private $_https		= true;

	public function __construct( $params ) {
	
		// { 'client_id', 'secret', 'redirect_uri', 'oauth_token', 'https' }
	
		if( isset( $params[ 'client_id' ] ) ) {
			
			$this->_clientId = $params[ 'client_id' ];
		
		}
	
		if( isset( $params[ 'client_secret' ] ) ) {
			
			$this->_secret = $params[ 'client_secret' ];
		
		}
		
		if( isset( $params[ 'redirect_uri' ] ) ) {
			
			$this->_redirectUri = $params[ 'redirect_uri' ];
		
		}
	
		if( isset( $params[ 'oauth_token' ] ) ) {
			
			$this->_oauthToken = $params[ 'oauth_token' ];
		
		}
	
		if( isset( $params[ 'https' ] ) ) {
			
			$this->_https = (bool)$params[ 'htpss' ];
		
		}
		
	}
	
	public function getApiUrl() {
		
		return ( ( $this->_https ) ? 'https://' : 'http://' ) . self::API_HOST . '/v' . self::API_VERSION . '/';
		
	
	}
	
	public function getRootUrl() {
		
		return ( ( $this->_https ) ? 'https://' : 'http://' ) . self::INTERSTATE_HOST . '/';
		
	
	}
	
	public function getAuthorizeUrl() {

		return $this->getRootUrl() . 'oauth2/authorize?client_id=' . $this->_clientId . '&redirect_uri=' . $this->_redirectUri . '&response_type=code';

	}
	
	public function sign( $uri ) {
	
		if( $this->_oauthToken === null ) {
			
			return $uri;
		
		} else {
			
			$parts = parse_url( $uri );
			
			if( isset( $parts[ 'query' ] ) !== false ) {
			
				$uri .= '&oauth_token=' . $this->_oauthToken;
			
			} else {
				
				$uri .= '?oauth_token=' . $this->_oauthToken;
			
			}
			
			return $uri;
		
		}
			
	}
	
	public function setAccessToken( $token ) {
	
		$this->_oauthToken = $token;
		
		return $this;
		
	}
	
	public function getAccessToken( $code, $type = 'authorization_code', $setToken = true ) {
	
		$post	= array(
			
			'client_id'		=> $this->_clientId,
			'client_secret'	=> $this->_secret,
			'redirect_uri'	=> $this->_redirectUri
		
		);
		
		switch( $type ) {
			
			default:
				case 'authorization_code':
				
				$post += array(
					
					'grant_type'	=> 'authorization_code',
					'code'			=> $code
				
				);
				
				break;
				
			case 'refresh_token':
				
				$post += array(
				
					'grant_type'	=> 'refresh_token',
					'refresh_token'	=> $code
				
				);
				
				break;
		
		}
		
		try {
			
			$response = $this->fetch( array(
				
				'url'		=> 'oauth2/token',
				'post'		=> $post
			
			));
			
			if( $setToken ) {
			
				$this->setAccessToken( $response[ 'access_token' ] );
			
			}
			
			return $response;
			
		} catch( Exception $e ) {
			
			return false;
		
		}
		
	}

	public function fetch( $params, $post = array() ) {
		
		if( is_string( $params ) ) {
			
			$params = array( 'url' => $params );
		
		}
		
		if( isset( $params[ 'post' ] ) === false ) {
			
			$params[ 'post' ] = $post;
		
		}
		
		if( preg_match( '/\s/', $params[ 'url' ] ) === 1 ) {
			
			$explode			= explode( ' ', $params[ 'url' ] );
			$params[ 'verb' ]	= strtoupper( $explode[ 0 ] );
			$params[ 'url' ]	= $explode[ 1 ];
			
		}
		
		if( $params[ 'url' ][ 0 ] == '/' ) {
			
			$params[ 'url' ] = substr( $params[ 'url' ], 1, strlen( $params[ 'url' ] ) );
		
		}
		
		$url	= $this->sign( $this->getApiUrl() . $params[ 'url' ] );
		$curl	= curl_init();
		
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		
		if( isset( $params[ 'post' ] ) !== false ) {
		
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $params[ 'post' ] );
		
		}
		
		if( isset( $params[ 'verb' ] ) !== false ) {
		
			curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $params[ 'verb' ] );
		
		}
		
		if( isset( $params[ 'onCurl' ] ) !== false ) {
		
			$params[ 'onCurl' ]( $this, $curl );
		
		}
		
		if( isset( $params[ 'curlopt' ] ) !== false ) {
			
			curl_setopt_array( $curl, $params[ 'curlopt' ] );
		
		}
		
		$response	= curl_exec( $curl );
		$code		= curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		if( $code === 200 ) {
			
			$response = $this->_parseResponse( $response );
			
			if( isset( $response[ 'status' ] ) !== false ) {
			
				if( $response[ 'status' ] != 'error' ) {
					
					return ( isset( $response[ 'response' ] ) ? $response[ 'response' ] : true );
				
				} else {
					
					throw new Exception();
				
				}
			
			} else {
			
				return $response;
			
			}
			
		} else {
		
			if( $response != '' ) {
				
				$response = $this->_parseResponse( $response );
				
				throw new Exception( ( is_array( $response[ 'error' ] ) ? $response[ 'error' ][ 'message' ] : $response[ 'error' ] ), $info[ 'http_code' ] );
			
			} else {
				
				throw new Exception( '', $info[ 'http_code' ] );
			
			}
		
		}
	
	}

	protected function _parseResponse( $response ) {
		
		return json_decode( $response, true );
	
	}

}