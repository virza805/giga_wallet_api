<?php
add_action('wp_head', 'giga_api_style');
function giga_api_style(){
    ?>
    <style>
        .error{
            color: red;
        }
        .loding{ 
            animation: rotetSpin 3s linear infinite; 
            display: inline-block; 
            font-size: 18px; 
            line-height: 0; 
        }
        @keyframes rotetSpin { 
            0%{ transform: rotate(360deg); } 
            100%{ transform: rotate(0deg); } 
        }
        .recharge_btn button {
            margin-top: 12px;
        }
        ul.giga-topup-btn-link {
            position: absolute;
            left: 0;
            top: 8px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        ul.giga-topup-btn-link + nav.woocommerce-MyAccount-navigation {
            margin-top: 44px;
        }
        .woo-wallet-add-amount input#woo_wallet_balance_to_add {
            border-radius: 50px;
            border: 1px solid #ddd;
            width: 100%;
            padding: .594rem 1rem;
            font-size: .875rem;
            font-weight: 400;
            line-height: 1.714;
            color: #333e48;
        }
        .giga-voucher-i {
            background: #226ed0;
            padding: 4px 9px;
            border-radius: 50px;
            line-height: 0;
            color: #ffffff;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: 0.3s;
        }
        .giga-voucher-i:hover {
            background: #000000;
            transition: 0.3s;
        }

    </style>
    <?php 
}
add_action('wp_footer', 'giga_api_script');
function giga_api_script(){
    ?>
    <script>
    // add your javaScript here
    // Form submit function onclick='formSubmit()'
    function formSubmit() {
        let giga_voucher_code = jQuery('input[name=giga_voucher_code]').val().trim();
        let giga_limit_time   = jQuery('input[name=giga_limit_time]').val().trim();

        // current timestamp in JavaScript
        let t = Math.floor(Date.now() / 1000);
        giga_limit_time = parseInt(giga_limit_time) || t;

        jQuery('.error').remove(); // Reset any previous error messages
         
        // validation ;
        let isValid = true;
         
        if(!giga_voucher_code){
          jQuery('input[name=giga_voucher_code]').css('border','1px solid red');
          jQuery('input[name=giga_voucher_code]').focus();
          jQuery('input[name=giga_voucher_code]').after(`<p class='error'>Voucher code is required</p>`);
          isValid = false;
        }else{
          if(giga_voucher_code.length!=13 && giga_voucher_code){
            jQuery('input[name=giga_voucher_code]').css('border','1px solid red');
            jQuery('input[name=giga_voucher_code]').focus();
            jQuery('input[name=giga_voucher_code]').after(`<p class='error'>Enter a valid voucher number</p>`);
            isValid = false;
          }else{
            jQuery('input[name=giga_voucher_code]').css('border','0px solid red');
            jQuery('input[name=giga_voucher_code]').after(` `);
          }
        }

        if (t>=giga_limit_time) {
            
        }else{

            // Convert timestamp to human-readable date format
            let timeNext = new Date(giga_limit_time * 1000);
            let showTime = timeNext.toISOString().replace('T', ' ').substring(0, 19);

            jQuery('#sms').html(`<b>You have 3 times wrong voucher code entries. So please try again after </b>${showTime} <span class='loding'>&#10044;</span>`);
            isValid = false;
        }
         
        if(isValid){
            jQuery('.recharge_btn').html(`<button type="button" >Wait...<span class='loding'>&#10044;</span></button>`);
            jQuery('#sms').html(`<b>Wait..</b> <span class='loding'>&#10044;</span>`);
            // ajax call
            jQuery.ajax({
                type: 'POST',
                dataType: 'json',
                url: '<?php echo admin_url('admin-ajax.php')?>',
                data: {
                    action: 'call_giga_api',
                    voucher: giga_voucher_code
                },
                success: function(response) {
                    jQuery('.recharge_btn').html(`<button type="button" onclick='formSubmit()'>Recharge Now</button>`);
                    if ( ! response ) return;
                    jQuery('#sms').html(` `);
                    if (response.status == 'ok') {
                        jQuery('#sms').html(`${response.message}`);
                        setTimeout(function() {
                            location.reload(); // This will reload the page
                        }, 3000);
                    } else { 
                        jQuery('input[name=giga_limit_time]').val(response.time);
                        jQuery('#sms').html(`<p class='error'>${response.message}</p>`);
                    }
                
                }
            });
        }
    }

    </script>
    <?php 
}

