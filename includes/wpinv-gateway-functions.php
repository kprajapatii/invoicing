<?php
/**
 * Contains gateway functions.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns an array of payment gateways.
 */
function wpinv_get_payment_gateways() {
    // Default, built-in gateways
    $gateways = array(
        'authorizenet' => array(
            'admin_label'    => __( 'Authorize.Net (AIM)', 'invoicing' ),
            'checkout_label' => __( 'Authorize.Net - Credit Card / Debit Card', 'invoicing' ),
            'ordering'       => 4,
        ),
        'bank_transfer' => array(
            'admin_label'    => __( 'Pre Bank Transfer', 'invoicing' ),
            'checkout_label' => __( 'Pre Bank Transfer', 'invoicing' ),
            'ordering'       => 11,
        ),
    );

    $gateways = apply_filters( 'wpinv_payment_gateways', $gateways );
    return is_array( $gateways ) ? $gateways : array();
}

function wpinv_payment_gateway_titles( $all_gateways ) {
    global $wpinv_options;

    $gateways = array();
    foreach ( $all_gateways as $key => $gateway ) {
        if ( !empty( $wpinv_options[$key . '_title'] ) ) {
            $all_gateways[$key]['checkout_label'] = __( $wpinv_options[$key . '_title'], 'invoicing' );
        }

        $gateways[$key] = isset( $wpinv_options[$key . '_ordering'] ) ? $wpinv_options[$key . '_ordering'] : ( isset( $gateway['ordering'] ) ? $gateway['ordering'] : '' );
    }

    asort( $gateways );

    foreach ( $gateways as $gateway => $key ) {
        $gateways[$gateway] = $all_gateways[$gateway];
    }

    return $gateways;
}
add_filter( 'wpinv_payment_gateways', 'wpinv_payment_gateway_titles', 1000, 1 );

function wpinv_get_enabled_payment_gateways( $sort = false ) {
    $gateways = wpinv_get_payment_gateways();
    $enabled  = wpinv_get_option( 'gateways', false );

    $gateway_list = array();

    foreach ( $gateways as $key => $gateway ) {
        if ( isset( $enabled[ $key ] ) && $enabled[ $key ] == 1 ) {
            $gateway_list[ $key ] = $gateway;
        }
    }

    if ( true === $sort ) {
        uasort( $gateway_list, 'wpinv_sort_gateway_order' );
        
        // Reorder our gateways so the default is first
        $default_gateway_id = wpinv_get_default_gateway();

        if ( wpinv_is_gateway_active( $default_gateway_id ) ) {
            $default_gateway    = array( $default_gateway_id => $gateway_list[ $default_gateway_id ] );
            unset( $gateway_list[ $default_gateway_id ] );

            $gateway_list = array_merge( $default_gateway, $gateway_list );
        }
    }

    return apply_filters( 'wpinv_enabled_payment_gateways', $gateway_list );
}

function wpinv_sort_gateway_order( $a, $b ) {
    return $a['ordering'] - $b['ordering'];
}

function wpinv_is_gateway_active( $gateway ) {
    $gateways = wpinv_get_enabled_payment_gateways();

    $ret = is_array($gateways) && $gateway ?  array_key_exists( $gateway, $gateways ) : false;

    return apply_filters( 'wpinv_is_gateway_active', $ret, $gateway, $gateways );
}

function wpinv_get_default_gateway() {
    $default = wpinv_get_option( 'default_gateway', 'paypal' );

    if ( !wpinv_is_gateway_active( $default ) ) {
        $gateways = wpinv_get_enabled_payment_gateways();
        $gateways = array_keys( $gateways );
        $default  = reset( $gateways );
    }

    return apply_filters( 'wpinv_default_gateway', $default );
}

function wpinv_get_gateway_admin_label( $gateway ) {
    $gateways = wpinv_get_payment_gateways();
    $label    = isset( $gateways[ $gateway ] ) ? $gateways[ $gateway ]['admin_label'] : $gateway;
    $payment  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : false;

    if( $gateway == 'manual' && $payment ) {
        if( !( (float)wpinv_payment_total( $payment ) > 0 ) ) {
            $label = __( 'Free Purchase', 'invoicing' );
        }
    }

    return apply_filters( 'wpinv_gateway_admin_label', $label, $gateway );
}

function wpinv_get_gateway_description( $gateway ) {
    global $wpinv_options;

    $description = ! empty( $wpinv_options[$gateway . '_desc'] ) ? $wpinv_options[$gateway . '_desc'] : '';

    return apply_filters( 'wpinv_gateway_description', $description, $gateway );
}

