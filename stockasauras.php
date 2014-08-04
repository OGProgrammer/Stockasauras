<?php
/**
 * Plugin Name: Stockasauras
 * Plugin URI: http://www.psycode.org/project/stockasauras/
 * Description: Quotes, Charts, and News on Stocks, Indices, and Currencies via Yahoo Finance API
 * Version: 0.1
 * Author: Joshua Ray Copeland
 * Author URI: http://www.psycode.org
 * License: GPLv2 or later
 *
 *
 * Copyright 2014 Joshua Ray Copeland <Josh@PsyCode.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
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

/// Security
//defined('ABSPATH') or die("No script kiddies please!");

// Adds Settings Link to Plugin Page
add_filter('plugin_action_links', 'stockasauras_settings_link', 10, 2);
function stockasauras_settings_link($links, $file)
{
    if ($file == 'stockasauras/stockasauras.php') {
        $links['settings'] = sprintf(
            '<a href="%s"> %s </a>',
            admin_url('options-general.php?page=stockasauras_plugin'),
            __('Settings', 'plugin_domain')
        );
    }
    return $links;
}

add_action('admin_menu', 'stockasauras_create_menu');

// Admin Menu Item under Settings
function stockasauras_create_menu()
{
    add_options_page(
        'Stockasauras Settings',
        'Stockasauras',
        'manage_options',
        'stockasauras_plugin',
        'stockasauras_settings_page'
    );

    add_action('admin_init', 'stockasauras_register_settings');
}

// Options
function stockasauras_register_settings()
{
    register_setting('stockasauras_settings', 'stockasauras_cache', 'boolval');
    register_setting('stockasauras_settings', 'stockasauras_cache_time', 'intval');
}

// The admin page
function stockasauras_settings_page()
{
    // Get an array of options from the database.
    $cache = get_option('stockasauras_enable_cache', 1);
    ?>
    <div class="wrap">
        <h2>Stockasauras Plugin Settings</h2>

        <form method="post" action="options.php">
            <?php settings_fields('stockasauras_settings'); ?>
            <?php //do_settings_sections( 'stockasauras_settings' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Enable Cache (Helps with page load time on high traffic sites)</th>
                    <td><input name="stockasauras_cache" value="1" <?php checked($cache, 1); ?>/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">How long did you want to cache it for?</th>
                    <td><input type="number" name="stockasauras_cache_time"
                               value="<?php echo get_option('stockasauras_cache_time', 15); ?>"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}

function SAS_csvFileToMultidimensionalArray($filepath, $indexes = null)
{
    $array = array();

    $file = fopen($filepath, 'r');

    while (!feof($file)) {
        $data = fgetcsv($file);
        if (is_array($data)) {
            if (!empty($indexes)) {
                $array[array_shift($indexes)] = $data;
            } else {
                $array[] = $data;
            }
        }
    }

    fclose($file);

    return $array;
}

function SAS_makeWebRequest($sourceURL, $destinationFile)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $sourceURL);
    $fp = fopen($destinationFile, 'w');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    return $destinationFile;
}

function SAS_getYahooQuoteData($stocks, $formats)
{
    $stockDataArray = array();
    $apiUri = sprintf(
        'http://download.finance.yahoo.com/d/quotes.csv?s=%s&f=%s',
        SAS_getCleanString($stocks),
        SAS_getCleanString($formats)
    );
    $cacheTime = get_option('stockasauras_cache_time', 15);
    if (!get_option('stockasauras_cache', 1)) {
        $cacheTime = 0;
    }
    $cacheFileName = sprintf(
        '%s/stockasauras/StockasaurasQuote_%s_%s.csv',
        WP_PLUGIN_DIR,
        md5(SAS_getSpaceInsteadOfComma($stocks)),
        md5($formats)
    );
    if (!file_exists($cacheFileName) || (time() - filemtime($cacheFileName) >= $cacheTime)) {
        $stockDataArray = SAS_makeWebRequest($apiUri, $cacheFileName);
    }
    $stockDataArray = SAS_csvFileToMultidimensionalArray($cacheFileName, explode(',', $stocks));
    return $stockDataArray;
}

function SAS_getYahooChart($stock, $time)
{
    $apiUri = sprintf(
        'http://chart.finance.yahoo.com/z?s=%s&t=%s&q=l&l=on',
        SAS_getCleanString($stock),
        SAS_getCleanString($time)
    );
    $cacheTime = get_option('stockasauras_cache_time', 15);
    if (!get_option('stockasauras_cache', 1)) {
        $cacheTime = 0;
    }
    $cacheFileName = sprintf('%s/stockasauras/StockasaurasChart_%s_%s.png', WP_PLUGIN_DIR, $stock, $time);
    if (!file_exists($cacheFileName) || (time() - filemtime($cacheFileName) >= $cacheTime)) {
        SAS_makeWebRequest($apiUri, $cacheFileName);
    }
    $imageUri = sprintf('/wp-content/plugins/stockasauras/StockasaurasChart_%s_%s.png', $stock, $time);
    return sprintf('<img src="%s" />', $imageUri);
}

function SAS_getOutputStringFromData($data, $labels = array(), $br = 0)
{
    $outputStr = '';
    $labelIndex = 0;
    foreach ($data as $row) {
        foreach ($row as $val) {
            if (!empty($labels[$labelIndex])) {
                $outputStr .= $labels[$labelIndex];
            }
            $outputStr .= $val;
            $labelIndex++;
        }
        if (count($labels) == count($row)) {
            $labelIndex = 0;
        }
        if (!empty($br)) {
            $outputStr .= '<br />';
        }
    }
    return $outputStr;
}

function SAS_getCleanString($str)
{
    return htmlentities(urlencode($str));
}

function SAS_getSpaceInsteadOfComma($str)
{
    return str_replace(',', '', $str);
}

function stockasauras_quote_func($atts, $content = null)
{

    $outputStr = $l = $s = $f = '';
    // Init vars via extract
    extract(
        shortcode_atts(
            array(
                's' => 'YHOO',
                'f' => 'l1',
                'l' => '',
                'b' => '0'
            ),
            $atts
        )
    );

    $labels = explode(',', $l);
    $formats = SAS_getSpaceInsteadOfComma($f);

    $stockDataArray = SAS_getYahooQuoteData($s, $formats);

    $outputStr = SAS_getOutputStringFromData($stockDataArray, $labels, $b);

    return $outputStr;
}

function stockasauras_fancy_quote_func($atts, $content = null)
{

    $outputStr = $s = '';
    extract(shortcode_atts(array('s' => 'YHOO'), $atts));

    $stockDataArray = SAS_getYahooQuoteData($s, 'p2');

    $outputStr = SAS_getOutputStringFromData($stockDataArray);

    $percentChange = floatval($outputStr);
    if ($percentChange > 0) {
        $outputStr = '
        <span class="stockasauras_fancy_quote_container" style="cursor: pointer;" onclick="window.location=\'/' . $s . '\';">
            <span class="stockasauras_fancy_quote up">
                <a class="stockasauras_symbol" href="/' . $s . '" onclick="event.preventDefault();">' . $s . '</a>
                            <span class="stockasauras_symbol">+' . $percentChange . '%</span>
                    </span>
                </span>';
    } elseif ($percentChange < 0) {
        $outputStr = '
        <span class="stockasauras_fancy_quote_container" style="cursor: pointer;" onclick="window.location=\'/' . $s . '\';">
            <span class="stockasauras_fancy_quote down">
                <a class="stockasauras_symbol" href="/' . $s . '" onclick="event.preventDefault();">' . $s . '</a>
                            <span class="stockasauras_symbol">' . $percentChange . '%</span>
                    </span>
                </span>';
    } else {
        $outputStr = '
        <span class="stockasauras_fancy_quote_container" style="cursor: pointer;" onclick="window.location=\'/' . $s . '\';">
            <span class="stockasauras_fancy_quote sideways">
                <a class="stockasauras_symbol" href="/' . $s . '" onclick="event.preventDefault();">' . $s . '</a>
                            <span class="stockasauras_symbol">' . $percentChange . '%</span>
                    </span>
                </span>';
    }
    return $outputStr;
}

function stockasauras_chart($atts, $content = null)
{

    $stockChart = $s = $t = '';
    // Init vars via extract
    extract(
        shortcode_atts(
            array(
                's' => 'YHOO',
                't' => '1d',
            ),
            $atts
        )
    );

    $stockChart = SAS_getYahooChart($s, $t);

    return $stockChart;
}

// Add CSS scripts to WP
add_action('wp_enqueue_scripts', 'add_stockasauras_styles');
function add_stockasauras_styles()
{
    // Respects SSL, Style.css is relative to the current file
    wp_register_style('prefix-style', plugins_url('styles.css', __FILE__));
    wp_enqueue_style('prefix-style');
}

// Attach short codes to WP
add_shortcode('stockasauras_quote', 'stockasauras_quote_func');
add_shortcode('stockasauras_fancy_quote', 'stockasauras_fancy_quote_func');
add_shortcode('stockasauras_chart', 'stockasauras_chart');

/// STOCKASAURAS PLUGIN FUNCTIONALITY

/// API Legend can be found here
/// https://code.google.com/p/yahoo-finance-managed/wiki/enumQuoteProperty

/// STOCKASAURAS LEGEND
//
//  s = Symbol of ticker (Required)
//
//  f = Format of data using the keys provided in the api
//
/// Get last price (quote)
/// [stockasauras_quote s="YHOO" f=""]
function SAS_getYahooAPIMapping()
{
    return array(
        'c8' => 'After Hours Change (Realtime)',
        'g3' => 'Annualized Gain',
        'a0' => 'Ask',
        'b2' => 'Ask (Realtime)',
        'a5' => 'Ask Size', // going to break shit since there is a comma in some calls
        'a2' => 'Average Daily Volume',
        'b0' => 'Bid',
        'b3' => 'Bid (Realtime)',
        'b6' => 'Bid Size', // going to break shit since there is a comma in some calls
        'b4' => 'Book Value Per Share',
        'c1' => 'Change',
        'c0' => 'Change Change In Percent',
        'm7' => 'Change From Fiftyday Moving Average',
        'm5' => 'Change From Two Hundredday Moving Average',
        'k4' => 'Change From Year High',
        'j5' => 'Change From Year Low',
        'p2' => 'Change In Percent',
        'k2' => 'Change In Percent (Realtime)',
        'c6' => 'Change (Realtime)',
        'c3' => 'Commission',
        'c4' => 'Currency',
        'h0' => 'Days High',
        'g0' => 'Days Low',
        'm0' => 'Days Range',
        'm2' => 'Days Range (Realtime)',
        'w1' => 'Days Value Change',
        'w4' => 'Days Value Change (Realtime)',
        'r1' => 'Dividend Pay Date',
        'd0' => 'Trailing Annual Dividend Yield',
        'y0' => 'Trailing Annual Dividend Yield In Percent',
        'e0' => 'Diluted E P S',
        'j4' => 'E B I T D A',
        'e7' => 'E P S Estimate Current Year',
        'e9' => 'E P S Estimate Next Quarter',
        'e8' => 'E P S Estimate Next Year',
        'q0' => 'Ex Dividend Date',
        'm3' => 'Fiftyday Moving Average',
        'f6' => 'Shares Float',
        'l2' => 'High Limit',
        'g4' => 'Holdings Gain',
        'g1' => 'Holdings Gain Percent',
        'g5' => 'Holdings Gain Percent (Realtime)',
        'g6' => 'Holdings Gain (Realtime)',
        'v1' => 'Holdings Value',
        'v7' => 'Holdings Value (Realtime)',
        'd1' => 'Last Trade Date',
        'l1' => 'Last Trade Price Only',
        'k1' => 'Last Trade (Realtime) With Time',
        'k3' => 'Last Trade Size',
        't1' => 'Last Trade Time',
        'l0' => 'Last Trade With Time',
        'l3' => 'Low Limit',
        'j1' => 'Market Capitalization',
        'j3' => 'Market Cap (Realtime)',
        'i0' => 'More Info',
        'n0' => 'Name',
        'n4' => 'Notes',
        't8' => 'Oneyr Target Price',
        'o0' => 'Open',
        'i5' => 'Order Book (Realtime)',
        'r5' => 'P E G Ratio',
        'r0' => 'P E Ratio',
        'r2' => 'P E Ratio (Realtime)',
        'm8' => 'Percent Change From Fiftyday Moving Average',
        'm6' => 'Percent Change From Two Hundredday Moving Average',
        'k5' => 'Change In Percent From Year High',
        'j6' => 'Percent Change From Year Low',
        'p0' => 'Previous Close',
        'p6' => 'Price Book',
        'r6' => 'Price E P S Estimate Current Year',
        'r7' => 'Price E P S Estimate Next Year',
        'p1' => 'Price Paid',
        'p5' => 'Price Sales',
        's6' => 'Revenue',
        's1' => 'Shares Owned',
        'j2' => 'Shares Outstanding',
        's7' => 'Short Ratio',
        'x0' => 'Stock Exchange',
        's0' => 'Symbol',
        't7' => 'Ticker Trend',
        'd2' => 'Trade Date',
        't6' => 'Trade Links',
        'f0' => 'Trade Links Additional',
        'm4' => 'Two Hundredday Moving Average',
        'v0' => 'Volume',
        'k0' => 'Year High',
        'j0' => 'Year Low',
        'w0' => 'Year Range'
    );
}

/*** BEGIN WIDGET CODE */

