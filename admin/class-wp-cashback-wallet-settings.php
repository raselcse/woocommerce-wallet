<?php

class WP_Cashback_Wallet_Settings {
    public $refund_request;
    public function __construct() {
		// Hook into the admin menu
    	add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );

        // Add Settings and Fields
    	add_action( 'admin_init', array( $this, 'setup_sections' ) );
		add_action( 'admin_init', array( $this, 'setup_fields' ) );
		
    }

    public function create_plugin_settings_page() {
    	// Add the menu item and page
    	$page_title = 'WP Cashback Wallet Settings';
    	$menu_title = 'WP Cashback Wallet';
    	$capability = 'manage_options';
    	$slug = 'wp_cashback_wallet_settings';
    	$callback = array( $this, 'plugin_settings_page_content' );
    	$icon = 'dashicons-admin-plugins';
    	$position = 100;

		add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
	
		
    }

    public function plugin_settings_page_content() {?>
    	<div class="wrap">
    		<h2>WP cashback wallet settings page</h2><?php
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ){
                  $this->admin_notice();
            } ?>
    		<form method="POST" action="options.php">
                <?php
                    settings_fields( 'wp_cashback_wallet_settings' );
                    do_settings_sections( 'wp_cashback_wallet_settings' );
                    submit_button();
                ?>
    		</form>
    	</div> <?php
    }
    
    public function admin_notice() { ?>
        <div class="notice notice-success is-dismissible">
            <p>Your settings have been updated!</p>
        </div><?php
    }

    public function setup_sections() {
        add_settings_section( 'our_first_section', 'WP Cashback Wallet', array( $this, 'section_callback' ), 'wp_cashback_wallet_settings' );
        //add_settings_section( 'our_second_section', 'My Second Section Title', array( $this, 'section_callback' ), 'wp_cashback_wallet_settings' );
        //add_settings_section( 'our_third_section', 'My Third Section Title', array( $this, 'section_callback' ), 'wp_cashback_wallet_settings' );
    }

    public function section_callback( $arguments ) {
    	switch( $arguments['id'] ){
    		case 'our_first_section':
    			echo 'Setting page for wallet system!';
    			break;
    		// case 'our_second_section':
    		// 	echo 'This one is number two';
    		// 	break;
    		// case 'our_third_section':
    		// 	echo 'Third time is the charm!';
    		// 	break;
    	}
    }

    public function setup_fields() {
        $fields = array(
        	array(
        		'uid' => 'cash_wallet_title',
        		'label' => 'Cash wallet title',
        		'section' => 'our_first_section',
        		'type' => 'text',
        		'placeholder' => 'Cash wallet',
        		'helper' => 'This title show at frontend?',
        		'supplimental' => 'Put short title!',
            ),
            
            array(
        		'uid' => 'site_wallet_title',
        		'label' => 'Site wallet title',
        		'section' => 'our_first_section',
        		'type' => 'text',
        		'placeholder' => 'Site wallet',
        		'helper' => 'This title show at frontend?',
        		'supplimental' => 'Put short title!',
            ),
            
        	array(
        		'uid' => 'cash_wallet_cashback_percentise',
        		'label' => 'Cashback to cash wallet',
        		'section' => 'our_first_section',
                'type' => 'number',
                'placeholder' => 'Put number',
				'helper' => '%',
				'supplimental' => 'set only number!',
            ),
            
            array(
        		'uid' => 'site_wallet_cashback_percentise',
        		'label' => 'Cashback to site wallet',
        		'section' => 'our_first_section',
                'type' => 'number',
                'placeholder' => 'Put number',
				'helper' => '%',
				'supplimental' => 'set only number!',
            ),
            

        	// array(
        	// 	'uid' => 'awesome_textarea',
        	// 	'label' => 'Sample Text Area',
        	// 	'section' => 'our_first_section',
        	// 	'type' => 'textarea',
        	// ),
        	array(
        		'uid' => 'wallet_show_at_menu',
        		'label' => 'Wallet show at menu',
        		'section' => 'our_first_section',
        		'type' => 'select',
        		'options' => array(
        			'yes' => 'Yes',
        			'no' => 'no',
        		),
                'default' => array()
        	),
        	// array(
        	// 	'uid' => 'awesome_multiselect',
        	// 	'label' => 'Sample Multi Select',
        	// 	'section' => 'our_first_section',
        	// 	'type' => 'multiselect',
        	// 	'options' => array(
        	// 		'option1' => 'Option 1',
        	// 		'option2' => 'Option 2',
        	// 		'option3' => 'Option 3',
        	// 		'option4' => 'Option 4',
        	// 		'option5' => 'Option 5',
        	// 	),
            //     'default' => array()
        	// ),
        	// array(
        	// 	'uid' => 'awesome_radio',
        	// 	'label' => 'Sample Radio Buttons',
        	// 	'section' => 'our_first_section',
        	// 	'type' => 'radio',
        	// 	'options' => array(
        	// 		'option1' => 'Option 1',
        	// 		'option2' => 'Option 2',
        	// 		'option3' => 'Option 3',
        	// 		'option4' => 'Option 4',
        	// 		'option5' => 'Option 5',
        	// 	),
            //     'default' => array()
        	// ),
        	// array(
        	// 	'uid' => 'awesome_checkboxes',
        	// 	'label' => 'Sample Checkboxes',
        	// 	'section' => 'our_first_section',
        	// 	'type' => 'checkbox',
        	// 	'options' => array(
        	// 		'option1' => 'Option 1',
        	// 		'option2' => 'Option 2',
        	// 		'option3' => 'Option 3',
        	// 		'option4' => 'Option 4',
        	// 		'option5' => 'Option 5',
        	// 	),
            //     'default' => array()
        	//)
        );
    	foreach( $fields as $field ){

        	add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'wp_cashback_wallet_settings', $field['section'], $field );
            register_setting( 'wp_cashback_wallet_settings', $field['uid'] );
    	}
    }

    public function field_callback( $arguments ) {

        $value = get_option( $arguments['uid'] );

        if( ! $value ) {
            $value = $arguments['default'];
        }

        switch( $arguments['type'] ){
            case 'text':
            case 'password':
            case 'number':
                printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
                break;
            case 'textarea':
                printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value );
                break;
            case 'select':
            case 'multiselect':
                if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
                    $attributes = '';
                    $options_markup = '';
                    foreach( $arguments['options'] as $key => $label ){
                        $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value[ array_search( $key, $value, true ) ], $key, false ), $label );
                    }
                    if( $arguments['type'] === 'multiselect' ){
                        $attributes = ' multiple="multiple" ';
                    }
                    printf( '<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $arguments['uid'], $attributes, $options_markup );
                }
                break;
            case 'radio':
            case 'checkbox':
                if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
                    $options_markup = '';
                    $iterator = 0;
                    foreach( $arguments['options'] as $key => $label ){
                        $iterator++;
                        $options_markup .= sprintf( '<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', $arguments['uid'], $arguments['type'], $key, checked( $value[ array_search( $key, $value, true ) ], $key, false ), $label, $iterator );
                    }
                    printf( '<fieldset>%s</fieldset>', $options_markup );
                }
                break;
        }

        if( $helper = $arguments['helper'] ){
            printf( '<span class="helper"> %s</span>', $helper );
        }

        if( $supplimental = $arguments['supplimental'] ){
            printf( '<p class="description">%s</p>', $supplimental );
        }

    }

}
new WP_Cashback_Wallet_Settings();