function wpinv_get_gateway_button_label( $gateway ) {
    return apply_filters( 'wpinv_gateway_' . $gateway . '_button_label', '' );
}

function wpinv_get_gateway_checkout_label( $gateway ) {
    $gateways = wpinv_get_payment_gateways();
    $label    = isset( $gateways[ $gateway ] ) ? $gateways[ $gateway ]['checkout_label'] : $gateway;

    if( $gateway == 'manual' ) {
        $label = __( 'Manual Payment', 'invoicing' );
    }

    return apply_filters( 'wpinv_gateway_checkout_label', ucfirst( $label ), $gateway );
}

function wpinv_settings_sections_gateways( $settings ) {
    $gateways = wpinv_get_payment_gateways();
    
    if (!empty($gateways)) {
        foreach  ($gateways as $key => $gateway) {
            $settings[$key] = $gateway['admin_label'];
        }
    }
    
    return $settings;    
}
add_filter( 'wpinv_settings_sections_gateways', 'wpinv_settings_sections_gateways', 10, 1 );

/**
 * Adds GateWay settings.
 */
function wpinv_settings_gateways( $settings ) {

    // Loop through each gateway.
    foreach  ( wpinv_get_payment_gateways() as $key => $gateway ) {

        $gateway_settings = array(

            // Header.
            "{$key}_header" => array(

                'id'     => "{$key}_gateway_header",
                'name'   => '<h3>' . wp_sprintf( __( '%s Settings', 'invoicing' ), $gateway['admin_label'] ) . '</h3>',
                'custom' => $key,
                'type'   => 'gateway_header',

            ),

            // Activate/Deactivate a gateway.
            "{$key}_active" => array(
                'id'   => $key . '_active',
                'name' => __( 'Activate', 'invoicing' ),
                'desc' => wp_sprintf( __( 'Enable %s', 'invoicing' ), $gateway['admin_label'] ),
                'type' => 'checkbox',
            ),

            // Activate/Deactivate sandbox.
            "{$key}_sandbox" => array(
                'id'   => $key . '_sandbox',
                'name' => __( 'Sandbox', 'invoicing' ),
                'desc' => __( 'Enable sandbox to test payments', 'invoicing' ),
                'type' => 'checkbox',
            ),

            // Checkout title.
            "{$key}_title" => array(
                'id'   => $key . '_title',
                'name' => __( 'Checkout Title', 'invoicing' ),
                'std'  => isset( $gateway['checkout_label'] ) ? $gateway['checkout_label'] : '',
                'type' => 'text',
            ),

            // Checkout description.
            "{$key}_desc" => array(
                'id'   => $key . '_desc',
                'name' => __( 'Checkout Description', 'invoicing' ),
                'std'  => apply_filters( "getpaid_default_{$key}_checkout_description", '' ),
                'type' => 'text',
            ),

            // Checkout order.
            "{$key}_ordering" => array(
                'id'   => $key . '_ordering',
                'name' => __( 'Priority', 'invoicing' ),
                'std'  => apply_filters( "getpaid_default_{$key}_checkout_description", '' ),
                'type' => 'number',
                'step' => '1',
                'min'  => '-100000',
                'max'  => '100000',
                'std'  => isset( $gateway['ordering'] ) ? $gateway['ordering'] : '10',
            ),

        );

        // Maybe remove the sandbox.
        if ( ! apply_filters( "wpinv_{$key}_supports_sandbox", false ) ) {
            unset( $gateway_settings["{$key}_sandbox"] );
        }
  
        $gateway_settings = apply_filters( 'wpinv_gateway_settings', $gateway_settings, $key, $gateway );
        $gateway_settings = apply_filters( 'wpinv_gateway_settings_' . $key, $gateway_settings, $gateway );
        
        $settings[$key] = $gateway_settings;
    }

    return $settings;

}
add_filter( 'wpinv_settings_gateways', 'wpinv_settings_gateways', 10, 1 );

function wpinv_gateway_header_callback( $args ) {
    echo '<input type="hidden" id="wpinv_settings[save_gateway]" name="wpinv_settings[save_gateway]" value="' . esc_attr( $args['custom'] ) . '" />';
}

function wpinv_get_gateway_supports( $gateway ) {
    $gateways = wpinv_get_enabled_payment_gateways();
    $supports = isset( $gateways[ $gateway ]['supports'] ) ? $gateways[ $gateway ]['supports'] : array();
    return apply_filters( 'wpinv_gateway_supports', $supports, $gateway );
}

function wpinv_gateway_supports_buy_now( $gateway ) {
    $supports = wpinv_get_gateway_supports( $gateway );
    $ret = in_array( 'buy_now', $supports );
    return apply_filters( 'wpinv_gateway_supports_buy_now', $ret, $gateway );
}

