<?php
/**
 * Plugin Name: Giga & Wallet wc API
 * Requires Plugins: woo-wallet
 * Description: Giga & Wallet wc API plugin is addon "Wallet for WooCommerce" plugin. #Designed, Developed, Maintained & Supported by 1mdalamin1
 * Plugin URI: https://www.fiverr.com/tanvirmdalamin/create-custom-code-for-plugins-and-api-integration-based-on-demand
 * Version: 1.0.2
 * Author: 1mdalamin1
 * Author URI: https://www.upwork.com/services/product/development-it-custom-code-for-plugins-and-api-integration-based-on-demand-1779447711902695424
 * License: GPLv2
 * textdomain: gwapi
 */
if ( ! defined('ABSPATH')) {exit;}
include_once 'giga_api.php';

add_action('admin_menu', 'gwapi_submenu_under_wallet');
function gwapi_submenu_under_wallet() {
    add_submenu_page(
        'woocommerce',
        __('Giga api Setting', 'gwapi'),// Page title
        'Giga api Setting',             // Menu title
        'manage_options',               // Capability
        'woo-wallet-giga-api',          // Menu slug (unique identifier)
        'gwapi_giga_api_page'           // Function to display page content
    );
}

function gwapi_giga_api_page() {
    ?>
    <style>
        .giga_btn {
            background: #2271b1;
            color: #fff;
            padding: 6px 20px;
            margin-top: 13px;
            cursor: pointer;
            border: 0;
            line-height: unset;
            border-radius: 4px;
            transition: 0.3s;
        }
        .giga_btn:hover {
            background: #054f8c;
            transition: 0.3s;
        }

    </style>
    <div class="wrap">
        <h1><?php esc_html_e( 'Giga API Settings', 'gwapi' ); ?></h1>
        <form action="options.php" method="post">
          <?php wp_nonce_field('update-options'); ?>
  
            <table>
              <!-- <tr>
                <td>
                    <label for="st1" name="ests_text_color"><?php // echo esc_attr( 'Text Color' ); ?></label>
                    <small><?php // echo esc_html( 'Add your Text Color' ); ?></small>
                </td>
                <td>
                    <input id="st1" type="color" name="ests_text_color" value="<?php // echo get_option('ests_text_color') ?>">
                </td>
              </tr> -->
              <tr>
                <td>
                    <label for="gwapi_username" name="gwapi_username"><?php echo esc_attr( __('Giga api username', 'gwapi') ); ?></label>
                </td>
                <td>
                    <input id="gwapi_username" type="text" name="gwapi_username" value="<?php echo get_option('gwapi_username'); ?>" placeholder="test.mg">
                </td>
              </tr>
              <tr>
                <td>
                    <label for="gwapi_charge_amount" name="gwapi_charge_amount"><?php echo esc_attr( __('Charge amount %', 'gwapi') ); ?></label>
                </td>
                <td>
                    <input id="gwapi_charge_amount" type="number" name="gwapi_charge_amount" value="<?php echo get_option('gwapi_charge_amount'); ?>" placeholder="10" min="1" max="100">
                </td>
              </tr>
              <tr>
                <td>
                    <label for="gwapi_url" name="gwapi_url"><?php echo esc_attr( __('Create a page use this code [giga_voucher]', 'gwapi') ); ?></label>
                    <br>
                    <small><?php echo "Default url is ". get_site_url()."/giga-topup/"; ?></small>
                </td>
                <td>
                    <input id="gwapi_url" type="text" name="gwapi_url" value="<?php echo get_option('gwapi_url'); ?>" placeholder="Enter page url">
                </td>
              </tr>
              <tr>
                <td>
                    <label for="gwapi_charge_add_sms" name="gwapi_charge_add_sms"><?php echo esc_attr( __('Shwo text top btn', 'gwapi') ); ?></label>
                </td>
                <td>
                    <input id="gwapi_charge_add_sms" type="text" name="gwapi_charge_add_sms" value="<?php echo get_option('gwapi_charge_add_sms'); ?>" placeholder="<?php echo get_option('gwapi_charge_amount'); ?>% charge will be added.">
                </td>
              </tr>
              <tr>
                <td>
                    <label for="gwapi_placeholder_text" name="gwapi_placeholder_text"><?php echo esc_attr( __('Placeholder text', 'gwapi') ); ?></label>
                </td>
                <td>
                    <input id="gwapi_placeholder_text" type="text" name="gwapi_placeholder_text" value="<?php echo get_option('gwapi_placeholder_text'); ?>" placeholder="Enter correct voucher code like 2953180523100">
                </td>
              </tr>

            </table>
            
          <!-- Round Corner -->
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="page_options"  value="
          gwapi_username,
          gwapi_charge_amount,
          gwapi_charge_add_sms,
          gwapi_placeholder_text,
          gwapi_url">
          <input type="submit" name="submit" value="<?php _e('Save', 'gwapi') ?>" class="giga_btn">
        </form>
    </div>
    <?php
}


