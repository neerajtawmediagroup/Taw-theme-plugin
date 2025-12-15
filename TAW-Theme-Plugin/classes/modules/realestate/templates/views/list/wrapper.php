<?php
/**
 * List View Wrapper Template
 *
 * @var array  $properties Array of property data
 * @var bool   $has_posts Whether there are posts
 * @var object $module Module instance
 *
 * @package TAW_Theme
 * @subpackage RealEstate
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $has_posts || empty( $properties ) ) {
	echo $module->load_template( 'no-results.php', array(), '', 'shared' );
	return;
}
?>
<div class="taw-property-list taw-property-list-view">
	<?php foreach ( $properties as $property ) : ?>
		<?php echo $module->load_template( 'item.php', array( 'property' => $property, 'module' => $module ), 'list' ); ?>
	<?php endforeach; ?>
</div>
<?php echo $module->load_template( 'styles.php', array( 'module' => $module ), 'list' ); ?>

