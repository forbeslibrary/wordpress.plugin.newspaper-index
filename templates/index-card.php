<?php
$custom = get_post_custom($post->ID);
if (isset($custom['index_cards'])) {
  $metadata = maybe_unserialize(
    $custom['index_cards'][0]
  );
} else {
  $metadata = array();
}
$date = $newDate = date("F j, Y", strtotime($metadata['date']));
?>
<table>
  <tr>
    <th>headline</th>
    <td><?php echo $metadata['headline']; ?></td>
  </tr>
  <tr>
    <th>source</th>
    <td>
      Daily Hampshire Gazette (Northampton, MA)<br>
      <b>
        <?php echo $date; ?>,
        p. <?php echo $metadata['page']; ?>
      </b>
    </td>
  </tr>
  <tr>
    <th>summary</th>
    <td><?php echo $metadata['annotation']; ?></td>
  </tr>
  <tr>
    <th>keywords</th>
    <td><?php echo $metadata['keywords']; ?></td>
  </tr>
</table>