function wpinv_shop_supports_buy_now() {
    $gateways = wpinv_get_enabled_payment_gateways();
    $ret      = false;

    if ( !wpinv_use_taxes()  && $gateways ) {
        foreach ( $gateways as $gateway_id => $gateway ) {
            if ( wpinv_gateway_supports_buy_now( $gateway_id ) ) {
                $ret = true;
                break;
            }
        }
    }

    return apply_filters( 'wpinv_shop_supports_buy_now', $ret );
}


function wpinv_show_gateways() {
    $gateways = wpinv_get_enabled_payment_gateways();
    $show_gateways = false;

    $chosen_gateway = isset( $_GET['payment-mode'] ) ? preg_replace('/[^a-zA-Z0-9-_]+/', '', $_GET['payment-mode'] ) : false;

    if ( count( $gateways ) > 1 && empty( $chosen_gateway ) ) {
        $show_gateways = true;
        if ( wpinv_get_cart_total() <= 0 ) {
            $show_gateways = false;
        }
    }
    
    if ( !$show_gateways && wpinv_cart_has_recurring_item() ) {
        $show_gateways = true;
    }

    return apply_filters( 'wpinv_show_gateways', $show_gateways );
}

function wpinv_get_chosen_gateway( $invoice_id = 0 ) {
	$gateways = array_keys( wpinv_get_enabled_payment_gateways() );

    $chosen = false;
    if ( $invoice_id > 0 && $invoice = wpinv_get_invoice( $invoice_id ) ) {
        $chosen = $invoice->get_gateway();
    }

	$chosen   = isset( $_REQUEST['payment-mode'] ) ? sanitize_text_field( $_REQUEST['payment-mode'] ) : $chosen;

	if ( false !== $chosen ) {
		$chosen = preg_replace('/[^a-zA-Z0-9-_]+/', '', $chosen );
	}

	if ( ! empty ( $chosen ) ) {
		$enabled_gateway = urldecode( $chosen );
	} else if (  !empty( $invoice ) && (float)$invoice->get_subtotal() <= 0 ) {
		$enabled_gateway = 'manual';
	} else {
		$enabled_gateway = wpinv_get_default_gateway();
	}
    
    if ( !wpinv_is_gateway_active( $enabled_gateway ) && !empty( $gateways ) ) {
        if(wpinv_is_gateway_active( wpinv_get_default_gateway()) ){
            $enabled_gateway = wpinv_get_default_gateway();
        }else{
            $enabled_gateway = $gateways[0];
        }

    }

	return apply_filters( 'wpinv_chosen_gateway', $enabled_gateway );
}

function wpinv_record_gateway_error( $title = '', $message = '', $parent = 0 ) {
    return wpinv_error_log( $message, $title );
}

function wpinv_count_sales_by_gateway( $gateway_id = 'paypal', $status = 'publish' ) {
	$ret  = 0;
	$args = array(
		'meta_key'    => '_wpinv_gateway',
		'meta_value'  => $gateway_id,
		'nopaging'    => true,
		'post_type'   => 'wpi_invoice',
		'post_status' => $status,
		'fields'      => 'ids'
	);

	$payments = new WP_Query( $args );

	if( $payments )
		$ret = $payments->post_count;
	return $ret;
}

function wpinv_settings_update_gateways( $input ) {
    global $wpinv_options;
    
    if ( !empty( $input['save_gateway'] ) ) {
        $gateways = wpinv_get_option( 'gateways', false );
        $gateways = !empty($gateways) ? $gateways : array();
        $gateway = $input['save_gateway'];
        
        if ( !empty( $input[$gateway . '_active'] ) ) {
            $gateways[$gateway] = 1;
        } else {
            if ( isset( $gateways[$gateway] ) ) {
                unset( $gateways[$gateway] );
            }
        }
        
        $input['gateways'] = $gateways;
    }
    
    if ( !empty( $input['default_gateway'] ) ) {
        $gateways = wpinv_get_payment_gateways();
        
        foreach ( $gateways as $key => $gateway ) {
            $active   = 0;
            if ( !empty( $input['gateways'] ) && !empty( $input['gateways'][$key] ) ) {
                $active = 1;
            }
            
            $input[$key . '_active'] = $active;
            
            if ( empty( $wpinv_options[$key . '_title'] ) ) {
                $input[$key . '_title'] = $gateway['checkout_label'];
            }
            
            if ( !isset( $wpinv_options[$key . '_ordering'] ) && isset( $gateway['ordering'] ) ) {
                $input[$key . '_ordering'] = $gateway['ordering'];
            }
        }
    }
    
    return $input;
}
add_filter( 'wpinv_settings_tab_gateways_sanitize', 'wpinv_settings_update_gateways', 10, 1 );

