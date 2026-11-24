## DESCRIPTION
Install *WordPress Analytics* on your website to track user activity beyond simple pageviews, and to allow efficient communication between WordPress and Google Analytics.

So far *WordPress Analytics* supports the following features:

* **Scroll tracking**: track the scrolling actions performed by users; this is an improved version of Justin Cutroni's [content tracking script].
* **Content grouping**: send category information from WordPress to Google Analytics, with WooCommerce support.
* **Call tracking**: track clicks on phone numbers throughout the website, with the possibility to include/exclude certain numbers based on regex.
* **Email tracking**: track clicks on email addresses throughout the website.
* **Form tracking**: track form submissions made via [Gravity Forms](http://www.gravityforms.com/).


The next feature to be implemented will be:

* **Custom-fields support**: send any kind of information from WordPress to Google Analytics using custom fields & taxonomies, with support to the industry standard plugin [Advanced Custom Fields](http://www.advancedcustomfields.com/).


## INSTALL
Download the latest release and install it directly in WordPress using the "Add new plugin" menu item in the backend.

Check the [development branch](https://github.com/coccoinomane/wordpress_analytics/tree/develop) for the latest features!


## PHP COMPATIBILITY AND DEPLOYMENT

The `php` branch runs on PHP **7.2 to 8.5** and needs no `vendor/` folder: to deploy
it, replace the plugin folder on the website. No Composer, no console.

The plugin settings structure is read from `settings/settings.json` with PHP's
built-in `json_decode()`. The former `settings.yaml` needed Symfony YAML, and each
release of that library only supports a limited range of PHP versions (the old
3.2.6 parser throws a `TypeError` on PHP 8.0+). The JSON file was generated from
the YAML one and produces the very same array; option values stay in the database
under the same `wpan:*` keys, so no migration is needed. A website with a
customised `settings.yaml` must convert it to JSON before updating.

Form tracking (Gravity Forms) is the only feature that still needs a Composer
package, `ins0/google-measurement-php-client`. Without it the plugin works and
form tracking is simply skipped. To enable it, run from the plugin directory:

```sh
composer install --no-dev --no-plugins --no-scripts --prefer-dist --no-interaction
```

`tests/php-compatibility.php` checks the settings files, the options loading, the
full `<head>` output (tracker, call/email tracking, custom code), the PHP 8.1-safe
regex validation and the settings editor without CodeMirror. Warnings and
deprecations fail the tests. GitHub Actions lints every PHP file and runs the tests
on PHP **7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 and 8.5**. WordPress APIs are
stubbed: this is not a full WordPress or Gravity Forms integration test.

## CREDITS
A huge thanks to Justin Cutroni for sharing with the community his great [content tracking script]!


## CONTRIBUTE
Please feel free to contribute to *WordPress Analytics* by forking the project on [Github].


[content tracking script]: http://cutroni.com/blog/2014/02/12/advanced-content-tracking-with-universal-analytics
[Github]: https://github.com/coccoinomane/wordpress_analytics
