<?php
/**
 * Plugin Name: Stockasauras
 * Plugin URI: http://www.psycode.org/project/stockasauras/
 * Description: Quotes, Charts, and News on Stocks, Indices, and Currencies via Yahoo Finance API
 * Version: 0.1
 * Author: Joshua Ray Copeland
 * Author URI: http://www.psycode.org
 * License: GPLv3 or later
 *
 *
 * Copyright 2014 Joshua Ray Copeland <Josh@PsyCode.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

///////// NOTES //////////////////////////
/// @TODO build links to DD sites externally
///
/// YAHOO FINANCE CSV QUOTE API
/// https://code.google.com/p/yahoo-finance-managed/wiki/csvQuotesDownload
//////////////////////////////////////////


// Security
//defined('ABSPATH') or die("No script kiddies please!");

/// WORDPRESS ADMIN STUFF

// Adds Settings Link to Plugin Page

add_filter('plugin_action_links', 'stockasauras_settings_link', 10, 2);
function stockasauras_settings_link($links, $file) {
    if ($file == 'stockasauras/stockasauras.php') {
        $links['settings'] = sprintf( '<a href="%s"> %s </a>', admin_url( 'options-general.php?page=stockasauras_plugin' ), __( 'Settings', 'plugin_domain' ) );
    }
    return $links;
}

add_action('admin_menu', 'stockasauras_create_menu');

function stockasauras_create_menu() {
    add_options_page('Stockasauras Settings',
        'Stockasauras',
        'manage_options',
        'stockasauras_plugin',
        'stockasauras_settings_page'
    );

    add_action('admin_init', 'stockasauras_register_settings');
}


function stockasauras_register_settings() {
    register_setting( 'stockasauras_settings', 'stockasauras_comprehensive_view' );
}


function stockasauras_settings_page () {
?>
    <div class="wrap">
        <h2>Stockasauras Plugin Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields( 'stockasauras_settings' ); ?>
            <?php do_settings_sections( 'stockasauras_settings' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Comprehensive View Layout</th>
                    <td><input type="text" name="stockasauras_comprehensive_view" value="<?php echo get_option('stockasauras_comprehensive_view'); ?>"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php }

/// STOCKASAURAS PLUGIN FUNCTIONALITY

/// LEGEND
//
//  s = Symbol of ticker
//
/// Get last price (quote)
/// [stockasauras_quote s="YHOO"]

function stockasauras_quote_func( $atts, $content = null ) {
    $s = '';
    $data = array();
    extract(
        shortcode_atts(
            array(
                's' => ''
            ),
            $atts
        )
    );

    // Init vars
    $quotesBaseUri = "http://download.finance.yahoo.com/d/quotes.csv?s=";

    // Get last price
    $csvDataColumn = 'l1';

    // Build REST Uri
    $quotesQueryUri = $quotesBaseUri . urlencode($s) . '&f=' . $csvDataColumn;

    // Make the API call
    $curl = curl_init($quotesQueryUri);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);

    if (isset($response) && !is_null($response)) {
        $data = str_getcsv($response);
    }
    return $data[0];
}

// Attach short codes to WP
add_shortcode('stockasauras_quote', 'stockasauras_quote_func');

//// Name, Stock Symbol, Last Price, Open, Close, AfterHoursChange(RT), Ask, Ask(RT), Ask Size, Bid, Bid(RT), Bid Size
//$csvDataColumns = 'n0'.'s'.'l1'.'o0'.'p0'.'c8'.'a0'.'b2'.'a5'.'b0'.'b3'.'b6'.
//    // Change, Change(RT), Change %, Change % (RT), Day High, Day Low, Float, Mkt Cap, More Info, Notes, Target Price
//    'c1' . 'c6'      . 'p2'    . 'k2'         . 'h0'    . 'g0'   . 'f6' . 'j1'   . 'i0'     . 'n4' . 't8' .
//    // PE Ratio, Shares Owned, Shared Outstanding, Short Ratio, Exchange, Volume, Avg Vol
//    'r0'   . 's1'        . 'j2'              . 's7'       . 'x0'    . 'v0'  . 'a2'