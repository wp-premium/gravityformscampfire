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
		
		/* Setup request arguments. */
		$args = array(
			'headers'   => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $this->api_token . ':x' ),
				'Content-Type'  => 'application/json'
			),
			'method'    => $method,
			'sslverify' => $this->verify_ssl	
		);

		/* Add request options to body of POST and PUT requests. */
		if ( $method == 'POST' || $method == 'PUT' ) {
			$args['body'] = json_encode( $options );
		}

		/* Execute request. */
		$result = wp_remote_request( $request_url, $args );
			
		/* If WP_Error, throw exception */
		if ( is_wp_error( $result ) ) {
			throw new Exception( 'Request failed. '. $result->get_error_messages() );
		}

		/* Decode JSON. */
		$decoded_result = json_decode( $result['body'], true );
		
		/* If invalid JSON, return original result body. */
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return trim( $result['body'] );
		}
		
		/* If return key is set and exists, return array item. */
		if ( $return_key && array_key_exists( $return_key, $decoded_result ) ) {
			return $decoded_result[ $return_key ];
		}
		
		return $decoded_result;
		
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