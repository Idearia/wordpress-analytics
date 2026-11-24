## DESCRIPTION
Install *WordPress Analytics* on your website to track user activity beyond simple pageviews, and to allow efficient communication between WordPress and Google Analytics 4 (GA4).

*WordPress Analytics* supports the following features:

* **Google tag**: load Google Analytics 4 (gtag.js) and send pageviews, or leave that to Google Tag Manager.
* **Content grouping**: send category information from WordPress to Google Analytics, with WooCommerce support.
* **Call tracking**: track clicks on phone numbers throughout the website, with the possibility to include/exclude certain numbers based on regex, and turn phone numbers into links.
* **Email tracking**: track clicks on email addresses throughout the website.
* **Custom code**: print your own code (ex. a Facebook Pixel) in the head of all pages.
* **Form tracking**: track form submissions made via [Gravity Forms](http://www.gravityforms.com/) (being ported to GA4, see below).

Universal Analytics (analytics.js) is not supported anymore: Google stopped processing its data in July 2023.


## INSTALL OR UPDATE

**You do not need SSH**, Composer or a console. The plugin has no `vendor/` folder and runs on PHP **7.2 to 8.5**.

1. Replace the plugin folder `wp-content/plugins/wordpress-analytics` with the new one, in any of these ways:
   * upload the zip from *Plugins > Add New > Upload Plugin* and choose *Replace current with uploaded*;
   * or overwrite the folder via FTP/SFTP or the file manager of your hosting.
2. **There is no file to move or copy.** The settings you entered (custom code, phone settings...) are stored in the database, under the `wpan:*` options, and are kept as they are.
3. Go to *WordPress Analytics* in the admin menu, then:
   * enter the **Google Analytics 4 measurement ID** (`G-XXXXXXXXXX`); you find it in Google Analytics under *Admin > Data streams*, in the details of the web stream. The old `UA-XXXXXX-Y` ID cannot be converted and is ignored;
   * choose **who loads Google Analytics 4** (see below).

Until the measurement ID is entered, a notice in the admin reminds you of it. In the meanwhile nothing is sent to Google Analytics, while custom code and phone number links keep working.

### Who loads Google Analytics 4?

| Choice | When to use it | What the plugin prints |
| --- | --- | --- |
| WordPress Analytics (gtag.js) | Nothing else loads GA4 on the website | The Google tag; pageviews are sent |
| Google Tag Manager or another plugin | GA4 is already loaded by a GTM container (ex. via the *GTM4WP* plugin) or by another plugin | No Google tag, so that pageviews are **not counted twice** |
| Nobody | You only want custom code and phone number links | No Google tag |

If *Google Tag Manager for WordPress* is active and the plugin is set to load GA4 by itself, a notice in the admin warns you of the risk of double pageviews.

Call and email tracking send the `phone_click` and `email_click` events (with the `link_url` and `page_path` parameters) when the Google tag is loaded by WordPress Analytics.

### What about settings.yaml?

Normally you can forget about it. `settings/settings.json` (bundled) and the former `settings/settings.yaml` only describe which fields the settings page has; they do not contain your settings.

The only case in which a website needs its own `settings.yaml` is if somebody edited that file by hand on that website. To find out, compare the `settings/settings.yaml` of the website with the [original one](tests/fixtures/settings.legacy.yaml): if they are the same, or if the website has no such file, there is nothing to do. Even if they differ, you most likely do not need to copy the file over: it describes the Universal Analytics fields.

If a `settings/settings.yaml` is left in (or copied to) the plugin folder, it still takes precedence over `settings.json`: it is read by a minimal YAML reader bundled with the plugin (`settings/settings_yaml.php`, no library needed), the Universal Analytics fields are removed from it and the Google Analytics 4 ones are added automatically. The reader supports nested mappings, comments, plain and quoted values, folded (`>`) and literal (`|`) text blocks. If the file uses anything else (lists, anchors, inline collections) the plugin uses `settings.json` instead, logs the reason and shows it in a red notice on every admin page, until the file is fixed or deleted.

### Form tracking

Form tracking (Gravity Forms) is being ported to the Google Analytics 4 measurement protocol, with no external library. Until then it is inactive: it still targets Universal Analytics and needs the Composer package `ins0/google-measurement-php-client`.

### Tests

`tests/php-compatibility.php` checks the settings files, the YAML reader and the upgrade of a legacy `settings.yaml` (`tests/fixtures/`), the options loading, the full `<head>` output in every load mode (Google tag, call/email tracking, custom code, content grouping), the admin notices, the settings validation and the settings editor without CodeMirror. Warnings and deprecations fail the tests. GitHub Actions lints every PHP file and runs the tests on PHP **7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 and 8.5**. WordPress APIs are stubbed: this is not a full WordPress or Gravity Forms integration test.

## CREDITS
A huge thanks to Justin Cutroni for sharing with the community his great [content tracking script], on which the former scroll tracking feature was based!


## CONTRIBUTE
Please feel free to contribute to *WordPress Analytics* by forking the project on [Github].


[content tracking script]: http://cutroni.com/blog/2014/02/12/advanced-content-tracking-with-universal-analytics
[Github]: https://github.com/coccoinomane/wordpress_analytics
