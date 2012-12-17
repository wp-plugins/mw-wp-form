=== Plugin Name ===
Contributors: Takashi Kitajima
Donate link: 
Tags: plugin, form, confirm, preview
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plug-in which can create mail form using short code. E-mail sending and validation can be specified at functions.php.

== Description ==

Plug-in which can create mail form using short code. E-mail sending and validation can be specified at functions.php.
http://2inc.org/blog/category/products/wordpress_plugins/mw-wp-form/

== Installation ==

1. Upload `MW WP Form` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php add_filter( 'mwform_validation_{$key}', array( $this, 'my_validation_filter_name' ) ); ?>` in your functions.php
1. Place `<?php add_action( 'mwform_mail_{$key}', array( $this, 'my_mail_action_name' ) ); ?>` in your functions.php

== Changelog ==

= 0.5 =
* Initial release.