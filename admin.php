<?php
/**
 * Admin interface for the plugin.
 */
class Newspaper_Index_Admin {
  public function __construct($plugin) {
    $this->plugin = $plugin;
    $this->add_hooks();
  }

  // admin only action hooks
  public function add_hooks() {
    add_action('admin_head', array($this, 'admin_css'));
    add_action('admin_notices', array($this, 'admin_notice'));
    add_action('dashboard_glance_items', array($this, 'add_glance_items'));
    add_action('edit_form_after_title', array($this, 'editbox_metadata'));
    //add_action("manage_{$this->plugin->data['post_type']}_posts_custom_column", array($this, 'custom_columns'));
    //add_action('pre_insert_term', array($this, 'restrict_insert_taxonomy_terms'));
    add_action('save_post', array($this, 'validate_and_save'));
    add_action('add_meta_boxes', array($this, 'modify_metaboxes'));

    //add_filter("manage_{$this->plugin->data['post_type']}_posts_columns", array($this, 'manage_columns'));
    //add_filter('redirect_post_location', array($this, 'fix_status_message'));
  }

  /**
  * Save custom fields from {$post_type} edit page.
  *
  * @wp-hook save_post
  */
  public function validate_and_save( $post_id ){
    $post =  get_post( $post_id );

    if ( $post->post_type != $this->plugin->data['post_type'] ) {
     return;
    }

    // Update fields
    if (isset($_POST[$this->plugin->data['post_type']])) {

      $custom_fields = $_POST[$this->plugin->data['post_type']];
      foreach ($this->plugin->data['custom_fields'] as $field_name => $input_type) {
        $custom_fields[$field_name] = trim($custom_fields[$field_name]);
      }
      update_post_meta($post->ID, $this->plugin->data['post_type'], $custom_fields);

      // we must remove this action or it will loop for ever
      remove_action('save_post', array($this, 'validate_and_save'));

      // update the post excerpt and title
      wp_update_post(
        array(
          'ID'=>$post->ID,
          'post_content' => $this->plugin->get_metadata_table($post),
          'post_title' => ($custom_fields['headline'] ? $custom_fields['headline'] : '[no headline]')
        )
      );

      // we must add back this action
      add_action('save_post', array($this, 'validate_and_save'));
    }

    // Stop interfering if this is a draft or the post is being deleted
    if ( in_array(
      get_post_status( $post->ID ),
      array('draft', 'auto-draft', 'trash')
    )) {
       return;
    }

    // Validation
    $errors = array();

    // no validation at present

    if ($errors) {
      // Save the errors using the transients api
      set_transient( $this->plugin->data['post_type'] . "_errors_{$post->ID}", $errors, 120 );

      // we must remove this action or it will loop for ever
      remove_action('save_post', array($this, 'validate_and_save'));

      // Change post from published to draft
      $post->post_status = 'draft';

      // update the post
      wp_update_post( $post );

      // we must add back this action
      add_action('save_post', array($this, 'validate_and_save'));
    }

  }

  /**
  *  Fix status message when user tries to publish an invalid post
  *
  * If the user hits the publish button the publish message will display even if
  * we have changed the status to draft during validation. This fixes that by
  * modifying the message if any errors have been queued.
  *
  * FIX ME. Currently broken!
  *
  * @wp-hook redirect_post_location
  */
  public function fix_status_message($location, $post_id) {
    //If any errors have been queued...
    if (get_transient( $this->plugin->data['post_type'] . "_errors_{$post->ID}" )){
      $status = get_post_status( $post_id );
      $location = add_query_arg('message', 10, $location);
    }

    return $location;
  }

  /**
  * Adds custom CSS to admin pages.
  *
  * @wp-hook admin_head
  */
  public function admin_css() {
    echo '<style>';
    readfile(dirname( __FILE__ ) . '/css/admin.css');
    echo '</style>';
  }

  /**
   * The Weaver II theme adds a giant meta box that isn't much help with custom
   * post types. This code removes that box from edit pages.
   *
   * @wp-hook add_meta_boxes
   */
  public function modify_metaboxes() {
    remove_meta_box('wii_post-box2', $this->plugin->data['post_type'], 'normal');
    remove_meta_box( 'postimagediv', $this->plugin->data['post_type'] , 'side' );
  }


  /**
   * Displays admin notices such as validation errors
   *
   * @wp-hook admin_notices
   */
  public function admin_notice() {
    global $post;

    if (!isset($post)) {
      return;
    }

    $errors = get_transient( $this->plugin->data['post_type'] . "_errors_{$post->ID}" );
    if ($errors) {
      foreach ($errors as $error): ?>
        <div class="error">
          <p><?php echo $error; ?></p>
        </div>
        <?php
      endforeach;
    }
    delete_transient( $this->plugin->data['post_type'] . "_errors_{$post->ID}" );
  }