// PayPal Standard settings
function wpinv_gateway_settings_paypal( $setting ) {    
    $setting['paypal_active']['desc'] = $setting['paypal_active']['desc'] . ' ' . __( '( Supported Currencies: AUD, BRL, CAD, CZK, DKK, EUR, HKD, HUF, ILS, JPY, MYR, MXN, NOK, NZD, PHP, PLN, GBP, SGD, SEK, CHF, TWD, THB, USD )', 'invoicing' );
    $setting['paypal_desc']['std'] = __( 'Pay via PayPal: you can pay with your credit card if you don\'t have a PayPal account.', 'invoicing' );
    
    $setting['paypal_sandbox'] = array(
            'type' => 'checkbox',
            'id'   => 'paypal_sandbox',
            'name' => __( 'PayPal Sandbox', 'invoicing' ),
            'desc' => __( 'PayPal sandbox can be used to test payments.', 'invoicing' ),
            'std'  => 1
        );
        
    $setting['paypal_email'] = array(
            'type' => 'text',
            'id'   => 'paypal_email',
            'name' => __( 'PayPal Email', 'invoicing' ),
            'desc' => __( 'Please enter your PayPal account\'s email address. Ex: myaccount@paypal.com', 'invoicing' ),
            'std' => __( 'myaccount@paypal.com', 'invoicing' ),
        );
    /*
    $setting['paypal_ipn_url'] = array(
            'type' => 'text',
            'id'   => 'paypal_ipn_url',
            'name' => __( 'PayPal IPN Url', 'invoicing' ),
            'desc' => __( 'Configure Instant Payment Notifications(IPN) url at PayPal. Ex: http://yoursite.com/?wpi-ipn=paypal', 'invoicing' ),
            'size' => 'large'
        );
    */
        
    return $setting;
}
add_filter( 'wpinv_gateway_settings_paypal', 'wpinv_gateway_settings_paypal', 10, 1 );

// Pre Bank Transfer settings
function wpinv_gateway_settings_bank_transfer( $setting ) {
    $setting['bank_transfer_desc']['std'] = __( 'Make your payment directly into our bank account. Please use your Invoice ID as the payment reference. Your invoice won\'t be processed until the funds have cleared in our account.', 'invoicing' );
    
    $setting['bank_transfer_ac_name'] = array(
            'type' => 'text',
            'id' => 'bank_transfer_ac_name',
            'name' => __( 'Account Name', 'invoicing' ),
            'desc' => __( 'Enter the bank account name to which you want to transfer payment.', 'invoicing' ),
            'std'  =>  __( 'Mr. John Martin', 'invoicing' ),
        );
    
    $setting['bank_transfer_ac_no'] = array(
            'type' => 'text',
            'id' => 'bank_transfer_ac_no',
            'name' => __( 'Account Number', 'invoicing' ),
            'desc' => __( 'Enter your bank account number.', 'invoicing' ),
            'std'  =>  __( 'TEST1234567890', 'invoicing' ),
        );
    
    $setting['bank_transfer_bank_name'] = array(
            'type' => 'text',
            'id'   => 'bank_transfer_bank_name',
            'name' => __( 'Bank Name', 'invoicing' ),
            'desc' => __( 'Enter the bank name to which you want to transfer payment.', 'invoicing' ),
            'std' => __( 'ICICI Bank', 'invoicing' ),
        );
    
    $setting['bank_transfer_ifsc'] = array(
            'type' => 'text',
            'id'   => 'bank_transfer_ifsc',
            'name' => __( 'IFSC Code', 'invoicing' ),
            'desc' => __( 'Enter your bank IFSC code.', 'invoicing' ),
            'std'  =>  __( 'ICIC0001234', 'invoicing' ),
        );
        
    $setting['bank_transfer_iban'] = array(
            'type' => 'text',
            'id'   => 'bank_transfer_iban',
            'name' => __( 'IBAN', 'invoicing' ),
            'desc' => __( 'Enter your International Bank Account Number(IBAN).', 'invoicing' ),
            'std'  =>  __( 'GB29NWBK60161331926819', 'invoicing' ),
        );
        
    $setting['bank_transfer_bic'] = array(
            'type' => 'text',
            'id'   => 'bank_transfer_bic',
            'name' => __( 'BIC/Swift Code', 'invoicing' ),
            'std'  =>  __( 'ICICGB2L129', 'invoicing' ),
        );

    $setting['bank_transfer_sort_code'] = array(
        'type' => 'text',
        'id'   => 'bank_transfer_sort_code',
        'name' => __( 'Sort Code', 'invoicing' ),
        'std'  =>  __( '12-34-56', 'invoicing' ),
    );
        
    $setting['bank_transfer_info'] = array(
            'id'   => 'bank_transfer_info',
            'name' => __( 'Instructions', 'invoicing' ),
            'desc' => __( 'Instructions that will be added to the thank you page and emails.', 'invoicing' ),
            'type' => 'textarea',
            'std' => __( 'Make your payment directly into our bank account. Please use your Invoice ID as the payment reference. Your invoice won\'t be processed until the funds have cleared in our account.', 'invoicing' ),
            'cols' => 37,
            'rows' => 5
        );
        
    return $setting;
}
add_filter( 'wpinv_gateway_settings_bank_transfer', 'wpinv_gateway_settings_bank_transfer', 10, 1 );

