<?php
/*
Template Name: Single Staff Pick
*/
$post = get_post();
$custom = get_post_custom($post->ID);


if (isset($custom['index_cards'])) {
  $metadata = maybe_unserialize(
    $custom['index_cards'][0]
  );
} else {
  $metadata = array();
}

get_header();
?>
<div id="content">
<h2><?php echo $metadata['headline']; ?></h2>
<table>
  <?php foreach($metadata as $field_name => $content): ?>
    <tr>
      <th><?php echo $field_name; ?></th>
      <td><?php echo $content; ?></td>
    </tr>
  <?php endforeach; ?>
</table>
</div>
<?php
get_footer();
