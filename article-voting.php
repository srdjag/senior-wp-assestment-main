<?php

/**
 * Plugin Name:       Article Voting
 * Plugin URI:        https://example.com
 * Description:       Gather feedback for your articles.
 * Version:           1.0.0
 * Author:            Srdja Gunjic
 * Author URI:        https://www.linkedin.com/in/srdjagunjic//
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       article-voting
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'ARTICLE_VOTING_VERSION', '1.0.0' );

// Include main class
include_once(plugin_dir_path(__FILE__) . 'includes/article-voting-class.php');

// Instantiate the main class
$article_voting = new ArticleVoting();
