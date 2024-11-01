<?php
if ($square_keys['token'] && $square_keys['appid'] && $square_keys['locid']) {
    do_action('give_before_cc_fields', $form_id);
    ?>
    <input type='hidden' value='<?=$form_id?>' name="giveformid" class='givesquareformid' />
    <fieldset class="give_square_container" id="gf_sqquare_container_<?php echo esc_attr($form_id); ?>">  
        <legend><?php esc_html_e('Credit Card', 'give-square'); ?></legend>
        <div class="messages"></div>
        <div id="payment-form" >
			<div id="card-container"></div>
		</div>             
        <input type="hidden" name="form-id" value="<?php echo esc_attr($form_id); ?>"/>    
		<input type="hidden" name="card-nonce" id="card-nonce"/>  
    </fieldset>
    <?php
    if ($square_keys['billing_details'] == 'enabled')
        give_default_cc_address_fields($form_id);
}else {
    ?>
    <div class="give_error "><?php esc_html_e('Please add Square keys.', 'give-square'); ?></div>
    <?php
}
?>