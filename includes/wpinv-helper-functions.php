<?php
/**
 * Contains functions related to Invoicing plugin.
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

function wpinv_item_quantities_enabled() {
    $ret = wpinv_get_option( 'item_quantities', true );
    
    return (bool) apply_filters( 'wpinv_item_quantities_enabled', $ret );
}

function wpinv_get_ip() {
    $ip = '127.0.0.1';

    if ( !empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
    } elseif ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
    } elseif( !empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
    }
    
    return apply_filters( 'wpinv_get_ip', $ip );
}

function wpinv_get_user_agent() {
    if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        $user_agent = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] );
    } else {
        $user_agent = '';
    }
    
    return apply_filters( 'wpinv_get_user_agent', $user_agent );
}

function wpinv_sanitize_amount( $amount ) {
    $is_negative   = false;
    $thousands_sep = wpinv_thousands_seperator();
    $decimal_sep   = wpinv_decimal_seperator();

    // Sanitize the amount
    if ( $decimal_sep == ',' && false !== ( $found = strpos( $amount, $decimal_sep ) ) ) {
        if ( ( $thousands_sep == '.' || $thousands_sep == ' ' ) && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
            $amount = str_replace( $thousands_sep, '', $amount );
        } elseif( empty( $thousands_sep ) && false !== ( $found = strpos( $amount, '.' ) ) ) {
            $amount = str_replace( '.', '', $amount );
        }

        $amount = str_replace( $decimal_sep, '.', $amount );
    } elseif( $thousands_sep == ',' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
        $amount = str_replace( $thousands_sep, '', $amount );
    }

    if( $amount < 0 ) {
        $is_negative = true;
    }

    $amount   = preg_replace( '/[^0-9\.]/', '', $amount );

    $decimals = apply_filters( 'wpinv_sanitize_amount_decimals', 2, $amount );
    $amount   = number_format( (double) $amount, $decimals, '.', '' );

    if( $is_negative ) {
        $amount *= -1;
    }

    return apply_filters( 'wpinv_sanitize_amount', $amount );
}

function wpinv_get_invoice_statuses() {
    $invoice_statuses = array(
        'pending'       => __( 'Pending Payment', 'invoicing' ),
        'publish'       => __( 'Paid', 'invoicing' ),
        'processing'    => __( 'Processing', 'invoicing' ),
        'onhold'        => __( 'On Hold', 'invoicing' ),
        'refunded'      => __( 'Refunded', 'invoicing' ),
        'cancelled'     => __( 'Cancelled', 'invoicing' ),
        'failed'        => __( 'Failed', 'invoicing' ),
        'renewal'       => __( 'Renewal Payment', 'invoicing' )
    );

    return apply_filters( 'wpinv_statuses', $invoice_statuses );
}

function wpinv_status_nicename( $status ) {
    $statuses = wpinv_get_invoice_statuses();
    $status   = isset( $statuses[$status] ) ? $statuses[$status] : __( $status, 'invoicing' );

    return $status;
}

function wpinv_get_currency() {
    $currency = wpinv_get_option( 'currency', 'USD' );
    
    return apply_filters( 'wpinv_currency', $currency );
}

function wpinv_currency_symbol( $currency = '' ) {
    if ( empty( $currency ) ) {
        $currency = wpinv_get_currency();
    }
    
    $symbols = apply_filters( 'wpinv_currency_symbols', array(
        'AED' => 'د.إ',
        'ARS' => '&#36;',
        'AUD' => '&#36;',
        'BDT' => '&#2547;&nbsp;',
        'BGN' => '&#1083;&#1074;.',
        'BRL' => '&#82;&#36;',
        'CAD' => '&#36;',
        'CHF' => '&#67;&#72;&#70;',
        'CLP' => '&#36;',
        'CNY' => '&yen;',
        'COP' => '&#36;',
        'CZK' => '&#75;&#269;',
        'DKK' => 'DKK',
        'DOP' => 'RD&#36;',
        'EGP' => 'EGP',
        'EUR' => '&euro;',
        'GBP' => '&pound;',
        'HKD' => '&#36;',
        'HRK' => 'Kn',
        'HUF' => '&#70;&#116;',
        'IDR' => 'Rp',
        'ILS' => '&#8362;',
        'INR' => '&#8377;',
        'ISK' => 'Kr.',
        'JPY' => '&yen;',
        'KES' => 'KSh',
        'KRW' => '&#8361;',
        'LAK' => '&#8365;',
        'MXN' => '&#36;',
        'MYR' => '&#82;&#77;',
        'NGN' => '&#8358;',
        'NOK' => '&#107;&#114;',
        'NPR' => '&#8360;',
        'NZD' => '&#36;',
        'PHP' => '&#8369;',
        'PKR' => '&#8360;',
        'PLN' => '&#122;&#322;',
        'PYG' => '&#8370;',
        'RMB' => '&yen;',
        'RON' => 'lei',
        'RUB' => '&#8381;',
        'SAR' => '&#x631;.&#x633;',
        'SEK' => '&#107;&#114;',
        'SGD' => '&#36;',
        'THB' => '&#3647;',
        'TRY' => '&#8378;',
        'TWD' => '&#78;&#84;&#36;',
        'UAH' => '&#8372;',
        'USD' => '&#36;',
        'VND' => '&#8363;',
        'ZAR' => '&#82;',
    ) );

    $currency_symbol = isset( $symbols[$currency] ) ? $symbols[$currency] : '&#36;';

    return apply_filters( 'wpinv_currency_symbol', $currency_symbol, $currency );
}

function wpinv_currency_position() {
    $position = wpinv_get_option( 'currency_position', 'left' );
    
    return apply_filters( 'wpinv_currency_position', $position );
}

function wpinv_thousands_seperator() {
    $thousand_sep = wpinv_get_option( 'thousands_seperator', ',' );
    
    return apply_filters( 'wpinv_thousands_seperator', $thousand_sep );
}

function wpinv_decimal_seperator() {
    $decimal_sep = wpinv_get_option( 'decimal_seperator', '.' );
    
    return apply_filters( 'wpinv_decimal_seperator', $decimal_sep );
}

function wpinv_decimals() {
    $decimals = wpinv_get_option( 'decimals', 2 );
    
    return apply_filters( 'wpinv_decimals', $decimals );
}

function wpinv_get_currencies() {
    $currencies = array(
        'USD'  => __( 'US Dollars (&#36;)', 'invoicing' ),
        'EUR'  => __( 'Euros (&euro;)', 'invoicing' ),
        'GBP'  => __( 'Pounds Sterling (&pound;)', 'invoicing' ),
        'AUD'  => __( 'Australian Dollars (&#36;)', 'invoicing' ),
        'BRL'  => __( 'Brazilian Real (R&#36;)', 'invoicing' ),
        'CAD'  => __( 'Canadian Dollars (&#36;)', 'invoicing' ),
        'CZK'  => __( 'Czech Koruna', 'invoicing' ),
        'DKK'  => __( 'Danish Krone', 'invoicing' ),
        'HKD'  => __( 'Hong Kong Dollar (&#36;)', 'invoicing' ),
        'HUF'  => __( 'Hungarian Forint', 'invoicing' ),
        'ILS'  => __( 'Israeli Shekel (&#8362;)', 'invoicing' ),
        'JPY'  => __( 'Japanese Yen (&yen;)', 'invoicing' ),
        'MYR'  => __( 'Malaysian Ringgits', 'invoicing' ),
        'MXN'  => __( 'Mexican Peso (&#36;)', 'invoicing' ),
        'NZD'  => __( 'New Zealand Dollar (&#36;)', 'invoicing' ),
        'NOK'  => __( 'Norwegian Krone', 'invoicing' ),
        'PHP'  => __( 'Philippine Pesos', 'invoicing' ),
        'PLN'  => __( 'Polish Zloty', 'invoicing' ),
        'SGD'  => __( 'Singapore Dollar (&#36;)', 'invoicing' ),
        'SEK'  => __( 'Swedish Krona', 'invoicing' ),
        'CHF'  => __( 'Swiss Franc', 'invoicing' ),
        'TWD'  => __( 'Taiwan New Dollars', 'invoicing' ),
        'THB'  => __( 'Thai Baht (&#3647;)', 'invoicing' ),
        'INR'  => __( 'Indian Rupee (&#8377;)', 'invoicing' ),
        'TRY'  => __( 'Turkish Lira (&#8378;)', 'invoicing' ),
        'RIAL' => __( 'Iranian Rial (&#65020;)', 'invoicing' ),
        'RUB'  => __( 'Russian Rubles', 'invoicing' )
    );

    return apply_filters( 'wpinv_currencies', $currencies );
}

function wpinv_price( $amount = '', $currency = '' ) {
    if( empty( $currency ) ) {
        $currency = wpinv_get_currency();
    }

    $position = wpinv_currency_position();

    $negative = $amount < 0;

    if ( $negative ) {
        $amount = substr( $amount, 1 );
    }

    $symbol = wpinv_currency_symbol( $currency );

    if ( $position == 'left' || $position == 'left_space' ) {
        switch ( $currency ) {
            case "GBP" :
            case "BRL" :
            case "EUR" :
            case "USD" :
            case "AUD" :
            case "CAD" :
            case "HKD" :
            case "MXN" :
            case "NZD" :
            case "SGD" :
            case "JPY" :
                $price = $position == 'left_space' ? $symbol . ' ' .  $amount : $symbol . $amount;
                break;
            default :
                //$price = $currency . ' ' . $amount;
                $price = $position == 'left_space' ? $symbol . ' ' .  $amount : $symbol . $amount;
                break;
        }
    } else {
        switch ( $currency ) {
            case "GBP" :
            case "BRL" :
            case "EUR" :
            case "USD" :
            case "AUD" :
            case "CAD" :
            case "HKD" :
            case "MXN" :
            case "SGD" :
            case "JPY" :
                $price = $position == 'right_space' ? $amount . ' ' .  $symbol : $amount . $symbol;
                break;
            default :
                //$price = $amount . ' ' . $currency;
                $price = $position == 'right_space' ? $amount . ' ' .  $symbol : $amount . $symbol;
                break;
        }
    }
    
    if ( $negative ) {
        $price = '-' . $price;
    }
    
    $price = apply_filters( 'wpinv_' . strtolower( $currency ) . '_currency_filter_' . $position, $price, $currency, $amount );

    return $price;
}

function wpinv_format_amount( $amount, $decimals = NULL, $calculate = false ) {
    $thousands_sep = wpinv_thousands_seperator();
    $decimal_sep   = wpinv_decimal_seperator();

    if ( $decimals === NULL ) {
        $decimals = wpinv_decimals();
    }

    if ( $decimal_sep == ',' && false !== ( $sep_found = strpos( $amount, $decimal_sep ) ) ) {
        $whole = substr( $amount, 0, $sep_found );
        $part = substr( $amount, $sep_found + 1, ( strlen( $amount ) - 1 ) );
        $amount = $whole . '.' . $part;
    }

    if ( $thousands_sep == ',' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
        $amount = str_replace( ',', '', $amount );
    }

    if ( $thousands_sep == ' ' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
        $amount = str_replace( ' ', '', $amount );
    }

    if ( empty( $amount ) ) {
        $amount = 0;
    }
    
    $decimals  = apply_filters( 'wpinv_amount_format_decimals', $decimals ? $decimals : 0, $amount, $calculate );
    $formatted = number_format( (float)$amount, $decimals, $decimal_sep, $thousands_sep );
    
    if ( $calculate ) {
        if ( $thousands_sep === "," ) {
            $formatted = str_replace( ",", "", $formatted );
        }
        
        if ( $decimal_sep === "," ) {
            $formatted = str_replace( ",", ".", $formatted );
        }
    }

    return apply_filters( 'wpinv_amount_format', $formatted, $amount, $decimals, $decimal_sep, $thousands_sep, $calculate );
}

function wpinv_sanitize_key( $key ) {
    $raw_key = $key;
    $key = preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );

    return apply_filters( 'wpinv_sanitize_key', $key, $raw_key );
}

function wpinv_get_file_extension( $str ) {
    $parts = explode( '.', $str );
    return end( $parts );
}

function wpinv_string_is_image_url( $str ) {
    $ext = wpinv_get_file_extension( $str );

    switch ( strtolower( $ext ) ) {
        case 'jpeg';
        case 'jpg';
            $return = true;
            break;
        case 'png';
            $return = true;
            break;
        case 'gif';
            $return = true;
            break;
        default:
            $return = false;
            break;
    }

    return (bool)apply_filters( 'wpinv_string_is_image', $return, $str );
}

function wpinv_error_log( $log, $title = '', $file = '', $line = '', $exit = false ) {
    $should_log = apply_filters( 'wpinv_log_errors', WP_DEBUG );
    
    if ( true === $should_log ) {
        $label = '';
        if ( $file && $file !== '' ) {
            $label .= basename( $file ) . ( $line ? '(' . $line . ')' : '' );
        }
        
        if ( $title && $title !== '' ) {
            $label = $label !== '' ? $label . ' ' : '';
            $label .= $title . ' ';
        }
        
        $label = $label !== '' ? trim( $label ) . ' : ' : '';
        
        if ( is_array( $log ) || is_object( $log ) ) {
            error_log( $label . print_r( $log, true ) );
        } else {
            error_log( $label . $log );
        }
        
        if ( $exit ) {
            exit;
        }
    }
}

function wpinv_is_ajax_disabled() {
    $retval = false;
    return apply_filters( 'wpinv_is_ajax_disabled', $retval );
}

function wpinv_get_current_page_url( $nocache = false ) {
    global $wp;

    if ( get_option( 'permalink_structure' ) ) {
        $base = trailingslashit( home_url( $wp->request ) );
    } else {
        $base = add_query_arg( $wp->query_string, '', trailingslashit( home_url( $wp->request ) ) );
        $base = remove_query_arg( array( 'post_type', 'name' ), $base );
    }

    $scheme = is_ssl() ? 'https' : 'http';
    $uri    = set_url_scheme( $base, $scheme );

    if ( is_front_page() ) {
        $uri = home_url( '/' );
    } elseif ( wpinv_is_checkout( array(), false ) ) {
        $uri = wpinv_get_checkout_uri();
    }

    $uri = apply_filters( 'wpinv_get_current_page_url', $uri );

    if ( $nocache ) {
        $uri = wpinv_add_cache_busting( $uri );
    }

    return $uri;
}

function wpinv_get_php_arg_separator_output() {
	return ini_get( 'arg_separator.output' );
}

function wpinv_rgb_from_hex( $color ) {
    $color = str_replace( '#', '', $color );
    // Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF"
    $color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );

    $rgb      = array();
    $rgb['R'] = hexdec( $color{0}.$color{1} );
    $rgb['G'] = hexdec( $color{2}.$color{3} );
    $rgb['B'] = hexdec( $color{4}.$color{5} );

    return $rgb;
}

function wpinv_hex_darker( $color, $factor = 30 ) {
    $base  = wpinv_rgb_from_hex( $color );
    $color = '#';

    foreach ( $base as $k => $v ) {
        $amount      = $v / 100;
        $amount      = round( $amount * $factor );
        $new_decimal = $v - $amount;

        $new_hex_component = dechex( $new_decimal );
        if ( strlen( $new_hex_component ) < 2 ) {
            $new_hex_component = "0" . $new_hex_component;
        }
        $color .= $new_hex_component;
    }

    return $color;
}

function wpinv_hex_lighter( $color, $factor = 30 ) {
    $base  = wpinv_rgb_from_hex( $color );
    $color = '#';

    foreach ( $base as $k => $v ) {
        $amount      = 255 - $v;
        $amount      = $amount / 100;
        $amount      = round( $amount * $factor );
        $new_decimal = $v + $amount;

        $new_hex_component = dechex( $new_decimal );
        if ( strlen( $new_hex_component ) < 2 ) {
            $new_hex_component = "0" . $new_hex_component;
        }
        $color .= $new_hex_component;
    }

    return $color;
}

function wpinv_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
    $hex = str_replace( '#', '', $color );

    $c_r = hexdec( substr( $hex, 0, 2 ) );
    $c_g = hexdec( substr( $hex, 2, 2 ) );
    $c_b = hexdec( substr( $hex, 4, 2 ) );

    $brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

    return $brightness > 155 ? $dark : $light;
}

function wpinv_format_hex( $hex ) {
    $hex = trim( str_replace( '#', '', $hex ) );

    if ( strlen( $hex ) == 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    return $hex ? '#' . $hex : null;
}