<?php
/**
 * Displays a website input setting in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/edit/website.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Field Label', 'invoicing' ); ?></span>
        <input v-model='active_form_element.label' class='form-control' type="text"/>
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Placeholder text', 'invoicing' ); ?></span>
        <input v-model='active_form_element.placeholder' class='form-control' type="text"/>
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Help Text', 'invoicing' ); ?></span>
        <textarea placeholder='<?php esc_attr_e( 'Add some help text for this field', 'invoicing' ); ?>' v-model='active_form_element.description' class='form-control' rows='3'></textarea>
        <small class="form-text text-muted"><?php _e( 'HTML is allowed', 'invoicing' ); ?></small>
    </label>
</div>

<div class='form-group form-check'>
    <input :id="active_form_element.id + '_edit'" v-model='active_form_element.required' type='checkbox' class='form-check-input' />
    <label class='form-check-label' :for="active_form_element.id + '_edit'"><?php esc_html_e( 'Is this field required?', 'invoicing' ); ?></label>
</div>

<div class='form-group form-check'>
    <input :id="active_form_element.id + '_edit_default_current'" v-model='active_form_element.default_current_post' type='checkbox' class='form-check-input' />
    <label class='form-check-label' :for="active_form_element.id + '_edit_default_current'"><?php esc_html_e( 'Default to the current page\'s URL?', 'invoicing' ); ?></label>
</div>

<hr class='featurette-divider mt-4'>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Email Merge Tag', 'invoicing' ); ?></span>
        <input :value='active_form_element.label | formatMergeTag' class='form-control bg-white' type="text" readonly onclick="this.select()" />
        <span class="form-text text-muted"><?php esc_html_e( 'You can use this merge tag in notification emails', 'invoicing' ); ?></span>
    </label>
</div>

<hr class='featurette-divider mt-4'>
