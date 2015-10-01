<?php
/**
 * Plugin Name: Newspaper Index
 * Plugin URI:
 * Description: A tool for creating a very simple newspaper index
 * Version: 0.1.0
 * Author: Benjamin Kalish
 * Author URI: https://github.com/bkalish
 * License: GNU General Public License v2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

class Newspaper_Index_Plugin {
  public function __construct() {
    $data_file = file_get_contents(dirname( __FILE__ ) . '/data.json');
    $this->data = json_decode($data_file, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      trigger_error('Could not parse invalid JSON');
    }

    $this->load_dependencies();
    $this->add_hooks();
  }

  /**
   * Requires/loads other files used by this plugin
   */
  public function load_dependencies() {
    if ( is_admin() ) {
      require_once(dirname( __FILE__ ) . '/admin.php');
    }
  }

  /**
   * Adds/registers hooks for this plugin
   */
  public function add_hooks() {
    // activation hooks
    register_activation_hook(__FILE__, array($this, 'flush_rewrites'));

    // action hooks
    add_action('init', array($this, 'init'));

    // filter hooks
    //add_filter('archive_template', array($this, 'filter_archive_template'));
    add_filter('single_template', array($this, 'filter_single_template'));
    //add_filter('wp_title', array($this, 'filter_page_title'));
  }

  /**
   * Flush rewrite rules on plugin activation
   *
   * This is registered with register_activation_hook for this file
   */
  function flush_rewrites() {
    $this->init();
    flush_rewrite_rules();
  }


  /**
   * Registers the custom post type {post_type} and the custom taxonomies.
   *
   * @wp-hook init
   */
  function init() {
    register_post_type( $this->data['post_type'], $this->data['post_type_data'] );
  }

  /**
   * Ouputs css to be used on public pages for this plugin
   *
   * @wp-hook wp_head
  function output_public_css() {
    echo '<style>';
    readfile(dirname( __FILE__ ) . '/css/public.css');
    echo '</style>';
  }
  */

  /**
   * Return the template file used to display a single post
   *
   * This is a filter. The current template is passed as an argument and is
   * modified if neccessary.
   *
   * @wp-hook single_template
   */
  function filter_single_template($template){
    global $post;

    if ($post->post_type == $this->data['post_type']) {
       $template = dirname( __FILE__ ) . "/templates/single-{$this->data['post_type']}.php";
    }
    return $template;
  }

  /**
   * Return the template file used to display archive pages
   *
   * This is a filter. The current template is passed as an argument and is
   * modified if neccessary.
   *
   * @wp-hook archive_template
   *
  function filter_archive_template($template){
    global $post;

    $use_custom_template = is_post_type_archive($this->data['post_type']);

    foreach($this->data['taxonomies'] as $taxonomy) {
      $use_custom_template = ($use_custom_template or is_tax($taxonomy['taxonomy_name']));
    }

    if ($use_custom_template) {
       $template = dirname( __FILE__ ) . "/templates/archive-{$this->data['post_type']}.php";
    }

    return $template;
  }
  */

  /**
   * Modifies the page title
   *
   * This is a filter. The current title is passed as an argument and is
   * modified if neccesary.
   *
   * @wp-hook wp_title
   *
  function filter_page_title($title) {
    if (staff_picks_get_title()) {
      $title = staff_picks_get_title();
      return "$title";
    }
    return $title;
  }
  */
}
// create a plugin instance to load the plugin
new Newspaper_Index_Plugin();