// Authorize.Net settings
function wpinv_gateway_settings_authorizenet( $setting ) {
    $setting['authorizenet_active']['desc'] = $setting['authorizenet_active']['desc'] . ' ' . __( '( Supported Currencies: AUD, CAD, CHF, DKK, EUR, GBP, JPY, NOK, NZD, PLN, SEK, USD, ZAR )', 'invoicing' );
    $setting['authorizenet_desc']['std'] = __( 'Pay using a Authorize.Net to process Credit card / Debit card transactions.', 'invoicing' );
    
    $setting['authorizenet_sandbox'] = array(
            'type' => 'checkbox',
            'id'   => 'authorizenet_sandbox',
            'name' => __( 'Authorize.Net Test Mode', 'invoicing' ),
            'desc' => __( 'Enable Authorize.Net test mode to test payments.', 'invoicing' ),
            'std'  => 1
        );
        
    $setting['authorizenet_login_id'] = array(
            'type' => 'text',
            'id'   => 'authorizenet_login_id',
            'name' => __( 'API Login ID', 'invoicing' ),
            'desc' => __( 'API Login ID can be obtained from Authorize.Net Account > Settings > Security Settings > General Security Settings > API Credentials & Keys. Example : 2j4rBekUnD', 'invoicing' ),
            'std' => '2j4rBekUnD',
        );
    
    $setting['authorizenet_transaction_key'] = array(
            'type' => 'text',
            'id'   => 'authorizenet_transaction_key',
            'name' => __( 'Transaction Key', 'invoicing' ),
            'desc' => __( 'Transaction Key can be obtained from Authorize.Net Account > Settings > Security Settings > General Security Settings > API Credentials & Keys. Example : 4vyBUOJgR74679xa', 'invoicing' ),
            'std' => '4vyBUOJgR74679xa',
        );
        
    $setting['authorizenet_md5_hash'] = array(
            'type' => 'text',
            'id'   => 'authorizenet_md5_hash',
            'name' => __( 'MD5-Hash', 'invoicing' ),
            'desc' => __( 'The MD5 Hash security feature allows to authenticate transaction responses from the Authorize.Net for recurring payments. It can be obtained from Authorize.Net Account > Settings > Security Settings > General Settings > MD5 Hash.', 'invoicing' ),
            'std' => '',
        );

    $setting['authorizenet_transaction_type'] = array(
        'id'          => 'authorizenet_transaction_type',
        'name'        => __( 'Transaction Type', 'invoicing' ),
        'desc'        => __( 'Choose transaction type.', 'invoicing' ),
        'type'        => 'select',
        'class'       => 'wpi_select2',
        'options'     => array(
            'authorize_capture' => __( 'Authorize And Capture', 'invoicing' ),
            'authorize_only' => __( 'Authorize Only', 'invoicing' ),
        ),
        'std'         => 'authorize_capture'
    );

    $setting['authorizenet_transaction_type_recurring'] = array(
        'id'          => 'authorizenet_transaction_type_recurring',
        'name'        => __( 'Transaction Type for Recurring', 'invoicing' ),
        'desc'        => __( 'Choose transaction type for recurring payments.', 'invoicing' ),
        'type'        => 'select',
        'class'       => 'wpi_select2',
        'options'     => array(
            'authorize_capture' => __( 'Authorize And Capture', 'invoicing' ),
            'authorize_only' => __( 'Authorize Only', 'invoicing' ),
        ),
        'std'         => 'authorize_only'
    );
        
    $setting['authorizenet_ipn_url'] = array(
            'type' => 'ipn_url',
            'id'   => 'authorizenet_ipn_url',
            'name' => __( 'Silent Post URL', 'invoicing' ),
            'std' => wpinv_get_ipn_url( 'authorizenet' ),
            'desc' => __( 'If you are accepting recurring payments then you must set this url at Authorize.Net Account > Settings > Transaction Format Settings > Transaction Response Settings > Silent Post URL.', 'invoicing' ),
            'size' => 'large',
            'custom' => 'authorizenet',
            'readonly' => true
        );
        
    return $setting;
}
add_filter( 'wpinv_gateway_settings_authorizenet', 'wpinv_gateway_settings_authorizenet', 10, 1 );

