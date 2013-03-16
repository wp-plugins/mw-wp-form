=== Plugin Name ===
Contributors: Takashi Kitajima
Donate link:
Tags: plugin, form, confirm, preview
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 0.6.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

MW WP Form can create mail form with a confirmation screen using shortcode.

== Description ==

MW WP Form can create mail form with a confirmation screen using shortcode.
* Form created using short codes
* Using confirmation page.
* The page changes by the same URL or individual URL are possible.
* Many validation rules
http://2inc.org/blog/category/products/wordpress_plugins/mw-wp-form/

== Installation ==

1. Upload `MW WP Form` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. You can create a form by settings page or using functions.php.
1. If you using functions.php, place `<?php add_filter( 'mwform_validation_{$key}', array( $this, 'my_validation_filter_name' ) ); ?>` in your functions.php
1. If you using functions.php, place `<?php add_filter( 'mwform_mail_{$key}', array( $this, 'my_mail_action_name' ) ); ?>` in your functions.php

== Changelog ==

= 0.6.3 =
* Bug fix: 管理画面のURL設定で http から入れないとメールが二重送信されてしまうバグを修正
* Bug fix: フォーム識別子部分が Firefox でコピペできないバグを修正

= 0.6.2 =
* Bug fix: Infinite loop when WordPress not root installed.

= 0.6.1 =
* Added To E-mail adress settings.

= 0.6 =
* Added settings page.
* Deprecated: acton hook mwform_mail_{$key}. This hook is removed when next version up.
* Added filter hook mwform_mail_{$key}.
* Bug fix: Validations.

= 0.5.5 =
* Added tag to show login user meta.
{user_id}, {user_login}, {user_email}, {user_url}, {user_registered}, {display_name}

= 0.5 =
* Initial release.