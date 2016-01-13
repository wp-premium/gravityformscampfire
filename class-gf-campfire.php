<?php
	
GFForms::include_feed_addon_framework();

class GFCampfire extends GFFeedAddOn {

	protected $_version = GF_CAMPFIRE_VERSION;
	protected $_min_gravityforms_version = '1.9.12';
	protected $_slug = 'gravityformscampfire';
	protected $_path = 'gravityformscampfire/campfire.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms Campfire Add-On';
	protected $_short_title = 'Campfire';
	protected $_enable_rg_autoupgrade = true;
	protected $api = null;
	private static $_instance = null;

	/* Permissions */
	protected $_capabilities_settings_page = 'gravityforms_campfire';
	protected $_capabilities_form_settings = 'gravityforms_campfire';
	protected $_capabilities_uninstall = 'gravityforms_campfire_uninstall';

	/* Members plugin integration */
	protected $_capabilities = array( 'gravityforms_campfire', 'gravityforms_campfire_uninstall' );

	/**
	 * Get instance of this class.
	 * 
	 * @access public
	 * @static
	 * @return $_instance
	 */
	public static function get_instance() {
		
		if ( self::$_instance == null )
			self::$_instance = new self;

		return self::$_instance;
		
	}

	/**
	 * Setup plugin settings fields.
	 * 
	 * @access public
	 * @return array
	 */
	public function plugin_settings_fields() {
						
		return array(
			array(
				'title'       => '',
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'account_url',
						'label'             => esc_html__( 'Account URL', 'gravityformscampfire' ),
						'type'              => 'text',
						'class'             => 'small',
						'after_input'       => '.campfirenow.com',
						'feedback_callback' => array( $this, 'validate_account_url' )
					),
					array(
						'name'              => 'api_token',
						'label'             => esc_html__( 'API Token', 'gravityformscampfire' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'type'              => 'save',
						'messages'          => array(
							'success' => esc_html__( 'Campfire settings have been updated.', 'gravityformscampfire' )
						),
					),
				),
			),
		);
		
	}

	/**
	 * Prepare plugin settings description.
	 * 
	 * @access public
	 * @return string
	 */
	public function plugin_settings_description() {
		
		$description  = '<p>';
		$description .= sprintf(
			esc_html__( 'Campfire provides simple group for your team. Use Gravity Forms to alert your Campfire rooms of a new form submission. If you don\'t have a Campfire account, you can %1$s sign up for one here.%2$s', 'gravityformscampfire' ),
			'<a href="http://www.campfirenow.com/" target="_blank">', '</a>'
		);
		$description .= '</p>';
		
		if ( ! $this->initialize_api() ) {
			
			$description .= '<p>';
			$description .= esc_html__( 'Gravity Forms Campfire Add-On requires an API token, which can be found on the My Info page. All messages will be posted by the API token owner.', 'gravityformscampfire' );
			$description .= '</p>';
			
		}
		
		return $description;
		
	}

	/**
	 * Setup fields for feed settings.
	 * 
	 * @access public
	 * @return array
	 */
	public function feed_settings_fields() {	        

		return array(
			array(
				'title' =>	'',
				'fields' =>	array(
					array(
						'name'           => 'feedName',
						'label'          => esc_html__( 'Name', 'gravityformscampfire' ),
						'type'           => 'text',
						'class'          => 'medium',
						'required'       => true,
						'tooltip'        => '<h6>'. esc_html__( 'Name', 'gravityformscampfire' ) .'</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformscampfire' )
					),
					array(
						'name'           => 'room',
						'label'          => esc_html__( 'Campfire Room', 'gravityformscampfire' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->get_rooms_for_feed_setting(),
						'onchange'       => "jQuery(this).parents('form').submit();",
						'tooltip'        => '<h6>'. __( 'HipChat Room', 'gravityformscampfire' ) .'</h6>' . esc_html__( 'Select which Campfire Room this feed will post a notification to.', 'gravityformscampfire' )
					),
					array(
						'name'           => 'message',
						'label'          => esc_html__( 'Message', 'gravityformscampfire' ),
						'type'           => 'textarea',
						'required'       => true,
						'dependency'     => 'room',
						'class'          => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
						'tooltip'        => '<h6>'. esc_html__( 'Message', 'gravityformscampfire' ) .'</h6>' . __( 'Enter the message that will be posted to the room. Maximum message length: 10,000 characters.', 'gravityformscampfire' ),
						'value'          => 'Entry #{entry_id} has been added. ({entry_url})'
					),
					array(
						'name'           => 'options',
						'label'          => esc_html__( 'Options', 'gravityformscampfire' ),
						'type'           => 'checkbox',
						'choices'        => array(
							array(
								'name'  => 'highlight',
								'label' => esc_html__( 'Highlight Message', 'gravityformscampfire' ),
							),
						)
					),
					array(
						'name'           => 'feedCondition',
						'label'          => esc_html__( 'Conditional Logic', 'gravityformscampfire' ),
						'type'           => 'feed_condition',
						'dependency'     => 'room',
						'checkbox_label' => esc_html__( 'Enable', 'gravityformscampfire' ),
						'instructions'   => esc_html__( 'Post to HipChat if', 'gravityformscampfire' ),
						'tooltip'        => '<h6>'. esc_html__( 'Conditional Logic', 'gravityformscampfire' ) .'</h6>' . esc_html__( 'When conditional logic is enabled, form submissions will only be posted to HipChat when the condition is met. When disabled, all form submissions will be posted.', 'gravityformscampfire' )
					)
				)
			)
		);
	
	}

	/**
	 * Get Campfire rooms for feed settings field.
	 * 
	 * @access public
	 * @return array $rooms
	 */
	public function get_rooms_for_feed_setting() {
		
		$rooms = array(
			array(
				'label' => esc_html__( 'Choose a Room', 'gravityformscampfire' ),
				'value' => ''
			)
		);
		
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): Unable to get rooms because API was not initialized.' );
			return $rooms;
		}
		
		$campfire_rooms = $this->api->get_rooms();
		
		if ( ! empty( $campfire_rooms ) ) {
			
			foreach ( $campfire_rooms as $room ) {
				
				$rooms[] = array(
					'label' => $room['name'],
					'value' => $room['id']
				);
				
			}
			
		}
		
		return $rooms;
		
	}

	/**
	 * Set if feeds can be created.
	 * 
	 * @access public
	 * @return bool
	 */
	public function can_create_feed() {
		
		return $this->initialize_api();
		
	}

	/**
	 * Setup feed list columns.
	 * 
	 * @access public
	 * @return array
	 */
	public function feed_list_columns() {
		
		return array(
			'feedName' => esc_html__( 'Name', 'gravityformscampfire' ),
			'room'     => esc_html__( 'Room', 'gravityformscampfire' )
		);
		
	}

	/**
	 * Get room for feed list column.
	 * 
	 * @access public
	 * @param array $feed
	 * @return string
	 */
	public function get_column_value_room( $feed ) {
		
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): Unable to get room name because API is not initialized.' );
			return rgars( $feed, 'meta/room' );
		}
		
		$room = $this->api->get_room( $feed['meta']['room'] );
		
		return $room['name'];
		
	}

	/**
	 * Process feed.
	 * 
	 * @access public
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return void
	 */
	public function process_feed( $feed, $entry, $form ) {
		
		/* If Campfire instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {
			$this->add_feed_error( esc_html__( 'Message was not posted to room because API was not initialized.', 'gravityformscampfire' ), $feed, $entry, $form );
			return;
		}
		
		/* Prepare message. */
		$message = array(
			'type' => 'TextMessage',
			'body' => gf_apply_filters( 'gform_campfire_message', $form['id'], rgars( $feed, 'meta/message' ), $feed, $entry, $form )
		);
		
		/* Replace merge tags in message. */
		$message['body'] = GFCommon::replace_variables( $message['body'], $form, $entry );

		/* If message is empty, exit. */
		if ( rgblank( $message['body'] ) ) {
			
			$this->add_feed_error( esc_html__( 'Message was not posted to room because it was empty.', 'gravityformscampfire' ), $feed, $entry, $form );
			return;
			
		}
		
		try {
			
			/* Post message. */
			$message = $this->api->create_message( rgars( $feed, 'meta/room' ), $message );
			
			/* Log that message was posted. */
			$this->log_debug( __METHOD__ . '(): Message #' . $message['id'] . ' was posted to room #' . $message['room_id'] . '.' );
			
		} catch ( Exception $e ) {
			
			/* Log that message was not posted. */
			$this->add_feed_error(
				sprintf(
					esc_html__( 'Message was not posted to room: %s', 'gravityformscampfire' ),
					$e->getMessage()
				), $feed, $entry, $form
			);
			
			return false;
			
		}
		
		if ( rgars( $feed, 'meta/highlight') == '1' ) {
			
			try {
			
				/* Highlight message. */
				$this->api->highlight_message( $message['id'] );
				
				/* Log that message was posted. */
				$this->log_debug( __METHOD__ . '(): Message #' . $message['id'] . ' was highlighted.' );
				
			} catch ( Exception $e ) {
				
				/* Log that message was not posted. */
				$this->add_feed_error(
					sprintf(
						esc_html__( 'Message was not highlighted: %s', 'gravityformscampfire' ),
						$e->getMessage()
					), $feed, $entry, $form
				);
				
				return false;
				
			}
			
		}
		
	}

	/**
	 * Initializes Campfire if credentials are valid.
	 * 
	 * @access public
	 * @return bool
	 */
	public function initialize_api() {

		if ( ! is_null( $this->api ) ) {
			return true;
		}
		
		/* Load the Campfire API library. */
		if ( ! class_exists( 'Campfire' ) ) {
			require_once 'includes/class-campfire.php';
		}

		/* Get the plugin settings */
		$settings = $this->get_plugin_settings();
		
		/* If the account URL or API token are empty, return null. */
		if ( rgblank( $settings['account_url'] ) || rgblank( $settings['api_token'] ) ) {
			return null;
		}
			
		$this->log_debug( __METHOD__ . "(): Validating API info." );
		
		$campfire = new Campfire( $settings['account_url'], $settings['api_token'] );
		
		try {
			
			/* Run API test. */
			$campfire->get_rooms();
			
			/* Log that test passed. */
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );
			
			/* Assign Campfire object to the class. */
			$this->api = $campfire;
			
			return true;
			
		} catch ( Exception $e ) {
			
			/* Log that test failed. */
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $e->getMessage() );			

			return false;
			
		}
		
	}

	/**
	 * Checks validity of Campfire account URL.
	 * 
	 * @access public
	 * @param mixed $account_url
	 * @return void
	 */
	public function validate_account_url( $account_url ) {
		
		/* Load the Campfire API library. */
		if ( ! class_exists( 'Campfire' ) ) {
			require_once 'includes/class-campfire.php';
		}

		/* If the account URL is empty, return null. */
		if ( rgblank( $account_url ) ) {
			return null;
		}
			
		$this->log_debug( __METHOD__ . "(): Validating account URL: {$account_url}.campfirenow.com" );
		
		try {
					
			$campfire = new Campfire( $account_url );
			
			/* Run account URL test. */
			$campfire->validate_account_url();
			
			/* Log that test passed. */
			$this->log_debug( __METHOD__ . '(): Account URL is valid.' );
						
			return true;
			
		} catch ( Exception $e ) {
			
			/* Log that test failed. */
			$this->log_error( __METHOD__ . '(): Account URL is invalid.' );			

			return false;
			
		}
		
	}

}
