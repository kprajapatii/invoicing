<?php

/**
 * Invoice Address
 *
 * Display the invoice address meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Invoice_Address Class.
 */
class GetPaid_Meta_Box_Invoice_Address {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

        // Prepare the invoice.
        $invoice = new WPInv_Invoice( $post );

        wp_nonce_field( 'getpaid_meta_nonce', 'getpaid_meta_nonce' );

        ?>

        <style>
            #wpinv-address label {
                margin-bottom: 3px;
                font-weight: 600;
            }
        </style>
            <div class="bsui" style="margin-top: 1.5rem; max-width: 820px;">
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <div id="getpaid-invoice-user-id-wrapper" class="form-group">
                                <div>
                                    <label for="post_author_override"><?php _e( 'Customer', 'invoicing' );?></label>
                                </div>
                                <?php 
                                    wpinv_dropdown_users(
                                        array(
                                            'name'             => 'post_author_override',
                                            'selected'         => $invoice->get_id() ? $invoice->get_user_id( 'edit' ) : get_current_user_id(),
                                            'include_selected' => true,
                                            'show'             => 'display_name_with_login',
                                            'orderby'          => 'user_email',
                                            'class'            => 'wpi_select2 form-control'
                                        )
                                    );
                                ?>
                            </div>

                            <div id="getpaid-invoice-email-wrapper" class="d-none">
                                <input type="hidden" id="getpaid-invoice-create-new-user" name="wpinv_new_user" value="" />
                                <?php
                                    echo aui()->input(
                                        array(
                                            'type'        => 'text',
                                            'id'          => 'getpaid-invoice-new-user-email',
                                            'name'        => 'wpinv_email',
                                            'label'       => __( 'Email', 'invoicing' ) . '<span class="required">*</span>',
                                            'label_type'  => 'vertical',
                                            'placeholder' => 'john@doe.com',
                                            'class'       => 'form-control-sm',
                                        )
                                    );
                                ?>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 form-group mt-sm-4">
                            <?php if ( ! $invoice->is_paid() && ! $invoice->is_refunded() ) : ?>
                                <a id="getpaid-invoice-fill-user-details" class="button button-small button-secondary" href="javascript:void(0)">
                                    <i aria-hidden="true" class="fa fa-refresh"></i>
                                    <?php _e( 'Fill User Details', 'invoicing' );?>
                                </a>
                                <a id="getpaid-invoice-create-new-user-button" class="button button-small button-secondary" href="javascript:void(0)">
                                    <i aria-hidden="true" class="fa fa-plus"></i>
                                    <?php _e( 'Add New User', 'invoicing' );?>
                                </a>
                                <a id="getpaid-invoice-cancel-create-new-user" class="button button-small button-secondary d-none" href="javascript:void(0)">
                                    <i aria-hidden="true" class="fa fa-close"></i>
                                    <?php _e( 'Cancel', 'invoicing' );?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_first_name',
                                        'name'        => 'wpinv_first_name',
                                        'label'       => __( 'First Name', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => 'Jane',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_first_name( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_last_name',
                                        'name'        => 'wpinv_last_name',
                                        'label'       => __( 'Last Name', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => 'Doe',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_last_name( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_company',
                                        'name'        => 'wpinv_company',
                                        'label'       => __( 'Company', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => 'Acme Corporation',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_company( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_vat_number',
                                        'name'        => 'wpinv_vat_number',
                                        'label'       => __( 'Vat Number', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => '1234567890',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_vat_number( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_address',
                                        'name'        => 'wpinv_address',
                                        'label'       => __( 'Address', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => 'Blekersdijk 295',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_address( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_city',
                                        'name'        => 'wpinv_city',
                                        'label'       => __( 'City', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => 'Dolembreux',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_vat_number( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->select(
                                    array(
                                        'id'          => 'wpinv_country',
                                        'name'        => 'wpinv_country',
                                        'label'       => __( 'Country', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => __( 'Choose a country', 'invoicing' ),
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_country( 'edit' ),
                                        'options'     => wpinv_get_country_list(),
                                        'data-allow-clear' => 'false',
                                        'select2'          => true,
                                    )
                                );
                            ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <?php

                                $states = wpinv_get_country_states( $invoice->get_country( 'edit' ) );

                                if ( empty( $states ) ) {

                                    echo aui()->input(
                                        array(
                                            'type'        => 'text',
                                            'id'          => 'wpinv_state',
                                            'name'        => 'wpinv_state',
                                            'label'       => __( 'State', 'invoicing' ),
                                            'label_type'  => 'vertical',
                                            'placeholder' => 'Liège',
                                            'class'       => 'form-control-sm',
                                            'value'       => $invoice->get_state( 'edit' ),
                                        )
                                    );

                                } else {

                                    echo aui()->select(
                                        array(
                                            'id'          => 'wpinv_state',
                                            'name'        => 'wpinv_state',
                                            'label'       => __( 'State', 'invoicing' ),
                                            'label_type'  => 'vertical',
                                            'placeholder' => __( 'Select a state', 'invoicing' ),
                                            'class'       => 'form-control-sm',
                                            'value'       => $invoice->get_state( 'edit' ),
                                            'options'     => $states,
                                            'data-allow-clear' => 'false',
                                            'select2'          => true,
                                        )
                                    );

                                }
                                
                            ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_zip',
                                        'name'        => 'wpinv_zip',
                                        'label'       => __( 'Zip / Postal Code', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => '4140',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_zip( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <?php
                                echo aui()->input(
                                    array(
                                        'type'        => 'text',
                                        'id'          => 'wpinv_phone',
                                        'name'        => 'wpinv_phone',
                                        'label'       => __( 'Phone', 'invoicing' ),
                                        'label_type'  => 'vertical',
                                        'placeholder' => '0493 18 45822',
                                        'class'       => 'form-control-sm',
                                        'value'       => $invoice->get_phone( 'edit' ),
                                    )
                                );
                            ?>
                        </div>
                    </div>

            </div>
        <?php
    }

    /**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 */
	public static function save( $post_id ) {

        // Prepare the invoice.
        $invoice = new WPInv_Invoice( $post_id );

        // Load new data.
        $invoice->set_props(
			array(
                'template'             => isset( $_POST['wpinv_template'] ) ? wpinv_clean( $_POST['wpinv_template'] ) : null,
                'email_cc'             => isset( $_POST['wpinv_cc'] ) ? wpinv_clean( $_POST['wpinv_cc'] ) : null,
                'disable_taxes'        => isset( $_POST['disable_taxes'] ),
                'currency'             => isset( $_POST['wpinv_currency'] ) ? wpinv_clean( $_POST['wpinv_currency'] ) : null,
                'gateway'              => isset( $_POST['wpinv_gateway'] ) ? wpinv_clean( $_POST['wpinv_gateway'] ) : null,
                'address'              => isset( $_POST['wpinv_address'] ) ? wpinv_clean( $_POST['wpinv_address'] ) : null,
                'vat_number'           => isset( $_POST['wpinv_vat_number'] ) ? wpinv_clean( $_POST['wpinv_vat_number'] ) : null,
                'company'              => isset( $_POST['wpinv_company'] ) ? wpinv_clean( $_POST['wpinv_company'] ) : null,
                'zip'                  => isset( $_POST['wpinv_zip'] ) ? wpinv_clean( $_POST['wpinv_zip'] ) : null,
                'state'                => isset( $_POST['wpinv_state'] ) ? wpinv_clean( $_POST['wpinv_state'] ) : null,
                'city'                 => isset( $_POST['wpinv_city'] ) ? wpinv_clean( $_POST['wpinv_city'] ) : null,
                'country'              => isset( $_POST['wpinv_country'] ) ? wpinv_clean( $_POST['wpinv_country'] ) : null,
                'phone'                => isset( $_POST['wpinv_phone'] ) ? wpinv_clean( $_POST['wpinv_phone'] ) : null,
                'first_name'           => isset( $_POST['wpinv_first_name'] ) ? wpinv_clean( $_POST['wpinv_first_name'] ) : null,
                'last_name'            => isset( $_POST['wpinv_last_name'] ) ? wpinv_clean( $_POST['wpinv_last_name'] ) : null,
                'author'               => isset( $_POST['post_author_override'] ) ? wpinv_clean( $_POST['post_author_override'] ) : null,
                'date_created'         => isset( $_POST['date_created'] ) ? wpinv_clean( $_POST['date_created'] ) : null,
                'due_date'             => isset( $_POST['wpinv_due_date'] ) ? wpinv_clean( $_POST['wpinv_due_date'] ) : null,
                'number'               => isset( $_POST['wpinv_number'] ) ? wpinv_clean( $_POST['wpinv_number'] ) : null,
                'status'               => isset( $_POST['wpinv_status'] ) ? wpinv_clean( $_POST['wpinv_status'] ) : null,
			)
        );

        // Recalculate totals.
        $invoice->recalculate_total();

        // If we're creating a new user...
        if ( ! empty( $_POST['wpinv_new_user'] ) && is_email( $_POST['wpinv_email'] ) ) {

            // Attempt to create the user.
            $user = wpinv_create_user( sanitize_email( $_POST['wpinv_email'] ) );


            // If successful, update the invoice author.
            if ( is_numeric( $user ) ) {
                $invoice->set_author( $user );
            } else {
                wpinv_error_log( $user->get_error_message(), __( 'Invoice add new user', 'invoicing' ), __FILE__, __LINE__ );
            }
        }

        // Save the invoice.
        $invoice->save();

        // (Maybe) send new user notification.
        if ( ! empty( $user ) && is_numeric( $user ) && apply_filters( 'getpaid_send_new_user_notification', true ) ) {
            wp_send_new_user_notifications( $user, 'user' );
        }

        // Fires after an invoice is saved.
		do_action( 'wpinv_invoice_metabox_saved', $invoice );
	}
}