  /**
   * Outputs the contents of each custom column on the admin page.
   *
   * @wp-hook manage_{$post_type}_posts_custom_column
   */
  public function custom_columns($column){
    global $post;
    $custom = get_post_custom($post->ID);
    if (isset($custom[$this->plugin->data['custom_field_name']])) {
      $metadata = maybe_unserialize(
        $custom[$this->plugin->data['custom_field_name']][0]
      );
    } else {
      $metadata = array();
    }

    switch ($column) {
      case $this->plugin->data['post_type'] . '_author':
        if (isset($metadata['author'])) {
          echo $metadata['author'];
        }
        break;
    }

  }

  /**
   * Customizes the columns on the {$post_type} admin page.
   *
   * @wp-hook manage_{$post_type}_posts_columns
   */
  public function manage_columns($columns){
    $custom_columns = array(
      'title' => 'Title',
      $this->plugin->data['post_type'] . '_author' => 'Author',
    );

    $columns = array_merge( $columns, $custom_columns);

    return $columns;
  }

  /**
   * Add information about {$post_type} to the glance items.
   *
   * @wp-hook dashboard_glance_items
   */
  public function add_glance_items() {
    $pt_info = get_post_type_object($this->plugin->data['post_type']);
    $num_posts = wp_count_posts($this->plugin->data['post_type']);
    $num = number_format_i18n($num_posts->publish);
    $text = _n( $pt_info->labels->singular_name, $pt_info->labels->name, intval($num_posts->publish) ); // singular/plural text label
    echo '<li class="page-count ' . $pt_info->name . '-count"><a href="edit.php?post_type=' . $this->plugin->data['post_type'] . '">' . $num . ' ' . $text . '</li>';
  }

  /**
   * Outputs the html for the  metadata box on the {post_type} edit page.
   */
  public function editbox_metadata(){
    global $post;
    if ($post->post_type !== $this->plugin->data['post_type']) {
      return;
    }
    $custom = get_post_custom($post->ID);
    if (isset($custom[$this->plugin->data['post_type']])) {
      $metadata = maybe_unserialize(
        $custom[$this->plugin->data['post_type']][0]
      );
    } else {
      $metadata = array();
    }
    ?>
    <table class="<?php echo $this->plugin->data['post_type']; ?>-metadata-table" >
      <?php foreach ($this->plugin->data['custom_fields'] as $custom_field => $input_type): ?>
        <tr>
          <td><label
            class="<?php echo $this->plugin->data['post_type']; ?>-metadata-label"
            for="<?php echo "{$this->plugin->data['post_type']}[$custom_field]"; ?>"
            >
            <?php echo $custom_field; ?>
          </label></td>
          <td>
            <?php switch ($input_type):
              case "checkbox" ?>
              <input
                name="<?php echo "{$this->plugin->data['post_type']}[$custom_field]"; ?>"
                class="<?php echo $this->plugin->data['post_type']; ?>-input"
                value="no"
                type="hidden"
                >
              <input
                id="<?php echo "{$this->plugin->data['post_type']}[$custom_field]"; ?>"
                name="<?php echo "{$this->plugin->data['post_type']}[$custom_field]"; ?>"
                class="<?php echo $this->plugin->data['post_type']; ?>-input"
                <?php checked($metadata[$custom_field], 'yes'); ?>
                value="yes"
                type="checkbox"
                >
                <?php break;
              case "textarea" ?>
                <textarea
                  id="<?php echo "{$this->plugin->data['post_type']}[$custom_field]"; ?>"
                  name="<?php echo "{$this->plugin->data['post_type']}[$custom_field]"; ?>"
                  class="<?php echo $this->plugin->data['post_type']; ?>-input"
                ><?php // be careful to not introduce extra whitespace into textarea!!
                  if (isset( $metadata[$custom_field] )) {
                    echo $metadata[$custom_field];
                  }
                ?></textarea>
                <?php break; ?>
              <?php default: ?>
                <input
                  id="<?php echo "{$this->plugin->data['post_type']}[$custom_field]"; ?>"
                  name="<?php echo "{$this->plugin->data['post_type']}[$custom_field]"; ?>"
                  class="<?php echo $this->plugin->data['post_type']; ?>-input"
                  type="<?php echo $input_type; ?>"
                  <?php if (isset( $metadata[$custom_field] )): ?>
                    value="<?php echo $metadata[$custom_field]; ?>"
                  <?php endif; ?>
                />
            <?php endswitch; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php
  }
}
