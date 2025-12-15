<?php
/**
 * List View Item Template
 *
 * @var array $property Property data array
 * @var object $module Module instance
 *
 * @package TAW_Theme
 * @subpackage RealEstate
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="taw-property-item taw-property-list-item nj">
	<div class="taw-property-list-content">
		<?php if ( ! empty( $property['thumbnail'] ) ) : ?>
			<div class="taw-property-photo">
				<a href="<?php echo esc_url( $property['permalink'] ); ?>">
					<?php echo $property['thumbnail']; ?>
				</a>
			</div>
		<?php endif; ?>
		
		<div class="taw-property-details">
			<h3 class="taw-property-title">
				<a href="<?php echo esc_url( $property['permalink'] ); ?>">
					<?php echo esc_html( $property['title'] ); ?>
				</a>
			</h3>
			
			<?php
			// Load property meta partial
			if ( $module ) {
				echo $module->load_template( 'property-meta.php', array( 'property' => $property ), '', 'partials' );
			}
			?>
			
			<?php if ( ! empty( $property['excerpt'] ) ) : ?>
				<div class="taw-property-excerpt">
					<?php echo wp_kses_post( $property['excerpt'] ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

