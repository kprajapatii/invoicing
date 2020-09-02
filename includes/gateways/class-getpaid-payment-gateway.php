<?php
/**
 * Abstract payment gateway
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstaract Payment Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 */
abstract class GetPaid_Payment_Gateway {

	/**
	 * Set if the place checkout button should be renamed on selection.
	 *
	 * @var string
	 */
	public $checkout_button_text;

	/**
	 * Boolean whether the method is enabled.
	 *
	 * @var bool
	 */
	public $enabled = true;

	/**
	 * Payment method id.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Payment method order.
	 *
	 * @var int
	 */
	public $order = 10;

	/**
	 * Payment method title for the frontend.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Payment method description for the frontend.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Gateway title.
	 *
	 * @var string
	 */
	public $method_title = '';

	/**
	 * Gateway description.
	 *
	 * @var string
	 */
	public $method_description = '';

	/**
	 * Countries this gateway is allowed for.
	 *
	 * @var array
	 */
	public $countries;

	/**
	 * Currencies this gateway is allowed for.
	 *
	 * @var array
	 */
	public $currencies;

	/**
	 * Currencies this gateway is not allowed for.
	 *
	 * @var array
	 */
	public $exclude_currencies;

	/**
	 * Maximum transaction amount, zero does not define a maximum.
	 *
	 * @var int
	 */
	public $max_amount = 0;

	/**
	 * Optional URL to view a transaction.
	 *
	 * @var string
	 */
	public $view_transaction_url = '';

	/**
	 * Optional URL to view a subscription.
	 *
	 * @var string
	 */
	public $view_subscription_url = '';

	/**
	 * Optional label to show for "new payment method" in the payment
	 * method/token selection radio selection.
	 *
	 * @var string
	 */
	public $new_method_label = '';

	/**
	 * Contains a users saved tokens for this gateway.
	 *
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * An array of features that this gateway supports.
	 *
	 * @var array
	 */
	protected $supports = array();

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->enabled = wpinv_is_gateway_active( $this->id );

		// Register gateway.
		add_filter( 'wpinv_payment_gateways', array( $this, 'register_gateway' ) );

		// Enable Subscriptions.
		if ( $this->supports( 'subscription' ) ) {
			add_filter( "wpinv_{$this->id}_support_subscription", '__return_true' );
		}

		// Enable sandbox.
		if ( $this->supports( 'sandbox' ) ) {
			add_filter( "wpinv_{$this->id}_supports_sandbox", '__return_true' );
		}

		// Gateway settings.
		add_filter( "wpinv_gateway_settings_{$this->id}", array( $this, 'admin_settings' ) );
		

		// Gateway checkout fiellds.
		add_action( "wpinv_{$this->id}_cc_form", array( $this, 'payment_fields' ), 10, 2 );

		// Process payment.
		add_action( "getpaid_gateway_{$this->id}", array( $this, 'process_payment' ), 10, 3 );

		// Change the checkout button text.
		if ( ! empty( $this->checkout_button_text ) ) {
			add_filter( "getpaid_gateway_{$this->id}_checkout_button_label", array( $this, 'rename_checkout_button' ) );
		}

		// Check if a gateway is valid for a given currency.
		add_filter( "getpaid_gateway_{$this->id}_is_valid_for_currency", array( $this, 'validate_currency' ), 10, 2 );

		// Generate the transaction url.
		add_filter( "getpaid_gateway_{$this->id}_transaction_url", array( $this, 'filter_transaction_url' ), 10, 2 );

		// Generate the subscription url.
		add_filter( "getpaid_gateway_{$this->id}_subscription_url", array( $this, 'filter_subscription_url' ), 10, 2 );

		// Confirm payments.
		add_filter( "wpinv_payment_confirm_{$this->id}", array( $this, 'confirm_payment' ), 10, 2 );