function wpinv_ipn_url_callback( $args ) {    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );
    
    $attrs = $args['readonly'] ? ' readonly' : '';

    $html = '<input style="background-color:#fefefe" type="text" ' . $attrs . ' value="' . esc_attr( $args['std'] ) . '" name="wpinv_settings[' . $sanitize_id . ']" id="wpinv_settings[' . $sanitize_id . ']" class="large-text">';
    $html .= '<label for="wpinv_settings[' . $sanitize_id . ']">'  . $args['desc'] . '</label>';

    echo $html;
}

function wpinv_is_test_mode( $gateway = '' ) {
    if ( empty( $gateway ) ) {
        return false;
    }
    
    $is_test_mode = wpinv_get_option( $gateway . '_sandbox', false );
    
    return apply_filters( 'wpinv_is_test_mode', $is_test_mode, $gateway );
}

function wpinv_get_ipn_url( $gateway = '', $args = array() ) {
    $data = array( 'wpi-listener' => 'IPN' );
    
    if ( !empty( $gateway ) ) {
        $data['wpi-gateway'] = wpinv_sanitize_key( $gateway );
    }
    
    $args = !empty( $args ) && is_array( $args ) ? array_merge( $data, $args ) : $data;
    
    $ipn_url = add_query_arg( $args,  home_url( 'index.php' ) );
    
    return apply_filters( 'wpinv_ipn_url', $ipn_url );
}

function wpinv_listen_for_payment_ipn() {
    // Regular PayPal IPN
    if ( isset( $_GET['wpi-listener'] ) && $_GET['wpi-listener'] == 'IPN' ) {
        do_action( 'wpinv_verify_payment_ipn' );
        
        if ( !empty( $_GET['wpi-gateway'] ) ) {
            wpinv_error_log( sanitize_text_field( $_GET['wpi-gateway'] ), 'WP Invoicing IPN', __FILE__, __LINE__ );
            do_action( 'wpinv_verify_' . sanitize_text_field( $_GET['wpi-gateway'] ) . '_ipn' );
        }
    }
}
add_action( 'init', 'wpinv_listen_for_payment_ipn' );

function wpinv_get_bank_instructions() {
    $bank_instructions = wpinv_get_option( 'bank_transfer_info' );
    
    return apply_filters( 'wpinv_bank_instructions', $bank_instructions );
}

function wpinv_get_bank_info( $filtered = false ) {
    $bank_fields = array(
        'bank_transfer_ac_name'     => __( 'Account Name', 'invoicing' ),
        'bank_transfer_ac_no'       => __( 'Account Number', 'invoicing' ),
        'bank_transfer_bank_name'   => __( 'Bank Name', 'invoicing' ),
        'bank_transfer_ifsc'        => __( 'IFSC code', 'invoicing' ),
        'bank_transfer_iban'        => __( 'IBAN', 'invoicing' ),
        'bank_transfer_bic'         => __( 'BIC/Swift code', 'invoicing' ),
        'bank_transfer_sort_code'   => __( 'Sort Code', 'invoicing' )
    );
    
    $bank_info = array();
    foreach ( $bank_fields as $field => $label ) {
        if ( $filtered && !( $value = wpinv_get_option( $field ) ) ) {
            continue;
        }
        
        $bank_info[$field] = array( 'label' => $label, 'value' => $value );
    }
    
    return apply_filters( 'wpinv_bank_info', $bank_info, $filtered );
}

