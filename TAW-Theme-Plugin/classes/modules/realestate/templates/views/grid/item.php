<?php
/**
 * Grid View Item Template
 *
 * @var array $property Property data array
 *
 * @package TAW_Theme
 * @subpackage RealEstate
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="taw-property-item">
	<?php if ( ! empty( $property['thumbnail'] ) ) : ?>
		<div class="taw-property-photo">
			<a href="<?php echo esc_url( $property['permalink'] ); ?>">
				<?php echo $property['thumbnail']; ?>
			</a>
		</div>
	<?php endif; ?>
	
	<div class="taw-property-content">
		<h3 class="taw-property-title">
			<a href="<?php echo esc_url( $property['permalink'] ); ?>">
				<?php echo esc_html( $property['title'] ); ?>
			</a>
		</h3>
		
		<?php
		// Load property meta partial
		if ( isset( $module ) && $module ) {
			echo $module->load_template( 'property-meta.php', array( 'property' => $property, 'module' => $module ), '', 'partials' );
		}
		?>
	</div>
</div>

