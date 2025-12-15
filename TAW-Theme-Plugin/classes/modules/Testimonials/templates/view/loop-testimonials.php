<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $query ) || ! $query->have_posts() ) {
	echo '<p>' . esc_html__( 'No testimonials found.', 'taw-theme' ) . '</p>';
	return;
}
?>

<div class="taw-testimonials taw-testimonials-grid">
	<?php while ( $query->have_posts() ) : $query->the_post(); ?>
		<article <?php post_class( 'taw-testimonial-card' ); ?>>

			<?php
			if ( has_post_thumbnail() ) :
				echo '<div class="taw-testimonial-thumb">';
				the_post_thumbnail( 'thumbnail' );
				echo '</div>';
			else :
				// DEBUG: show clearly that WP thinks there is no thumbnail
				echo '<div class="taw-testimonial-thumb taw-no-thumb">';
				echo '<strong>NO THUMB:</strong> ' . esc_html( get_the_title() );
				echo '</div>';
			endif;
			?>

			<div class="taw-testimonial-body">
				<h3 class="taw-testimonial-title"><?php the_title(); ?></h3>
				<div class="taw-testimonial-content">
					<?php the_content(); ?>
				</div>
			</div>

		</article>
	<?php endwhile; ?>
</div>