// Form data ajax process
function call_giga_api() {
    $code           = sanitize_text_field($_POST['voucher']);
    $user_id        = get_current_user_id();
    $countWrongEntry= get_user_meta($user_id, 'wrong_voucher_entry', true);
    $hasEligibleTime= get_user_meta($user_id, 'action_close_time', true);
    
    $current_time   = time(); // Get the current timestamp
    $hasEligibleTime= $hasEligibleTime?$hasEligibleTime:($current_time-1);

    if(3>=intval($countWrongEntry) && $current_time>=$hasEligibleTime){
        $result = res_giga_voucher_code($code);
        $data   = json_decode($result, true);

        // Check if 'data' is not null
        if (!is_null($data['data'])) {

            $vAmount ='';
            // Extract the voucher_price from the 'data' array
            if (isset($data['data']['voucher_price'])) {
                $vAmount = intval($data['data']['voucher_price']);
            }


            $chargeAmo      = get_option('gwapi_charge_amount');
            $chargeAmount   = $chargeAmo?intval($chargeAmo) : 10;
            $valuAmo        = 100 - $chargeAmount;
            $okChargeAmount = $chargeAmount/100;
            $okValuAmount   = $valuAmo/100;

            $charge_amount  = $vAmount * $okChargeAmount; // 0.1
            $voucherPrice   = $vAmount * $okValuAmount; // 0.9

            // Credit the wallet
            $transaction_id = woo_wallet()->wallet->credit(
				$user_id,
				$voucherPrice,
				__( 'Credit from purchase giga voucher #', 'woo-wallet' ) . $code,
				array(
					'for'      => 'credit_purchase',
					'currency' => get_woocommerce_currency(),
				)
			);

            // add code for update teraWellat amount.
            update_wallet_transaction_meta( $transaction_id, '_wc_wallet_purchase_gateway_charge', $charge_amount, $user_id );


            update_user_meta($user_id, 'wrong_voucher_entry', '');
            update_user_meta($user_id, 'action_close_time', '');

            $sms="تم تعبئة القسيمة بنجاح";
            // $sms=// Extract message from 'data'
            if (isset($data['data']['message'])) {
                $sms= $data['data']['message'];
            }

            echo json_encode(['status'=>'ok', 'message' => $sms ]);
            exit(); // wp_die();

        }else{
            $addCountTimeNomber = intval($countWrongEntry) + 1;

            if($addCountTimeNomber===3){
                // $currentTime =date('Y-m-d H:i:s');
                $timeNext = strtotime('+15 minutes', $current_time); // Add 15 minutes

                update_user_meta($user_id, 'action_close_time', $timeNext);
                update_user_meta($user_id, 'wrong_voucher_entry', '');
                    
                // $sms = $addCountTimeNomber.$data['message'];
                $sms = $addCountTimeNomber.'-time رقم قسيمة التعبئة غير صحيح يرجى التحقق';
                echo json_encode(['status'=>'not_ok', 'message' => $sms, 'time' => $timeNext]);
                exit(); // wp_die();

            }else{

                update_user_meta($user_id, 'wrong_voucher_entry', $addCountTimeNomber);
                // $sms = $addCountTimeNomber.$data['message'];
                $sms = $addCountTimeNomber.'-time رقم قسيمة التعبئة غير صحيح يرجى التحقق';
                echo json_encode(['status'=>'not_ok', 'message' => $sms, 'time' =>'']);
                exit(); // wp_die();
            }
        }

    }else{
        
        $afterTime= date('Y-m-d H:i:s', $hasEligibleTime);
        $sms = 'You have 3 times wrong voucher code entries. So please try again after '.$afterTime;
        echo json_encode(['status'=>'not_ok', 'message' => $sms ]);
        exit(); // wp_die();
    }
    
}
add_action('wp_ajax_call_giga_api', 'call_giga_api');
add_action('wp_ajax_nopriv_call_giga_api', 'call_giga_api');

