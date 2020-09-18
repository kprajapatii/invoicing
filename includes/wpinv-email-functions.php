<?php
/**
 * Contains email functions.
 *
 */

defined( 'ABSPATH' ) || exit;

/*
|--------------------------------------------------------------------------
| Email Template functions.
|--------------------------------------------------------------------------
*/

/**
 * Generates the email header.
 */
function wpinv_email_header( $email_heading ) {
    wpinv_get_template( 'emails/wpinv-email-header.php', compact( 'email_heading' ) );
}
add_action( 'wpinv_email_header', 'wpinv_email_header' );


/**
 * Generates the email footer.
 */
function wpinv_email_footer() {
    wpinv_get_template( 'emails/wpinv-email-footer.php' );
}
add_action( 'wpinv_email_footer', 'wpinv_email_footer' );


/**
 * Display invoice details in emails.
 * 
 * @param WPInv_Invoice $invoice
 * @param string $email_type
 * @param bool $sent_to_admin
 */
function wpinv_email_invoice_details( $invoice,  $email_type, $sent_to_admin ) {

    $args = compact( 'invoice', 'email_type', 'sent_to_admin' );
    wpinv_get_template( 'emails/wpinv-email-invoice-details.php', $args );

}
add_action( 'wpinv_email_invoice_details', 'wpinv_email_invoice_details', 10, 3 );


/**
 * Display line items in emails.
 * 
 * @param WPInv_Invoice $invoice
 * @param string $email_type
 * @param bool $sent_to_admin
 */
function wpinv_email_invoice_items( $invoice, $email_type, $sent_to_admin ) {

    // Prepare line items.
    $columns = getpaid_invoice_item_columns( $invoice );
    $columns = apply_filters( 'getpaid_invoice_line_items_table_columns', $columns, $invoice );

    // Load the template.
    wpinv_get_template( 'emails/wpinv-email-invoice-items.php', compact( 'invoice', 'columns', 'email_type', 'sent_to_admin' ) );

}
add_action( 'wpinv_email_invoice_items', 'wpinv_email_invoice_items', 10, 3 );


/**
 * Display billing details in emails.
 * 
 * @param WPInv_Invoice $invoice
 * @param string $email_type
 * @param bool $sent_to_admin
 */
function wpinv_email_billing_details( $invoice, $email_type, $sent_to_admin ) {

    $args = compact( 'invoice', 'email_type', 'sent_to_admin' );
    wpinv_get_template( 'emails/wpinv-email-billing-details.php', $args );

}
add_action( 'wpinv_email_billing_details', 'wpinv_email_billing_details', 10, 3 );

/**
 * Returns email css.
 * 
 */
function getpaid_get_email_css() {

    $css = wpinv_get_template_html( 'emails/wpinv-email-styles.php' );
    return apply_filters( 'wpinv_email_styles', $css );

}

/**
 * Inline styles to email content.
 * 
 * @param string $content
 * @return string
 * 
 */
function wpinv_email_style_body( $content ) {

    $css = getpaid_get_email_css();

    // Inline the css.
    try {
        $emogrifier = new Emogrifier( $content, $css );
        $_content   = $emogrifier->emogrify();
        $content    = $_content;
    } catch ( Exception $e ) {
        wpinv_error_log( $e->getMessage(), 'emogrifier' );
    }

    return $content;
}


// Backwards compatibility.
function wpinv_init_transactional_emails() {
    foreach ( apply_filters( 'wpinv_email_actions', array() ) as $action ) {
        add_action( $action, 'wpinv_send_transactional_email', 10, 10 );
    }
}
add_action( 'init', 'wpinv_init_transactional_emails' );







function wpinv_send_transactional_email() {
    $args       = func_get_args();
    $function   = current_filter() . '_notification';
    do_action_ref_array( $function, $args );
}

function wpinv_mail_get_from_address() {
    $from_address = apply_filters( 'wpinv_mail_from_address', wpinv_get_option( 'email_from' ) );
    return sanitize_email( $from_address );
}

