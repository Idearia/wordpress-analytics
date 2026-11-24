## DESCRIPTION
Install *WordPress Analytics* on your website to track user activity beyond simple pageviews, and to allow efficient communication between WordPress and Google Analytics 4 (GA4).

*WordPress Analytics* supports the following features:

* **Google tag**: load Google Analytics 4 (gtag.js) and send pageviews, or leave that to Google Tag Manager.
* **Scroll tracking**: track the scrolling actions performed by users (started reading, reached the end of the content, read it, reached the bottom of the page); this is an improved version of Justin Cutroni's [content tracking script].
* **Content grouping**: send category information from WordPress to Google Analytics, with WooCommerce support.
* **Call tracking**: track clicks on phone numbers throughout the website, with the possibility to include/exclude certain numbers based on regex, and turn phone numbers into links.
* **Email tracking**: track clicks on email addresses throughout the website.
* **Custom code**: print your own code (ex. a Facebook Pixel) in the head of all pages.
* **Form tracking**: track form submissions made via [Gravity Forms](http://www.gravityforms.com/) from the server, so that ad blockers cannot stop it.

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

If you want the plugin to load the Google tag without sending the pageview, uncheck *Send pageview to GA?* in the advanced settings (it is checked by default, and a website updated from the Universal Analytics version keeps the choice it had).

If *Google Tag Manager for WordPress* is active and the plugin is set to load GA4 by itself, a notice in the admin warns you of the risk of double pageviews.

In the *Google Tag Manager* mode the scroll, phone and email events (`start_reading`, `phone_click`, `email_click`, ...) are pushed to the `dataLayer` as custom events with the same names and parameters: to see them in GA4, add to your GTM container a GA4 event tag fired by those custom events.

In the same mode, on single posts and products the content group is pushed to the `dataLayer` at the very beginning of the `<head>`, before the container is loaded, with the keys `content_group`, `product_category` and `content_type`: to send them to GA4, read them in your GTM container with Data Layer Variables and add them as parameters of the GA4 tag. If GA4 is loaded by another plugin, without Google Tag Manager, the content group is not sent.

If the custom code still calls the Universal Analytics tracker (ex. `ga('send', ...)`), those calls do nothing anymore: a notice in the admin tells you, until the code is rewritten or removed.

Call and email tracking send the `phone_click` and `email_click` events (with the `link_url` and `page_path` parameters) when the Google tag is loaded by WordPress Analytics.

Scroll tracking sends, on single posts, the events `article_loaded`, `start_reading` (parameter `time_to_scroll`), `content_bottom` (parameters `time_to_content_end` and `reading_behaviour`, either `Scanner` or `Reader`), `content_read` (only for readers) and `page_bottom` (parameter `total_time`); times are in seconds. To use the parameters in the GA4 reports, register them in the GA4 admin as custom metrics and, for `reading_behaviour`, as a custom dimension. Like call and email tracking, the events are sent when the Google tag is loaded by WordPress Analytics.

### What about settings.yaml?

Normally you can forget about it. `settings/settings.json` (bundled) and the former `settings/settings.yaml` only describe which fields the settings page has; they do not contain your settings.

The only case in which a website needs its own `settings.yaml` is if somebody edited that file by hand on that website. To find out, compare the `settings/settings.yaml` of the website with the [original one](tests/fixtures/settings.legacy.yaml): if they are the same, or if the website has no such file, there is nothing to do. Even if they differ, you most likely do not need to copy the file over: it describes the Universal Analytics fields.

If a `settings/settings.yaml` is left in (or copied to) the plugin folder, it still takes precedence over `settings.json`: it is read by a minimal YAML reader bundled with the plugin (`settings/settings_yaml.php`, no library needed), the Universal Analytics fields are removed from it and the Google Analytics 4 ones are added automatically. The reader supports nested mappings, comments, plain and quoted values, folded (`>`) and literal (`|`) text blocks. If the file uses anything else (lists, anchors, inline collections) the plugin uses `settings.json` instead, logs the reason and shows it in a red notice on every admin page, until the file is fixed or deleted.

### Form tracking (Gravity Forms)

When a visitor submits a Gravity Forms form, the web server sends the event `generate_lead` to Google Analytics 4 with the [measurement protocol](https://developers.google.com/analytics/devguides/collection/protocol/ga4). The event is sent by the server, not by the browser, so ad blockers cannot stop it. No library is needed.

To turn it on:

1. In Google Analytics go to *Admin > Data streams*, open the web stream, then *Measurement Protocol API secrets*, and create a secret.
2. In the plugin settings paste it in **Measurement Protocol API secret**, right below the measurement ID, and check **Enable form tracking?**. The API secret is optional: it is needed only for form tracking. You can also define it in `wp-config.php` with `define( 'WPAN_GA4_API_SECRET', '...' );`, which takes precedence over the field.
3. Save, submit a form and look for the event `generate_lead` in Google Analytics, under *Reports > Realtime*. **Google does not report a wrong measurement ID or API secret**: the request is accepted and the event silently discarded, so seeing the event in Google Analytics is the only real check.
4. Mark `generate_lead` as a key event in *Admin > Events* if you want to count it as a conversion.

The event carries the parameters `form_id`, `form_title`, `page_location`, `page_path` and `page_title`. It is attributed to the visitor and to the session read from the Google Analytics cookies (`_ga` and `_ga_<ID>`), hence to the same traffic source as the pageviews; without cookies the form is counted anyway, with no traffic source. This works both when GA4 is loaded by WordPress Analytics and when it is loaded by Google Tag Manager.

For payment forms, if *Wait for payment confirmation* is checked the event is sent when the payment goes through, with the parameters `transaction_id`, `value` and `currency`.

If form tracking is enabled but the measurement ID or the API secret are missing, a notice in the admin warns you that form submissions are not being sent.

The event name `generate_lead` is used, rather than `form_submit`, because GA4 already sends `form_submit` from the browser by itself (enhanced measurement) for any form in the page: the two would be mixed up.

#### Hooks for developers

* `wpan_filter_ga4_form_event` (filter; arguments: `$event`, `$entry`, `$form`): change the event before it is sent. `$event` is an array with the `name` and `params` elements; return `false` not to send it.
* `wpan_action_after_ga4_form_event` (action; arguments: `$event`, `$visitor`, `$entry`, `$form`): run after the event is sent; you can send more events for the same visitor with `wpan_ga4_send_event( $event, $visitor )`.
* `wpan_filter_ga4_endpoint` (filter): change the host the events are sent to, ex. `https://region1.google-analytics.com` to collect data in the EU.

The hooks of the Universal Analytics version (`wpan_filter_form_tracking_options`, `wpan_filter_form_tracking_event`, `wpan_action_post_send_form_tracking_event`) are **not called anymore**: they received objects of a library that is no longer there. If a website still uses them, a notice in the admin names them.

### Tests

`tests/php-compatibility.php` checks the settings files, the YAML reader and the upgrade of a legacy `settings.yaml` (`tests/fixtures/`), the options loading, the full `<head>` output in every load mode (Google tag, call/email tracking, scroll tracking, custom code, content grouping), form tracking (cookies, payload sent to Google, payments, hooks; the HTTP request is stubbed), the admin notices, the settings validation and the custom code editor. Warnings and deprecations fail the tests. GitHub Actions lints every PHP file and runs the tests on PHP **7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 and 8.5**. WordPress APIs are stubbed: this is not a full WordPress or Gravity Forms integration test.

## CREDITS
A huge thanks to Justin Cutroni for sharing with the community his great [content tracking script]!


## CONTRIBUTE
Please feel free to contribute to *WordPress Analytics* by forking the project on [Github].


[content tracking script]: http://cutroni.com/blog/2014/02/12/advanced-content-tracking-with-universal-analytics
[Github]: https://github.com/coccoinomane/wordpress_analytics