function wpinv_get_post_data( $method = 'request' ) {
    $data       = array();
    $request    = $_REQUEST;
    
    if ( $method == 'post' ) {
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] != 'POST' ) {
            return $data;
        }
        
        $request = $_POST;
    }
    
    if ( $method == 'get' ) {
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] != 'GET' ) {
            return $data;
        }
        
        $request = $_GET;
    }
    
    // Set initial post data to empty string
    $post_data = '';
    
    // Fallback just in case post_max_size is lower than needed
    if ( ini_get( 'allow_url_fopen' ) ) {
        $post_data = file_get_contents( 'php://input' );
    } else {
        // If allow_url_fopen is not enabled, then make sure that post_max_size is large enough
        ini_set( 'post_max_size', '12M' );
    }
    // Start the encoded data collection with notification command
    $encoded_data = 'cmd=_notify-validate';

    // Get current arg separator
    $arg_separator = wpinv_get_php_arg_separator_output();

    // Verify there is a post_data
    if ( $post_data || strlen( $post_data ) > 0 ) {
        // Append the data
        $encoded_data .= $arg_separator . $post_data;
    } else {
        // Check if POST is empty
        if ( empty( $request ) ) {
            // Nothing to do
            return;
        } else {
            // Loop through each POST
            foreach ( $request as $key => $value ) {
                // Encode the value and append the data
                $encoded_data .= $arg_separator . "$key=" . urlencode( $value );
            }
        }
    }

    // Convert collected post data to an array
    wp_parse_str( $encoded_data, $data );

    foreach ( $data as $key => $value ) {
        if ( false !== strpos( $key, 'amp;' ) ) {
            $new_key = str_replace( '&amp;', '&', $key );
            $new_key = str_replace( 'amp;', '&' , $new_key );

            unset( $data[ $key ] );
            $data[ $new_key ] = sanitize_text_field( $value );
        }
    }
    
    return $data;
}

/**
 * Checks if a given gateway supports subscription payments.
 */
function wpinv_gateway_support_subscription( $gateway ) {
    $supports = false;

    if ( wpinv_is_gateway_active( $gateway ) ) {
        $supports = apply_filters( 'wpinv_' . $gateway . '_support_subscription', $supports );
        $supports = apply_filters( 'getapid_gateway_supports_subscription', $supports, $gateway );
    }

    return $supports;
}

/**
 * Filters payment form gateways.
 * 
 * @param array $gateways an array of gateways.
 * @param GetPaid_Payment_Form $form payment form.
 */
function wpinv_payment_gateways_on_cart( $gateways, $form ) {

    if ( $form->is_recurring() ) {

        foreach ( array_keys( $gateways ) as $gateway ) {

            if ( ! wpinv_gateway_support_subscription( $gateway ) ) {
                unset( $gateways[$gateway] );
            }

        }

    }

    return $gateways;
}
add_filter( 'getpaid_payment_form_gateways', 'wpinv_payment_gateways_on_cart', 10, 2 );

/**
 * Validates checkout fields.
 *
 * @param GetPaid_Payment_Form_Submission $submission
 */
function wpinv_checkout_validate_gateway( $submission ) {

    $data = $submission->get_data();

    // Non-recurring gateways should not be allowed to process recurring invoices.
    if ( $submission->has_recurring && ! wpinv_gateway_support_subscription( $data['wpi-gateway'] ) ) {
        wpinv_set_error( 'invalid_gateway', __( 'The selected payment gateway does not support subscription payment.', 'invoicing' ) );
    }

    if ( ! wpinv_is_gateway_active( $data['wpi-gateway'] ) ) {
        wpinv_set_error( 'invalid_gateway', __( 'The selected payment gateway is not active', 'invoicing' ) );
    }

}

/**
 * Validates a zip code.
 */
function wpinv_checkout_validate_cc_zip( $zip = 0, $country_code = '' ) {

    if ( empty( $zip ) || empty( $country_code ) ){
        return false;
    }

    // Prepare the country code.
    $country_code = strtoupper( trim( $country_code ) );

    // Fetch the regexes.
    $zip_regex = wpinv_get_data( 'zip-regexes' );

    // Check if it is valid.
    $is_valid = ! isset ( $zip_regex[ $country_code ] ) || preg_match( "/" . $zip_regex[ $country_code ] . "/i", $zip );

    return apply_filters( 'wpinv_is_zip_valid', $is_valid, $zip, $country_code );
}

function wpinv_checkout_validate_agree_to_terms() {
    // Validate agree to terms
    if ( ! isset( $_POST['wpi_agree_to_terms'] ) || $_POST['wpi_agree_to_terms'] != 1 ) {
        // User did not agree
        wpinv_set_error( 'agree_to_terms', apply_filters( 'wpinv_agree_to_terms_text', __( 'You must agree to the terms of use', 'invoicing' ) ) );
    }
}

