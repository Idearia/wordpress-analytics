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


## PHP 8 COMPATIBILITY AND DEPLOYMENT

The `php` branch locks Symfony YAML **5.4.56** and its compatible runtime dependencies.
The 5.4 line is intentional: Symfony 6.4 requires PHP 8.1 and Symfony 7.4 requires
PHP 8.2, whereas this branch also targets PHP 8.0. Newer PHP versions are supported
only as verified by the test matrix, not by an open-ended compatibility guarantee.
Use a PHP version that still receives security updates for production.

`vendor/` is not tracked by Git. Updating the plugin files alone does **not** replace
an old installed copy of Symfony YAML. From the plugin directory, deploy the
committed lock file with Composer 2:

```sh
composer install --no-dev --no-plugins --no-scripts --prefer-dist --no-interaction --optimize-autoloader
composer check-platform-reqs --no-dev
php tests/php-compatibility.php
```

Alternatively, build with the same command and package the resulting `vendor/`
with the plugin. The GitHub source ZIP alone is not an installable plugin bundle.
Do not run an unrestricted `composer update` in production or manually patch
`vendor/symfony/yaml/Parser.php`.

The old YAML 3.2.6 parser passes a non-numeric block-scalar modifier to `abs()`.
PHP 8.0 throws `TypeError` for this instead of tolerating the call. The upstream
fix is included in the locked version, together with subsequent YAML fixes:

- [PHP 8.0 incompatible changes](https://www.php.net/manual/en/migration80.incompatible.php)
- [Symfony's block-scalar fix](https://github.com/symfony/symfony/pull/32910)
- [PHP 8.1 deprecations](https://www.php.net/manual/en/migration81.deprecated.php)
- [PHP 8.4 deprecations](https://www.php.net/manual/en/migration84.deprecated.php)
- [PHP 8.5 deprecations](https://www.php.net/manual/en/migration85.deprecated.php)
- [Symfony 5.4 maintenance information](https://symfony.com/releases/5.4)

The regression suite covers folded/literal YAML blocks, scalar types, aliases,
invalid YAML, both bundled settings files and the plugin's menu-loading path.
It also checks PHP 8.1-safe regex validation and the settings editor when optional
CodeMirror assets are absent. Warnings and deprecations fail the tests.
GitHub Actions runs these checks on PHP **8.0, 8.1, 8.2, 8.3, 8.4 and 8.5**.
WordPress APIs are stubbed: this is not a full WordPress, Gravity Forms or analytics
integration test, and no live website is updated by this change.

The legacy private `require-dev` coding standards still require PHP 7.x and their
repository credentials. They are intentionally outside this runtime fix; use
`--no-dev` for the PHP 8 production/test installation above. Production dependencies
are public, and the private Composer repository is scoped to `idearia/*`.
Recent Composer versions may still report an HTTP 401 warning when querying that
private repository for advisory/filter metadata; the tested `--no-dev` installation
succeeds without its credentials, but that is not a complete audit of private packages.
If an old parser is still used after deployment, inspect the actual loaded class
with `(new ReflectionClass(\Symfony\Component\Yaml\Parser::class))->getFileName()`
inside WordPress: another plugin may have loaded a different Symfony copy first.

## CREDITS
A huge thanks to Justin Cutroni for sharing with the community his great [content tracking script]!


## CONTRIBUTE
Please feel free to contribute to *WordPress Analytics* by forking the project on [Github].


[content tracking script]: http://cutroni.com/blog/2014/02/12/advanced-content-tracking-with-universal-analytics
[Github]: https://github.com/coccoinomane/wordpress_analytics
