<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'aielectDBqfqze');

/** MySQL database username */
define('DB_USER', 'aielectDBqfqze');

/** MySQL database password */
define('DB_PASSWORD', 'qCfiaruRJ');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '5ZK[@sG4shV:~OD1pd5]-SH#wlD1pdS]-L9;ma;.xPD2xh9;_aL_xl6{eTH*uI6{q');
define('SECURE_AUTH_KEY',  '8hRG!vNC0kZ0|zK5shV:_WK9~sK8:hV:_wD1peS]~SH5-pd5#dSG~teTH*tL6]iX6');
define('LOGGED_IN_KEY',    '@YU^0!}Co@w|Rggs8NJZ|-[Vodo~GSNZoC1[~:dWl5KKd[91Kw#+HWSi;HHW_5;Dp');
define('NONCE_KEY',        '~]adt9SKa]9]Aq*xHXWm2LDT*2.2fyq*PPe]E6Ly{${bqj3IIX.6Eq^v,Ujc}BBQ');
define('AUTH_SALT',        '|z01:K|VlWp5KHt_;:9ht~t_KWht.LXiXi]ALHT+#6]6ep+iuAMXMXj66IT+<+<6');
define('SECURE_AUTH_SALT', '|gvdo-GRGSd|51COw!w!1ZkSdt5G5HS-#11Dlw_w_OZlSe#6H6Hp+##2alxl+DOaH');
define('LOGGED_IN_SALT',   'swCOZZk:8NCNw!:~[Sdpht5GSOd|1C1COw_t~]SeWit5HDOa_1#1Dlxit*HTeXi]6');
define('NONCE_SALT',       'wRdVh[5K1Dlx_+#Oalht9KWKWh]5.2Dmxmx.LWWit5H9LW*]6.;bmym$EPbXi]ALA');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);define('FS_METHOD', 'direct');

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