function wpinv_checkout_validate_invoice_user() {
    global $wpi_cart, $user_ID;

    if(empty($wpi_cart)){
        $wpi_cart = wpinv_get_invoice_cart();
    }

    $invoice_user = (int)$wpi_cart->get_user_id();
    $valid_user_data = array(
        'user_id' => $invoice_user
    );

    // If guest checkout allowed
    if ( !wpinv_require_login_to_checkout() ) {
        return $valid_user_data;
    }
    
    // Verify there is a user_ID
    if ( $user_ID == $invoice_user ) {
        // Get the logged in user data
        $user_data = get_userdata( $user_ID );
        $required_fields  = wpinv_checkout_required_fields();

        // Loop through required fields and show error messages
         if ( !empty( $required_fields ) ) {
            foreach ( $required_fields as $field_name => $value ) {
                if ( in_array( $value, $required_fields ) && empty( $_POST[ 'wpinv_' . $field_name ] ) ) {
                    wpinv_set_error( $value['error_id'], $value['error_message'] );
                }
            }
        }

        // Verify data
        if ( $user_data ) {
            // Collected logged in user data
            $valid_user_data = array(
                'user_id'     => $user_ID,
                'email'       => isset( $_POST['wpinv_email'] ) ? sanitize_email( $_POST['wpinv_email'] ) : $user_data->user_email,
                'first_name'  => isset( $_POST['wpinv_first_name'] ) && ! empty( $_POST['wpinv_first_name'] ) ? sanitize_text_field( $_POST['wpinv_first_name'] ) : $user_data->first_name,
                'last_name'   => isset( $_POST['wpinv_last_name'] ) && ! empty( $_POST['wpinv_last_name']  ) ? sanitize_text_field( $_POST['wpinv_last_name']  ) : $user_data->last_name,
            );

            if ( !empty( $_POST[ 'wpinv_email' ] ) && !is_email( $_POST[ 'wpinv_email' ] ) ) {
                wpinv_set_error( 'invalid_email', __( 'Please enter a valid email address', 'invoicing' ) );
            }
        } else {
            // Set invalid user error
            wpinv_set_error( 'invalid_user', __( 'The user billing information is invalid', 'invoicing' ) );
        }
    } else {
        // Set invalid user error
        wpinv_set_error( 'invalid_user_id', __( 'The invalid invoice user id', 'invoicing' ) );
    }

    // Return user data
    return $valid_user_data;
}

function wpinv_checkout_validate_current_user() {
    global $wpi_cart;

    $data = array();
    
    if ( is_user_logged_in() ) {
        if ( !wpinv_require_login_to_checkout() || ( wpinv_require_login_to_checkout() && (int)$wpi_cart->get_user_id() === (int)get_current_user_id() ) ) {
            $data['user_id'] = (int)get_current_user_id();
        } else {
            wpinv_set_error( 'logged_in_only', __( 'You are not allowed to pay for this invoice', 'invoicing' ) );
        }
    } else {
        // If guest checkout allowed
        if ( !wpinv_require_login_to_checkout() ) {
            $data['user_id'] = 0;
        } else {
            wpinv_set_error( 'logged_in_only', __( 'You must be logged in to pay for this invoice', 'invoicing' ) );
        }
    }

    return $data;
}


/**
 * Processes checkout payments.
 *
 * @param WPInv_Invoice $invoice
 * @param GetPaid_Payment_Form_Submission $submission
 */
function wpinv_process_checkout( $invoice, $submission ) {

    // No need to send free invoices to the gateway.
    if ( $invoice->is_free() ) {
        $invoice->set_gateway( 'none' );
        $invoice->add_note( __( "This is a free invoice and won't be sent to the payment gateway", 'invoicing' ), false, false, true );
        $invoice->mark_paid();
        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
    }

    // Clear an checkout errors.
    wpinv_clear_errors();

    // Fires before sending to the gateway.
    do_action( 'getpaid_checkout_before_gateway', $invoice, $submission );

    // Allow the sumission data to be modified before it is sent to the gateway.
    $submission_data    = $submission->get_data();
    $submission_gateway = apply_filters( 'getpaid_gateway_submission_gateway', $submission_data['wpi-gateway'], $submission, $invoice );
    $submission_data    = apply_filters( 'getpaid_gateway_submission_data', $submission_data, $submission, $invoice );

    // Validate the currency.
    if ( ! apply_filters( "getpaid_gateway_{$submission_gateway}_is_valid_for_currency", true, $invoice->get_currency() ) ) {
        wpinv_set_error( 'invalid_currency', __( 'The chosen payment gateway does not support the invoice currency', 'invoicing' ) );
    }

    // Check to see if we have any errors.
    if ( wpinv_get_errors() ) {
        wpinv_send_back_to_checkout();
    }

    // Send info to the gateway for payment processing
    do_action( "getpaid_gateway_$submission_gateway", $invoice, $submission_data, $submission );

    // Backwards compatibility.
    do_action( "wpinv_gateway_$submission_gateway", null, $invoice, $submission_data, $submission );

}