function wpinv_mail_get_from_name() {
    $from_name = apply_filters( 'wpinv_mail_from_name', wpinv_get_option( 'email_from_name' ) );
    return wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES );
}

function wpinv_mail_admin_bcc_active( $mail_type = '' ) {
    $active = apply_filters( 'wpinv_mail_admin_bcc_active', wpinv_get_option( 'email_' . $mail_type . '_admin_bcc' ) );
    return ( $active ? true : false );
}
    
function wpinv_mail_get_content_type(  $content_type = 'text/html', $email_type = 'html' ) {
    $email_type = apply_filters( 'wpinv_mail_content_type', $email_type );

    switch ( $email_type ) {
        case 'html' :
            $content_type = 'text/html';
            break;
        case 'multipart' :
            $content_type = 'multipart/alternative';
            break;
        default :
            $content_type = 'text/plain';
            break;
    }

    return $content_type;
}

/**
 * Sends a single email.
 * 
 * @param string|array $to The recipient's email or an array of recipient emails.
 * @param string       $subject The email subject.
 * @param string       $message The email content.
 * @param mixed        $deprecated
 * @param array        $attachments Any files to attach to the email.
 * @param string|array $cc An email or array of extra emails to send a copy of the email to.
 */
function wpinv_mail_send( $to, $subject, $message, $deprecated, $attachments = array(), $cc = array() ) {

    $mailer  = new GetPaid_Notification_Email_Sender();
    $message = wpinv_email_style_body( $message );
    $to      = array_merge( wpinv_parse_list( $to ), wpinv_parse_list( $cc ) );

	return $mailer->send(
        $to,
        $subject,
        $message,
        $attachments
    );

}

/**
 * Returns an array of email settings.
 * 
 * @return array
 */
function wpinv_get_emails() {
    return apply_filters( 'wpinv_get_emails', wpinv_get_data( 'email-settings' ) );
}

/**
 * Filter email settings.
 * 
 * @param array $settings
 * @return array
 */
function wpinv_settings_emails( $settings = array() ) {
    $settings = array_merge( $settings, wpinv_get_emails() );
    return apply_filters( 'wpinv_settings_get_emails', $settings );
}
add_filter( 'wpinv_settings_emails', 'wpinv_settings_emails', 10, 1 );

/**
 * Filter email section names.
 * 
 */
function wpinv_settings_sections_emails( $settings ) {
    foreach  ( wpinv_get_emails() as $key => $email) {
        $settings[$key] = ! empty( $email['email_' . $key . '_header']['name'] ) ? strip_tags( $email['email_' . $key . '_header']['name'] ) : strip_tags( $key );
    }

    return $settings;    
}
add_filter( 'wpinv_settings_sections_emails', 'wpinv_settings_sections_emails', 10, 1 );

function wpinv_email_is_enabled( $email_type ) {
    $emails = wpinv_get_emails();
    $enabled = isset( $emails[$email_type] ) && wpinv_get_option( 'email_'. $email_type . '_active', 0 ) ? true : false;

    return apply_filters( 'wpinv_email_is_enabled', $enabled, $email_type );
}

function wpinv_email_get_recipient( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    switch ( $email_type ) {
        case 'new_invoice':
        case 'cancelled_invoice':
        case 'failed_invoice':
            $recipient  = wpinv_get_admin_email();
        break;
        default:
            $invoice    = !empty( $invoice ) && is_object( $invoice ) ? $invoice : ( $invoice_id > 0 ? wpinv_get_invoice( $invoice_id ) : NULL );
            $recipient  = !empty( $invoice ) ? $invoice->get_email() : '';
        break;
    }

    return apply_filters( 'wpinv_email_recipient', $recipient, $email_type, $invoice_id, $invoice );
}

/**
 * Returns invoice CC recipients
 */