		// Verify IPNs.
		add_action( "wpinv_verify_{$this->id}_ipn", array( $this, 'verify_ipn' ) );

	}

	/**
	 * Checks if this gateway is a given gateway.
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function is( $gateway ) {
		return $gateway == $this->id;
	}

	/**
	 * Returns a users saved tokens for this gateway.
	 *
	 * @since 1.0.19
	 * @return array
	 */
	public function get_tokens() {

		if ( count( $this->tokens ) > 0 ) {
			return $this->tokens;
		}

		if ( is_user_logged_in() && $this->supports( 'tokens' ) ) {
			$tokens = get_user_meta( get_current_user_id(), "getpaid_{$this->id}_tokens", true );

			if ( is_array( $tokens ) ) {
				$this->tokens = $tokens;
			}

		}

		return $this->tokens;
	}

	/**
	 * Return the title for admin screens.
	 *
	 * @return string
	 */
	public function get_method_title() {
		return apply_filters( 'getpaid_gateway_method_title', $this->method_title, $this );
	}

	/**
	 * Return the description for admin screens.
	 *
	 * @return string
	 */
	public function get_method_description() {
		return apply_filters( 'getpaid_gateway_method_description', $this->method_description, $this );
	}

	/**
	 * Get the success url.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
	 * @return string
	 */
	public function get_return_url( $invoice ) {

		// Payment success url
		$return_url = add_query_arg(
			array(
				'payment-confirm' => $this->id,
				'invoice_key'     => $invoice->get_key(),
				'utm_nooverride'  => 1
			),
			wpinv_get_success_page_uri()
		);

		return apply_filters( 'getpaid_gateway_success_url', $return_url, $invoice, $this );
	}

	/**
	 * Confirms payments when rendering the success page.
	 *
	 * @param string $content Success page content.
	 * @return string
	 */
	public function get_confirm_payment( $content ) {
		
		// Retrieve the invoice.
		$invoice_id = getpaid_get_current_invoice_id();
		$invoice    = wpinv_get_invoice( $invoice_id );
	
		// Ensure that it exists and that it is pending payment.
		if ( empty( $invoice_id ) || ! $invoice->needs_payment() ) {
			return $content;
		}
	
		// Can the user view this invoice??
		if ( ! wpinv_user_can_view_invoice( $invoice ) ) {
			return $content;
		}
	
		// Show payment processing indicator.
		return wpinv_get_template_html( 'wpinv-payment-processing.php', compact( 'invoice' ) );
	}

	/**
	 * Processes ipns and marks payments as complete.
	 *
	 * @return void
	 */
	public function verify_ipn() {}

	/**
	 * Get a link to the transaction on the 3rd party gateway site (if applicable).
	 *
	 * @param string $transaction_url transaction url.
	 * @param WPInv_Invoice $invoice Invoice object.
	 * @return string transaction URL, or empty string.
	 */
	public function filter_transaction_url( $transaction_url, $invoice ) {

		$transaction_id  = $invoice->get_transaction_id();

		if ( ! empty( $this->view_transaction_url ) && ! empty( $transaction_id ) ) {
			$transaction_url = sprintf( $this->view_transaction_url, $transaction_id );
			$replace         = $this->is_sandbox( $invoice ) ? 'sandbox' : '';
			$transaction_url = str_replace( '{sandbox}', $replace, $transaction_url );
		}

		return $transaction_url;
	}

	/**
	 * Get a link to the subscription on the 3rd party gateway site (if applicable).
	 *
	 * @param string $subscription_url transaction url.
	 * @param WPInv_Invoice $invoice Invoice object.
	 * @return string subscription URL, or empty string.
	 */
	public function filter_subscription_url( $subscription_url, $invoice ) {

		$profile_id      = $invoice->get_subscription_id();

		if ( ! empty( $this->view_subscription_url ) && ! empty( $profile_id ) ) {

			$subscription_url = sprintf( $this->view_subscription_url, $profile_id );
			$replace          = $this->is_sandbox( $invoice ) ? 'sandbox' : '';
			$subscription_url = str_replace( '{sandbox}', $replace, $subscription_url );

		}

		return $subscription_url;
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		return ! empty( $this->enabled );
	}

	/**
	 * Return the gateway's title.
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'getpaid_gateway_title', $this->title, $this );
	}

	/**
	 * Return the gateway's description.
	 *
	 * @return string
	 */
	public function get_description() {
		return apply_filters( 'getpaid_gateway_description', $this->description, $this );
	}

	/**
	 * Process Payment.
	 *
	 *
	 * @param WPInv_Invoice $invoice Invoice.
	 * @param array $submission_data Posted checkout fields.
	 * @param GetPaid_Payment_Form_Submission $submission Checkout submission.
	 * @return void
	 */
	public function process_payment( $invoice, $submission_data, $submission ) {
		// Process the payment then either redirect to the success page or the gateway.
	}

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @param WPInv_Invoice $invoice Invoice.
	 * @param  float  $amount Refund amount.
	 * @param  string $reason Refund reason.
	 * @return WP_Error|bool True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $invoice, $amount = null, $reason = '' ) {
		return false;
	}

	/**
	 * Displays the payment fields, credit cards etc.
	 * 
	 * @param int $invoice_id 0 or invoice id.
	 * @param GetPaid_Payment_Form $form Current payment form.
	 */
	public function payment_fields( $invoice_id, $form ) {}

	/**
	 * Filters the gateway settings.
	 * 
	 * @param array $admin_settings
	 */
	public function admin_settings( $admin_settings ) {
		return $admin_settings;
	}

	/**
	 * Retrieves the value of a gateway setting.
	 * 
	 * @param string $option
	 */
	public function get_option( $option, $default = false ) {
		return wpinv_get_option( $this->id . '_' . $option, $default );
	}

	/**
	 * Check if a gateway supports a given feature.
	 *
	 * Gateways should override this to declare support (or lack of support) for a feature.
	 * For backward compatibility, gateways support 'products' by default, but nothing else.
	 *
	 * @param string $feature string The name of a feature to test support for.
	 * @return bool True if the gateway supports the feature, false otherwise.
	 * @since 1.0.19
	 */
	public function supports( $feature ) {
		return apply_filters( 'getpaid_payment_gateway_supports', in_array( $feature, $this->supports ), $feature, $this );
	}

	/**
	 * Grab and display our saved payment methods.
	 *
	 * @since 1.0.19
	 */
	public function saved_payment_methods() {
		$html = '<ul class="getpaid-saved-payment-methods" data-count="' . esc_attr( count( $this->get_tokens() ) ) . '">';

		foreach ( $this->get_tokens() as $token ) {
			$html .= $this->get_saved_payment_method_option_html( $token );
		}

		$html .= $this->get_new_payment_method_option_html();
		$html .= '</ul>';

		echo apply_filters( 'getpaid_payment_gateway_form_saved_payment_methods_html', $html, $this );
	}

	/**
	 * Gets saved payment method HTML from a token.
	 *
	 * @since 1.0.19
	 * @param  array $token Payment Token.
	 * @return string Generated payment method HTML
	 */
	public function get_saved_payment_method_option_html( $token ) {

		return sprintf(
			'<li class="getpaid-payment-method form-group">
				<label>
					<input name="getpaid-%1$s-payment-method" type="radio" value="%2$s" style="width:auto;" class="getpaid-saved-payment-method-token-input" %4$s />
					<span>%3$s</span>
				</label>
			</li>',
			esc_attr( $this->id ),
			esc_attr( $token['id'] ),
			esc_html( $token['name'] ),
			checked( empty( $token['default'] ), false, false )
		);

	}

	/**
	 * Displays a radio button for entering a new payment method (new CC details) instead of using a saved method.
	 *
	 * @since 1.0.19
	 */
	public function get_new_payment_method_option_html() {

		$label = apply_filters( 'getpaid_new_payment_method_label', $this->new_method_label ? $this->new_method_label : __( 'Use a new payment method', 'invoicing' ), $this );

		return sprintf(
			'<li class="getpaid-new-payment-method">
				<label>
					<input name="getpaid-%1$s-payment-method" type="radio" value="new" style="width:auto;" />
					<span>%2$s</span>
				</label>
			</li>',
			esc_attr( $this->id ),
			esc_html( $label )
		);

	}

	/**
	 * Outputs a checkbox for saving a new payment method to the database.
	 *
	 * @since 1.0.19
	 */
	public function save_payment_method_checkbox() {
	
		return sprintf(
			'<p class="form-group getpaid-save-payment-method">
				<label>
					<input name="getpaid-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;" />
					<span>%2$s</span>
				</label>
			</p>',
			esc_attr( $this->id ),
			esc_html__( 'Save payment method', 'invoicing' )
		);

	}

	/**
	 * Registers the gateway.
	 *
	 * @return array
	 */
	public function register_gateway( $gateways ) {

		$gateways[ $this->id ] = array(

			'admin_label'    => $this->method_title,
            'checkout_label' => $this->title,
			'ordering'       => $this->order,

		);

		return $gateways;

	}

	/**
	 * Checks whether or not this is a sandbox request.
	 *
	 * @param  WPInv_Invoice|null $invoice Invoice object or null.
	 * @return bool
	 */
	public function is_sandbox( $invoice = null ) {

		if ( ! empty( $invoice ) && ! $invoice->needs_payment() ) {
			return $invoice->get_mode() == 'test';
		}

		return wpinv_is_test_mode( $this->id );

	}

	/**
	 * Renames the checkout button
	 *
	 * @return string
	 */
	public function rename_checkout_button() {
		return $this->checkout_button_text;
	}

	/**
	 * Validate gateway currency
	 *
	 * @return bool
	 */
	public function validate_currency( $validation, $currency ) {

		// Required currencies.
		if ( ! empty( $this->currencies ) && ! in_array( $currency, $this->currencies ) ) {
			return false;
		}

		// Excluded currencies.
		if ( ! empty( $this->exclude_currencies ) && in_array( $currency, $this->exclude_currencies ) ) {
			return false;
		}

		return $validation;
	}

}