function res_giga_voucher_code($code){
    // $code="1195102878275";
    $username = get_option('gwapi_username');
    $username = $username?$username :'test.mg';
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://client.giga.ly/api/single-topup-with-price',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "voucher": "'.$code.'",
            "username": "'.$username.'"
        }',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;

}

// add recharge with [giga_voucher] card Short code
add_shortcode('giga_voucher','giga_voucher_short_code_fun');
function giga_voucher_short_code_fun($jekono){ 
    $result = shortcode_atts(array( 
        'title' =>'',
    ),$jekono);
    extract($result);
    ob_start();
    $user_id        = get_current_user_id();
    $hasEligibleTime= get_user_meta($user_id, 'action_close_time', true);
    // $countWrongEntry= get_user_meta($user_id, 'wrong_voucher_entry', true);
    $charge_amount = get_option('gwapi_charge_amount');
    $smsNote = get_option('gwapi_charge_add_sms');
    $placeText = get_option('gwapi_placeholder_text');
    $smsNote = $smsNote ? $smsNote : $charge_amount.'% charge will be added.';
    $placeholderText = $placeText ? $placeText : 'Enter correct voucher code like 2953180523100';
    ?>

    <div class="giga_api">
        <label for="giga_voucher_code">Enter giga voucher code</label>
        <input type="text" name="giga_voucher_code" placeholder="<?php echo $placeholderText; ?>" id="giga_voucher_code">
        <input type="hidden" name="giga_limit_time" value="<?php echo $hasEligibleTime;?>">
        <small><?php echo $smsNote; ?></small>

        <div class="recharge_btn">
            <button type="button" onclick='formSubmit()' >Recharge Now</button>
        </div>
        <h3 id="sms"></h3>
    </div>
    <?php
    return ob_get_clean();
}

add_action('woo_wallet_menu_content', 'wallet_topup_from_giga_vaucher_code');
function wallet_topup_from_giga_vaucher_code(){
    ?>
    <br>
    <br>
    <br>
    <div class="woo-wallet-content-heading">
        <h3>Recharge with Giga </h3>
    </div>
    
    <?php 
    echo do_shortcode('[giga_voucher]');
}

add_action('woocommerce_before_account_navigation', 'wallet_topup_from_link_for_giga_vaucher_code');
function wallet_topup_from_link_for_giga_vaucher_code(){
    $pageUrl    = get_option('gwapi_url');
    $defaultUrl = get_site_url()."/giga-topup/";
    $pageUrl    = $pageUrl?$pageUrl:$defaultUrl;
    ?>
    <ul class="giga-topup-btn-link">
        <li class="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--giga-topup-btn">
            <a href="<?php echo $pageUrl; ?>" target="_blank" rel="noopener noreferrer" class="giga-topup-btn">Recharge with giga voucher card</a>
        </li>
    </ul>
    <?php 
    
}


add_filter('woo_wallet_locate_template', 'custom_wallet_template_path', 10, 3);
function custom_wallet_template_path($template, $template_name, $template_path) {
    // Check if the template is the mini-wallet template
    if ($template_name === 'mini-wallet.php') {
        // Set the custom path to your plugin's templates folder
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/' . $template_name;

        // If the file exists in your plugin, use it
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }

    // Otherwise, return the original template path
    return $template;
}


// add_action('wp_footer', 'ssget_fetch_price_script');
function ssget_fetch_price_script(){
    $code='4924887487144'; // 4971414079069
    $result = res_giga_voucher_code($code);
    $data   = json_decode($result, true);
    
    // Check if 'data' is not null
    if (!is_null($data['data'])) {
        // Extract the voucher_price from the 'data' array
        if (isset($data['data']['voucher_price'])) {
            $voucherPrice = intval($data['data']['voucher_price']);
            echo "Voucher price is: " . $voucherPrice . "\n";
        }
        
        // Extract message from 'data'
        if (isset($data['data']['message'])) {
            echo "Message: " . $data['data']['message'] . "\n";
        }
    } else {
        // Handle the case when 'data' is null, extract the message field
        echo "Error Message: " . $data['message'] . "\n";
    }
    
    
}


/*

4978236	384718	 
 4971414079069
 4924887487144	 
 4821132918601
================================
2953180523100
5645050953180
2911325423100
2248 7869 68552
2953942975706
2002273324987
2437323346515

*/
