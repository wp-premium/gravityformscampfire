<?php
	
class Campfire {
	
	public function __construct( $subdomain, $api_token = null, $verify_ssl = true ) {
		
		$this->subdomain  = $subdomain;
		$this->api_token  = $api_token;
		$this->verify_ssl = $verify_ssl;
		
	}

	/**
	 * Make API request.
	 * 
	 * @access public
	 * @param string $path
	 * @param array $options
	 * @param bool $return_status (default: false)
	 * @param string $method (default: 'GET')
	 * @return void
	 */
	public function make_request( $path, $options = array(), $method = 'GET', $return_key = null ) {
		
		/* Build request URL. */
		$request_url = 'https://' . $this->subdomain . '.campfirenow.com/' . $path . '.json';
					
		/* Initialize cURL session. */
		$curl = curl_init();
		
		/* Setup cURL options. */
		curl_setopt( $curl, CURLOPT_URL, $request_url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl );
		curl_setopt( $curl, CURLOPT_USERPWD, $this->api_token . ':x' );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Accept: application/json', 'Content-Type: application/json' ) );
			
		/* If this is a POST request, pass the request options via cURL option. */
		if ( $method == 'POST' ) {
			
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $options ) );
			
		}

		/* If this is a PUT request, pass the request options via cURL option. */
		if ( $method == 'PUT' ) {
			
			curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'PUT' );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $options ) );
			
		}
		
		/* Execute cURL request. */
		$curl_result = curl_exec( $curl );
		
		/* If there is an error, die with error message. */
		if ( $curl_result === false ) {
			
			die( 'cURL error: '. curl_error( $curl ) );
			
		}
		
		/* Close cURL session. */
		curl_close( $curl );
		
		/* Attempt to decode JSON. If isn't JSON, return raw cURL result. */
		$json_result = json_decode( $curl_result, true );
		$curl_result = trim( $curl_result );
		
		if ( ! is_array( $json_result ) && ! empty( $curl_result ) ) {
			throw new Exception( $curl_result );
		}
		
		if ( ! empty( $return_key ) ) {
			return $json_result[ $return_key ];
		} else {
			return $json_result;
		}
		
	}
	
	/**
	 * Create message.
	 * 
	 * @access public
	 * @param int $room_id
	 * @param array $message
	 * @return array
	 */
	public function create_message( $room_id, $message ) {
		
		return $this->make_request( 'room/' . $room_id . '/speak', array( 'message' => $message ), 'POST', 'message' );
		
	}
	
	/**
	 * Get Campfire room.
	 * 
	 * @access public
	 * @param mixed $room_id
	 * @return array
	 */
	public function get_room( $room_id ) {
		
		return $this->make_request( 'room/' . $room_id, array(), 'GET', 'room' );
		
	}
	
	/**
	 * Get all Campfire rooms.
	 * 
	 * @access public
	 * @return array
	 */
	public function get_rooms() {
		
		return $this->make_request( 'rooms', array(), 'GET', 'rooms' );
		
	}

	/**
	 * Highlight message.
	 * 
	 * @access public
	 * @param int $message_id
	 * @return void
	 */
	public function highlight_message( $message_id ) {
		
		return $this->make_request( 'messages/' . $message_id . '/star', array(), 'POST' );
		
	}

	/**
	 * Check if account URL is valid.
	 * 
	 * @access public
	 * @return boolean
	 */
	public function validate_account_url() {
		
		/* Execute request. */
		$response = wp_remote_request( 'https://' . $this->subdomain . '.campfirenow.com/?' . time() );
		
		if ( $response['response']['code'] !== 200 ) {
			throw new Exception( 'Account URL is invalid.' );
		}
		
		return true;
		
	}
	
}