function wpinv_email_get_cc_recipients( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    switch ( $email_type ) {
        case 'new_invoice':
        case 'cancelled_invoice':
        case 'failed_invoice':
            return array();
        break;
        default:
            $invoice    = !empty( $invoice ) && is_object( $invoice ) ? $invoice : ( $invoice_id > 0 ? wpinv_get_invoice( $invoice_id ) : NULL );
            $recipient  = empty( $invoice ) ? '' : get_post_meta( $invoice->ID, 'wpinv_email_cc', true );
            if ( empty( $recipient ) ) {
                return array();
            }
            return  array_filter( array_map( 'trim', explode( ',', $recipient ) ) );
        break;
    }

}

function wpinv_email_get_subject( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    $subject    = wpinv_get_option( 'email_' . $email_type . '_subject' );
    $subject    = __( $subject, 'invoicing' );

    $subject    = wpinv_email_format_text( $subject, $invoice );

    return apply_filters( 'wpinv_email_subject', $subject, $email_type, $invoice_id, $invoice );
}

function wpinv_email_get_heading( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    $email_heading = wpinv_get_option( 'email_' . $email_type . '_heading' );
    $email_heading = __( $email_heading, 'invoicing' );

    $email_heading = wpinv_email_format_text( $email_heading, $invoice );

    return apply_filters( 'wpinv_email_heading', $email_heading, $email_type, $invoice_id, $invoice );
}

function wpinv_email_get_content( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    $content    = wpinv_get_option( 'email_' . $email_type . '_body' );
    $content    = __( $content, 'invoicing' );

    $content    = wpinv_email_format_text( $content, $invoice );

    return apply_filters( 'wpinv_email_content', $content, $email_type, $invoice_id, $invoice );
}

function wpinv_email_get_headers( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    $from_name = wpinv_mail_get_from_address();
    $from_email = wpinv_mail_get_from_address();
    
    $invoice    = !empty( $invoice ) && is_object( $invoice ) ? $invoice : ( $invoice_id > 0 ? wpinv_get_invoice( $invoice_id ) : NULL );
    
    $headers    = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
    $headers    .= "Reply-To: ". $from_email . "\r\n";
    $headers    .= "Content-Type: " . wpinv_mail_get_content_type() . "\r\n";
    
    return apply_filters( 'wpinv_email_headers', $headers, $email_type, $invoice_id, $invoice );
}

function wpinv_email_get_attachments( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    $attachments = array();
    
    return apply_filters( 'wpinv_email_attachments', $attachments, $email_type, $invoice_id, $invoice );
}

/**
 * Searches for and replaces certain placeholders in an email.
 */
function wpinv_email_format_text( $content, $invoice ) {

    $replace_array = array(
        '{site_title}'      => wpinv_get_blogname(),
        '{date}'            => date_i18n( get_option( 'date_format' ), (int) current_time( 'timestamp' ) ),
    );

    $invoice = new WPInv_Invoice( $invoice );

    if ( $invoice->get_id() ) {

        $replace_array = array_merge(
            $replace_array, 
            array(
                '{name}'            => sanitize_text_field( $invoice->get_user_full_name() ),
                '{full_name}'       => sanitize_text_field( $invoice->get_user_full_name() ),
                '{first_name}'      => sanitize_text_field( $invoice->get_first_name() ),
                '{last_name}'       => sanitize_text_field( $invoice->get_last_name() ),
                '{email}'           => sanitize_email( $invoice->get_email() ),
                '{invoice_number}'  => sanitize_text_field( $invoice->get_number() ),
                '{invoice_total}'   => wpinv_price( wpinv_format_amount( $invoice->get_total( true ) ) ),
                '{invoice_link}'    => esc_url( $invoice->get_view_url() ),
                '{invoice_pay_link}'=> esc_url( $invoice->get_checkout_payment_url() ),
                '{invoice_date}'    => date( get_option( 'date_format' ), strtotime( $invoice->get_date_created(), current_time( 'timestamp' ) ) ),
                '{invoice_due_date}'=> date( get_option( 'date_format' ), strtotime( $invoice->get_due_date(), current_time( 'timestamp' ) ) ),
                '{invoice_quote}'   => sanitize_text_field( $invoice->get_type() ),
                '{invoice_label}'   => sanitize_text_field( ucfirst( $invoice->get_type() ) ),
                '{is_was}'          => strtotime( $invoice->get_due_date() ) < current_time( 'timestamp' ) ? __( 'was', 'invoicing' ) : __( 'is', 'invoicing' ),
            )
        );

    }

    // Let third party plugins filter the arra.
    $replace_array = apply_filters( 'wpinv_email_format_text', $replace_array, $content, $invoice );

    foreach ( $replace_array as $key => $value ) {
        $content = str_replace( $key, $value, $content );
    }

    return apply_filters( 'wpinv_email_content_replace', $content );
}


