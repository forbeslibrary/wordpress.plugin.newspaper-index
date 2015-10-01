<?php
/*
Template Name: Single Index Card
*/
$post = get_post();
get_header();
?>
<div id="content">
<?php include(dirname( __FILE__ ) . '/index-card.php'); ?>
</div>
<?php
get_footer();
