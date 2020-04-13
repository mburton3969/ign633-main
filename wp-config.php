<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'ignition_main_site' );

/** MySQL database username */
define( 'DB_USER', 'ignition_ign633' );

/** MySQL database password */
define( 'DB_PASSWORD', 'nObi%Lzvo#Fl' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '_(jBeFrcGsb65?XU5?4/&#$(D.2DMgDMw7bB.3K)$7h%lY*it6;tcZ8bX/E$cOfl' );
define( 'SECURE_AUTH_KEY',  'r5 u:J!iW-C`$$xkR%!<h2Xl=KYTifxNOrZSsL:t_[5EneW#rIQa~R9G_#Eac[yh' );
define( 'LOGGED_IN_KEY',    'mmcCo)@sRdXS7rgy;CLHtw;@}k5L.]Qu~2?<^Qhh.HH_{i3H{p3AqjLw3fW<s<m4' );
define( 'NONCE_KEY',        'QXPsA%Yr%~?GkWKd<LP^.Q?K7tL9V9eVbKM@75KIGz*@BM:q>x=*GZKy>cItEan~' );
define( 'AUTH_SALT',        '(?)yR5PU<jSfLb+2W]MpjzNte4ot#!GzfYs$m`e&#zk+/XBwcI].Dv:ln/#H[.z;' );
define( 'SECURE_AUTH_SALT', '&rU6EogZo2)h)$jM}Vw#MDNG{S]/0wJs9{Cyrr2C/%^.h[P<p7Br*_0KOnj]P=B0' );
define( 'LOGGED_IN_SALT',   '97X|tJfktU=q|N:CrUEfn|znWTvqL<?DLnT~-J]}}<`[N#Nt>O|ED})<,]HD^4TU' );
define( 'NONCE_SALT',       'egwR^BDAoFBpX>W[a1+hwGEQ?sKZMnT~wqpTL^*&w7LNPhQ0-:$-$0u:#/%o!4,C' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
