=== Stockasauras ===
Contributors: psycodedotorg
Tags: stocks, finance, quotes, trading, charts, equities, yahoo, market, economy, price, trade, watchlist
Donate link: http://www.psycode.org/donate/
Requires at least: 3.0.1
Tested up to: 3.9.1
License: GPLv2 or later
License URI: http://www.gnu.org/copyleft/gpl.html
Stable tag: 0.1

Stockasauras gives you 3 short codes and 1 watchlist widget utilizing the Yahoo Finance API to get quotes/charts for Stocks, Indices, and Currencies.

== Description ==

Stockasauras taps into the very powerful Yahoo Finance REST API and adds 3 easy to use short codes and 1 widget. Get 87+ data points on your favorite stocks including after hours, real-time, ask, bid, price, volume, market cap, and much more. Use our chart short code to pull yahoo charts which allows you to specify time span for your chart. Also use our slick fancy quote to display a red(down)/green(up)/grey(no movement) pill box quote for a stock of your choice which is great for financial blogs that which to display inline stock quotes within article. Another great feature is the watchlist widget that allows you to put a comma separated list of your favorite stock symbols and displays them in a nice green/red/grey fashion with last days change in price and percent. The symbols are linked out to yahoo finance by default but feel free to change to the site of your choosing (including your own if you wish).

= Stock Quotes =

The short code "stockasauras_quote" allows you to get data on one or more stocks. For example, to get the last price traded for Yahoo, find the symbol and place it in the s parameter within the short code.

** Short code parameters **
* s = comma separated list of symbols
* f = format of data you want to get from Yahoos' API
* l = labels to place in front of the data received
* b = defaults to 0 which is off, 1 will insert breaks in html between each row of data

 Ex.

`[stockasauras_quote s="YHOO"]`

Displays like this :

`35.62`

To customize the label and data received, simply do something like this :

`[stockasauras_quote s="YHOO,GOOG" f="s,l1,a,b" l=", -> Last Price :, Ask :, Bid :" b="1"]`

Which displays like this :

`YHOO -> Last Price :35.62 Ask :35.64 Bid :35.30
GOOG -> Last Price :566.07 Ask :567.00 Bid :565.90`

Visit [PsyCode.org](http://www.psycode.org/stockasauras/) for a complete list of "f"ormat data codes and other international indexes you can use or just checkout the [Yahoo Finance API Docs](https://code.google.com/p/yahoo-finance-managed/wiki/YahooFinanceAPIs).

= Fancy Pillbox Stock Quote =

Another shortcode "stockasauras_fancy_quote" gives you the ability to include a pillbox looking stock quotes that shows red, green, grey depending on the stocks last trading day percent change.

* s = Symbol of the stock to display

 Ex.

`[stockasauras_fancy_quote s="YHOO"]`

= Stock Charts =

`[stockasauras_chart symbol="YHOO" t="1d"]`

Generates a price chart for 1 trading day.

**You can choose the time span by changing the t parameter to one of the following**
* 5d
* 3m   ~ (3 months)
* 6m
* 1y   ~ (1 year)
* 2y
* 5y
* my   ~ (max)

= Watchlist Widget =

This plugin also offers a "Stockasauras Watchlist" widget that allows you to create a stock watchlist which displays symbol, last price, day price change, day percent change and even colors them accordingly.

You may customize the title, stock symbols, data outputted, and outbound links (for the symbols so they can get more info on a stock, defaults to yahoo).

= Settings =

Under Settings -> Stockasauras

**For now the current settings are**

* Enable Caching (save csv data and chart images to your web server)
* Caching time (how many seconds we should keep using the files data before attempting to get current data via the API)

= Misc =

Requires WordPress 3.0 and PHP 5.

**Current add-ons**
* Watchlist widget
* Short code to diplay over 87 data points on your site
* Fancy pillbox quote for inline blogging awesomeness
* Chart images from Yahoo

**Coming soon**
* Fix pillbox outbound links
* Change all outbound links to open new window or just make as an option
* Allow user to get more detailed charts (more api parameters we can use but isn't coded for it right now)

If you have suggestions for a new add-on, feel free to email me at Josh@PsyCode.org.

Want regular updates? Become a fan of my sites on Facebook!

https://www.facebook.com/pages/Psycodeorg-Community/186214501474471

Or follow me on Twitter!

https://twitter.com/psycodedotorg

== Installation ==

1. Upload `stockasauras` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the \'Plugins\' menu in WordPress.
3. Use the short codes in posts to show quotes, charts, etc.
4. Go to widgets to create a sweet looking watchlist of your favorite stocks.

== Frequently Asked Questions ==
= How do I request support or features? =

You can e-mail us at support@psycode.org for any feature requests or ask any questions. We don\'t provide free support but we can be bribed to help or add features.

= Where can I find documentation on Stockasauras? =

http://www.psycode.org/stockasauras/

= Can I contribute to this? =

Sure, there is a github for this project.

https://github.com/PsycodeDotOrg/Stockasauras

Feel free to record bugs, or send pull requests there.

== Screenshots ==
1. Stockasauras tearin\' sh*t up

== Changelog ==
= 0.1 =
* 1st Alpha Release