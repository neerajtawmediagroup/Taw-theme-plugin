<?php
/**
 * Property Meta Partial
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
<?php if ( $property['type'] || $property['area'] || $property['price'] ) : ?>
	<p class="taw-property-meta">
		<?php if ( $property['type'] ) : ?>
			<span class="taw-property-type"><?php echo esc_html( $property['type'] ); ?></span>
		<?php endif; ?>
		<?php if ( $property['area'] ) : ?>
			<span class="taw-property-area"><?php echo esc_html( $property['area'] . ' ' . $property['area_unit'] ); ?></span>
		<?php endif; ?>
		<?php if ( $property['price'] ) : ?>
			<span class="taw-property-price"><?php echo esc_html( $property['price'] ); ?></span>
		<?php endif; ?>
	</p>
<?php endif; ?>

<?php if ( $property['bedrooms'] || $property['bathrooms'] ) : ?>
	<p class="taw-property-rooms">
		<?php if ( $property['bedrooms'] ) : ?>
			<span class="taw-property-bedrooms"><?php printf( esc_html__( '%s Bed', 'taw-theme' ), esc_html( $property['bedrooms'] ) ); ?></span>
		<?php endif; ?>
		<?php if ( $property['bathrooms'] ) : ?>
			<span class="taw-property-bathrooms"><?php printf( esc_html__( '%s Bath', 'taw-theme' ), esc_html( $property['bathrooms'] ) ); ?></span>
		<?php endif; ?>
	</p>
<?php endif; ?>

<?php if ( $property['address'] ) : ?>
	<p class="taw-property-address"><?php echo esc_html( $property['address'] ); ?></p>
<?php endif; ?>