function wpinv_email_wrap_message( $message ) {
    // Buffer
    ob_start();

    do_action( 'wpinv_email_header' );

    echo wpautop( wptexturize( $message ) );

    do_action( 'wpinv_email_footer' );

    // Get contents
    $message = ob_get_clean();

    return $message;
}



function wpinv_send_customer_invoice( $data = array() ) {
    $invoice_id = !empty( $data['invoice_id'] ) ? absint( $data['invoice_id'] ) : NULL;

    if ( empty( $invoice_id ) ) {
        return;
    }

    if ( !wpinv_current_user_can_manage_invoicing() ) {
        wp_die( __( 'You do not have permission to send invoice notification', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }
    
    $sent = wpinv_user_invoice_notification( $invoice_id );

    if ( -1 === $sent ) {
        $status = 'email_disabled';
    } elseif ( $sent ) {
        $status = 'email_sent';
    } else {
        $status = 'email_fail';
    }

    $redirect = add_query_arg( array( 'wpinv-message' => $status, 'wpi_action' => false, 'invoice_id' => false ) );
    wp_redirect( $redirect );
    exit;
}
add_action( 'wpinv_send_invoice', 'wpinv_send_customer_invoice' );

function wpinv_send_overdue_reminder( $data = array() ) {
    $invoice_id = !empty( $data['invoice_id'] ) ? absint( $data['invoice_id'] ) : NULL;

    if ( empty( $invoice_id ) ) {
        return;
    }

    if ( !wpinv_current_user_can_manage_invoicing() ) {
        wp_die( __( 'You do not have permission to send reminder notification', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }

    $sent = wpinv_send_payment_reminder_notification( $invoice_id );
    
    $status = $sent ? 'email_sent' : 'email_fail';

    $redirect = add_query_arg( array( 'wpinv-message' => $status, 'wpi_action' => false, 'invoice_id' => false ) );
    wp_redirect( $redirect );
    exit;
}
add_action( 'wpinv_send_reminder', 'wpinv_send_overdue_reminder' );

/**
 * @deprecated
 */
function wpinv_send_customer_note_email() {}

function wpinv_add_notes_to_invoice_email( $invoice, $email_type, $sent_to_admin ) {
    if ( !empty( $invoice ) && $email_type == 'user_invoice' && $invoice_notes = wpinv_get_invoice_notes( $invoice->ID, true ) ) {
        $date_format = get_option( 'date_format' );
        $time_format = get_option( 'time_format' );
        ?>
        <div id="wpinv-email-notes">
            <h3 class="wpinv-notes-t"><?php echo apply_filters( 'wpinv_email_invoice_notes_title', __( 'Invoice Notes', 'invoicing' ) ); ?></h3>
            <ol class="wpinv-notes-lists">
        <?php
        foreach ( $invoice_notes as $note ) {
            $note_time = strtotime( $note->comment_date );
            ?>
            <li class="comment wpinv-note">
            <p class="wpinv-note-date meta"><?php printf( __( '%2$s at %3$s', 'invoicing' ), $note->comment_author, date_i18n( $date_format, $note_time ), date_i18n( $time_format, $note_time ), $note_time ); ?></p>
            <div class="wpinv-note-desc description"><?php echo wpautop( wptexturize( $note->comment_content ) ); ?></div>
            </li>
            <?php
        }
        ?>  </ol>
        </div>
        <?php
    }
}
add_action( 'wpinv_email_billing_details', 'wpinv_add_notes_to_invoice_email', 10, 3 );

/**
 * Sends renewal reminders.
 */
function wpinv_email_renewal_reminders() {
    global $wpdb;

    if ( ! wpinv_get_option( 'email_pre_payment_active' ) ) {
        return;
    }

    $reminder_days = wpinv_get_option( 'email_pre_payment_reminder_days' );

    if ( empty( $reminder_days ) ) {
        return;
    }

    // How many days before renewal should we send this email?
    $reminder_days = array_unique( array_map( 'absint', array_filter( wpinv_parse_list( $reminder_days ) ) ) );
    if ( empty( $reminder_days ) ) {
        return;
    }

    if ( 1 == count( $reminder_days ) ) {
        $days  = $reminder_days[0];
        $date  = date( 'Y-m-d', strtotime( "+$days days", current_time( 'timestamp' ) ) );
        $where = $wpdb->prepare( "DATE(expiration)=%s", $date );
    } else {
        $in    = array();

        foreach ( $reminder_days as $days ) {
            $date  = date( 'Y-m-d', strtotime( "+$days days", current_time( 'timestamp' ) ) );
            $in[]  = $wpdb->prepare( "%s", $date );
        }

        $in    = implode( ',', $in );
        $where = "DATE(expiration) IN ($in)";
    }

    // Fetch subscriptions to send out reminders for.
    $table = $wpdb->prefix . 'wpinv_subscriptions';

    // Fetch invoices.
	$subscriptions  = $wpdb->get_results(
		"SELECT parent_payment_id, product_id, expiration FROM $table
		WHERE
            $where
            AND status IN ( 'trialling', 'active' )");

    foreach ( $subscriptions as $subscription ) {

        // Skip packages.
        if ( get_post_meta( $subscription->product_id, '_wpinv_type', true ) == 'package' ) {
            continue;
        }

        wpinv_send_pre_payment_reminder_notification( $subscription->parent_payment_id, $subscription->expiration );
    }

}

function wpinv_email_payment_reminders() {
    global $wpi_auto_reminder, $wpdb;

    if ( ! wpinv_get_option( 'email_overdue_active' ) ) {
        return;
    }

    if ( $reminder_days = wpinv_get_option( 'email_due_reminder_days' ) ) {

        // Get a list of all reminder dates.
        $reminder_days  = is_array( $reminder_days ) ? array_values( $reminder_days ) : array();

        // Ensure we have integers.
        $reminder_days  = array_unique( array_map( 'absint', $reminder_days ) );

        // Abort if non is selected.
        if ( empty( $reminder_days ) ) {
            return;
        }

        // Fetch the max reminder day.
        $max_date = max( $reminder_days );

        // Todays date.
        $today = date( 'Y-m-d', current_time( 'timestamp' ) );

        if ( empty( $max_date ) ) {
            $where = $wpdb->prepare( "DATE(invoices.due_date)=%s", $today );
        } else if ( 1 == count( $reminder_days ) ) {
            $days  = $reminder_days[0];
            $date  = date( 'Y-m-d', strtotime( "-$days days", current_time( 'timestamp' ) ) );
            $where = $wpdb->prepare( "DATE(invoices.due_date)=%s", $date );
        } else {
            $in    = array();

            foreach ( $reminder_days as $days ) {
                $date  = date( 'Y-m-d', strtotime( "-$days days", current_time( 'timestamp' ) ) );
                $in[]  = $wpdb->prepare( "%s", $date );
            }

            $in    = implode( ',', $in );
            $where = "DATE(invoices.due_date) IN ($in)";
        }

        // Invoices table.
        $table = $wpdb->prefix . 'getpaid_invoices';

        // Fetch invoices.
		$invoices  = $wpdb->get_col(
			"SELECT posts.ID FROM $wpdb->posts as posts
			LEFT JOIN $table as invoices ON invoices.post_id = posts.ID
			WHERE
                $where 
				AND posts.post_type = 'wpi_invoice'
                AND posts.post_status = 'wpi-pending'");

        $wpi_auto_reminder  = true;

        foreach ( $invoices as $invoice ) {

            if ( 'payment_form' != get_post_meta( $invoice, 'wpinv_created_via', true ) ) {
                do_action( 'wpinv_send_payment_reminder_notification', $invoice );
            }

        }

        $wpi_auto_reminder  = false;
    }
}

/**
 * Sends an upcoming renewal notification.
 */
function wpinv_send_pre_payment_reminder_notification( $invoice_id, $renewal_date ) {

    $email_type = 'pre_payment';
    if ( ! wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }

    $invoice    = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    $recipient  = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( ! is_email( $recipient ) ) {
        return false;
    }

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );

    $renewal_date = date_i18n( 'Y-m-d', strtotime( $renewal_date ) );
    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'       => $invoice,
            'email_type'    => $email_type,
            'email_heading' => str_replace( '{subscription_renewal_date}', $renewal_date, $email_heading ),
            'sent_to_admin' => false,
            'plain_text'    => false,
            'message_body'  => str_replace( '{subscription_renewal_date}', $renewal_date, $message_body )
        ) );

    $content        = wpinv_email_format_text( $content, $invoice );

    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );

    if ( wpinv_mail_admin_bcc_active( $email_type ) ) {
        $recipient  = wpinv_get_admin_email();
        $subject    .= ' - ADMIN BCC COPY';
        wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );
    }

    return $sent;

}

function wpinv_send_payment_reminder_notification( $invoice_id ) {
    $email_type = 'overdue';
    if ( !wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }

    $invoice    = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    if ( !$invoice->needs_payment() ) {
        return false;
    }

    $recipient  = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( !is_email( $recipient ) ) {
        return false;
    }

    do_action( 'wpinv_pre_send_invoice_notification', $invoice, $email_type );

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );

    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'       => $invoice,
            'email_type'    => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => false,
            'plain_text'    => false,
            'message_body'  => $message_body
        ) );

    $content        = wpinv_email_format_text( $content, $invoice );

    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );
    if ( $sent ) {
        do_action( 'wpinv_payment_reminder_sent', $invoice_id, $invoice );
    }

    if ( wpinv_mail_admin_bcc_active( $email_type ) ) {
        $recipient  = wpinv_get_admin_email();
        $subject    .= ' - ADMIN BCC COPY';
        wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );
    }

    do_action( 'wpinv_post_send_invoice_notification', $invoice, $email_type );

    return $sent;
}
add_action( 'wpinv_send_payment_reminder_notification', 'wpinv_send_payment_reminder_notification', 10, 1 );

function wpinv_payment_reminder_sent( $invoice_id, $invoice ) {
    global $wpi_auto_reminder;

    $sent = get_post_meta( $invoice_id, '_wpinv_reminder_sent', true );

    if ( empty( $sent ) ) {
        $sent = array();
    }
    $sent[] = date_i18n( 'Y-m-d' );

    update_post_meta( $invoice_id, '_wpinv_reminder_sent', $sent );

    if ( $wpi_auto_reminder ) { // Auto reminder note.
        $note = __( 'Automated reminder sent to the user.', 'invoicing' );
        $invoice->add_note( $note, false, false, true );
    } else { // Menual reminder note.
        $note = __( 'Manual reminder sent to the user.', 'invoicing' );
        $invoice->add_note( $note );
    }
}
add_action( 'wpinv_payment_reminder_sent', 'wpinv_payment_reminder_sent', 10, 2 );