/**
 * Adds Foo_Widget widget.
 */
class Stockasauras_Widget extends WP_Widget
{

    /**
     * Register widget with WordPress.
     */
    function __construct()
    {
        parent::__construct(
            'stockasauras_widget', // Base ID
            __('Stockasauras Watchlist', 'text_domain'), // Name
            array(
                'description' => __(
                    'A widget to allow you to display a list of stocks & data from yahoo.',
                    'text_domain'
                ),
            ) // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance)
    {
        $title = apply_filters('widget_title', $instance['title']);
        $stocks = $instance['stocks'];
        $formats = $instance['formats'];
        $link = $instance['link'];

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        $stocks = __($stocks, 'text_domain');
        $stockData = SAS_getYahooQuoteData($stocks, $formats);

        if (!empty($stockData)) {
            echo '<table class="stockasauras_watchlist" border="0" cellpadding="0" cellspacing="0"><tbody>';
            foreach ($stockData as $symbol => $data) {
                echo sprintf('<tr><td><a href="%s%s">%s</a></td>', $link, $symbol, $symbol);
                foreach ($data as $dataPoint) {
                    if (strpos($dataPoint, '-') !== false) {
                        echo '<td class="down">' . $dataPoint . '</td>';
                    } elseif (strpos($dataPoint, '+') !== false) {
                        echo '<td class="up">' . $dataPoint . '</td>';
                    } else {
                        echo '<td>' . $dataPoint . '</td>';
                    }
                }
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo 'Something bad happened when trying to get stock data :(';
        }
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form($instance)
    {
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('Delayed Quotes', 'text_domain');
        }
        if (isset($instance['stocks'])) {
            $stocks = $instance['stocks'];
        } else {
            $stocks = __('YHOO', 'text_domain');
        }
        if (isset($instance['formats'])) {
            $formats = $instance['formats'];
        } else {
            $formats = __('l1c1p2', 'text_domain');
        }
        if (isset($instance['link'])) {
            $link = $instance['link'];
        } else {
            $link = __('http://finance.yahoo.com/q?s=', 'text_domain');
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" placeholder="My Watchlist"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('stocks'); ?>"><?php _e('Stocks (comma separated):'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('stocks'); ?>"
                   name="<?php echo $this->get_field_name('stocks'); ?>" type="text" placeholder="YHOO,GOOG"
                   value="<?php echo esc_attr($stocks); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('formats'); ?>"><?php _e('Yahoo Data Fields:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('formats'); ?>"
                   name="<?php echo $this->get_field_name('formats'); ?>" type="text" placeholder="l1c1p2"
                   value="<?php echo esc_attr($formats); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('link'); ?>"><?php _e(
                    'Symbol Link Uri (Set to "/" to link stock ticker back to your site or "#" to have it go nowhere):'
                ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('link'); ?>"
                   name="<?php echo $this->get_field_name('link'); ?>" type="text"
                   placeholder="http://finance.yahoo.com/q?s=" value="<?php echo esc_attr($link); ?>">
        </p>
    <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['stocks'] = (!empty($new_instance['stocks'])) ? strip_tags($new_instance['stocks']) : 'YHOO';
        $instance['formats'] = (!empty($new_instance['formats'])) ? strip_tags($new_instance['formats']) : 'l1c1p2';
        $instance['link'] = (!empty($new_instance['link'])) ? strip_tags(
            $new_instance['link']
        ) : 'http://finance.yahoo.com/q?s=';

        return $instance;
    }

} // class Stockasauras_Widget

// register Stockasauras_Widget widget
function register_foo_widget()
{
    register_widget('Stockasauras_Widget');
}

add_action('widgets_init', 'register_foo_widget');