<?php
/**
 * TAW Theme Dashboard Template
 */

// Current tab from URL.
$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';

// Base URL for this page.
$dashboard_url = admin_url( 'admin.php?page=taw-theme-builder' );
?>

<div class="taw-theme-dashboard-wrap">

	<!-- LEFT PANEL -->
	<aside class="taw-re-left-panel">
		<div class="taw-re-left-panel__inner">
			<ul class="taw-re-left-panel__menu">

				<!-- Dashboard -->
				<li class="taw-re-left-panel__item <?php echo ( 'dashboard' === $current_tab ) ? 'is-active' : ''; ?>">
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'dashboard', $dashboard_url ) ); ?>">
						<span class="dashicons dashicons-dashboard taw-re-left-panel__icon"></span>
						<span class="taw-re-left-panel__text">
							<?php esc_html_e( 'Dashboard', 'taw-theme' ); ?>
						</span>
					</a>
				</li>

				<!-- Real Estate (main row) -->
				<li class="taw-re-left-panel__item <?php echo ( in_array( $current_tab, array( 'real-estate', 'views', 'categories' ), true ) ) ? 'is-active' : ''; ?>">
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'real-estate', $dashboard_url ) ); ?>">
						<span class="dashicons dashicons-admin-multisite taw-re-left-panel__icon"></span>
						<span class="taw-re-left-panel__text">
							<?php esc_html_e( 'Real Estate', 'taw-theme' ); ?>
						</span>
					</a>
				</li>

				<!-- Real Estate sub‑items -->
				<li class="taw-re-left-panel__subitem <?php echo ( 'real-estate' === $current_tab ) ? 'is-active' : ''; ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=taw-properties-list' ) ); ?>">
						<?php esc_html_e( 'All Listing', 'taw-theme' ); ?>
					</a>
				</li>

				<!-- Add New Property (subitem) – USE NATIVE EDITOR -->
				<li class="taw-re-left-panel__subitem">
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=taw_property' ) ); ?>">
						<?php esc_html_e( 'Add New Property', 'taw-theme' ); ?>
					</a>
				</li>

				<li class="taw-re-left-panel__subitem <?php echo ( 'views' === $current_tab ) ? 'is-active' : ''; ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=taw-views-list' ) ); ?>">
						<?php esc_html_e( 'Manage View', 'taw-theme' ); ?>
					</a>
				</li>
				<li class="taw-re-left-panel__subitem <?php echo ( 'categories' === $current_tab ) ? 'is-active' : ''; ?>">
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'categories', $dashboard_url ) ); ?>">
						<?php esc_html_e( 'Categories', 'taw-theme' ); ?>
					</a>
				</li>
			</ul>
		</div>
	</aside>

	<!-- MAIN DASHBOARD CONTENT (right side) -->
	<main class="taw-theme-dashboard-main">
		<?php
		switch ( $current_tab ) {

			/*
			 * REAL ESTATE TAB
			 * Shows a summary table of the latest properties
			 */
			case 'real-estate':
				?>
				<h1><?php esc_html_e( 'Real Estate – All Listings (summary)', 'taw-theme' ); ?></h1>
				<?php
				// Show latest 10 properties.
				$props = new WP_Query(
					array(
						'post_type'      => 'taw_property',
						'posts_per_page' => 10,
						'post_status'    => 'publish',
						'orderby'        => 'date',
						'order'          => 'DESC',
					)
				);

				if ( $props->have_posts() ) :
					?>
					<table class="widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'taw-theme' ); ?></th>
								<th><?php esc_html_e( 'Categories', 'taw-theme' ); ?></th>
								<th><?php esc_html_e( 'Date', 'taw-theme' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							while ( $props->have_posts() ) :
								$props->the_post();
								$post_id = get_the_ID();
								$terms   = get_the_terms( $post_id, 'taw_property_category' );
								$cats    = array();

								if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                                    foreach ( $terms as $t ) {
                                        $cats[] = $t->name;
                                    }
                                }
								?>
								<tr>
									<td>
										<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>">
											<?php echo esc_html( get_the_title() ); ?>
										</a>
									</td>
									<td><?php echo ! empty( $cats ) ? esc_html( implode( ', ', $cats ) ) : '—'; ?></td>
									<td><?php echo esc_html( get_the_date() . ' ' . get_the_time() ); ?></td>
								</tr>
							<?php endwhile; wp_reset_postdata(); ?>
						</tbody>
					</table>

					<p style="margin-top:15px;">
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=taw-properties-list' ) ); ?>">
							<?php esc_html_e( 'Open Full Listings Page', 'taw-theme' ); ?>
						</a>
						<!-- Add New Property -> NATIVE EDITOR -->
						<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=taw_property' ) ); ?>" style="margin-left:8px;">
							<?php esc_html_e( 'Add New Property', 'taw-theme' ); ?>
						</a>
					</p>

				<?php else : ?>
					<p><?php esc_html_e( 'No properties found.', 'taw-theme' ); ?></p>
					<p>
						<!-- Add First Property -> NATIVE EDITOR -->
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=taw_property' ) ); ?>">
							<?php esc_html_e( 'Add First Property', 'taw-theme' ); ?>
						</a>
					</p>
				<?php
				endif;
				break;

			/*
			 * MANAGE VIEW TAB
			 * Shows the taw_prop_shortcode custom post type in a table
			 */
			case 'views':
				?>
				<h1><?php esc_html_e( 'Property Views', 'taw-theme' ); ?></h1>
				<p><?php esc_html_e( 'Manage shortcode views for displaying properties.', 'taw-theme' ); ?></p>

				<?php
				$views_query = new WP_Query(
					array(
						'post_type'      => 'taw_prop_shortcode',
						'posts_per_page' => 20,
						'post_status'    => 'publish',
						'orderby'        => 'date',
						'order'          => 'DESC',
					)
				);

				if ( $views_query->have_posts() ) :
					?>
					<table class="widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'taw-theme' ); ?></th>
								<th><?php esc_html_e( 'Shortcode', 'taw-theme' ); ?></th>
								<th><?php esc_html_e( 'Date', 'taw-theme' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							while ( $views_query->have_posts() ) :
								$views_query->the_post();
								$post_id = get_the_ID();
								?>
								<tr>
									<td>
										<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>">
											<?php echo esc_html( get_the_title() ); ?>
										</a>
									</td>
									<td>
										<code>[taw_property_view id="<?php echo esc_attr( $post_id ); ?>"]</code>
									</td>
									<td><?php echo esc_html( get_the_date() . ' ' . get_the_time() ); ?></td>
								</tr>
							<?php endwhile; wp_reset_postdata(); ?>
						</tbody>
					</table>

					<p style="margin-top:15px;">
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=taw_prop_shortcode' ) ); ?>">
							<?php esc_html_e( 'Add New View', 'taw-theme' ); ?>
						</a>
						<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=taw_prop_shortcode' ) ); ?>" style="margin-left:8px;">
							<?php esc_html_e( 'Open Full Views Page', 'taw-theme' ); ?>
						</a>
					</p>

				<?php else : ?>
					<p><?php esc_html_e( 'No views found.', 'taw-theme' ); ?></p>
					<p>
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=taw_prop_shortcode' ) ); ?>">
							<?php esc_html_e( 'Create First View', 'taw-theme' ); ?>
						</a>
					</p>
				<?php
				endif;
				break;

			/*
			 * CATEGORIES TAB
			 * Shows taw_property_category terms in a table
			 */
		/*
 * CATEGORIES TAB
 * Load custom categories template
 */
case 'categories':
    ?>
    <h1><?php esc_html_e( 'Property Categories', 'taw-theme' ); ?></h1>
    <?php

    // Ensure the constant exists
    if ( ! defined( 'TAW_THEME_PLUGIN_DIR' ) ) {
        echo '<div class="error"><p><strong>TAW_THEME_PLUGIN_DIR is not defined!</strong></p></div>';
        break;
    }

    // Correct path to your template
    $template = TAW_THEME_PLUGIN_DIR . 'classes/modules/realestate/templates/admin/taw-Categories-list.php';

    if ( file_exists( $template ) ) {
        include $template;
    } else {
        echo '<div class="error"><p>'
             . esc_html__( 'Categories template not found at: ', 'taw-theme' )
             . '<code>' . esc_html( $template ) . '</code>'
             . '</p></div>';
    }

    break;


			/*
			 * DASHBOARD TAB (default)
			 */
			case 'dashboard':
			default:
				?>
				<h1><?php esc_html_e( 'TAW Theme Dashboard', 'taw-theme' ); ?></h1>
				<p><?php esc_html_e( 'This is your custom dashboard template for TAW Theme.', 'taw-theme' ); ?></p>

				<div class="taw-dashboard-grid">
					<div class="taw-dashboard-card">
						<h2><?php esc_html_e( 'Real Estate', 'taw-theme' ); ?></h2>
						<p><?php esc_html_e( 'Manage property listings and views.', 'taw-theme' ); ?></p>
						<p>
							<a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'tab', 'real-estate', $dashboard_url ) ); ?>">
								<?php esc_html_e( 'View Listings Summary', 'taw-theme' ); ?>
							</a>
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=taw-properties-list' ) ); ?>">
								<?php esc_html_e( 'Open Full Listings', 'taw-theme' ); ?>
							</a>
							<!-- Add New Property -> NATIVE EDITOR -->
							<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=taw_property' ) ); ?>" style="margin-left:8px;">
								<?php esc_html_e( 'Add New Property', 'taw-theme' ); ?>
							</a>
						</p>
					</div>

					<div class="taw-dashboard-card">
						<h2><?php esc_html_e( 'Views', 'taw-theme' ); ?></h2>
						<p><?php esc_html_e( 'Manage property shortcode views.', 'taw-theme' ); ?></p>
						<p>
							<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=taw-views-list' ) ); ?>">
								<?php esc_html_e( 'View Shortcode List', 'taw-theme' ); ?>
							</a>
						</p>
					</div>

					<div class="taw-dashboard-card">
						<h2><?php esc_html_e( 'Categories nj', 'taw-theme' ); ?></h2>
						<p><?php esc_html_e( 'Overview of property categories.', 'taw-theme' ); ?></p>
						<p>
							<a class="button button-primary" href="<?php echo esc_url( add_query_arg( 'tab', 'categories', $dashboard_url ) ); ?>">
								<?php esc_html_e( 'View Categories', 'taw-theme' ); ?>
							</a>
						</p>
					</div>
				</div>
				<?php
				break;
		}
		?>
	</main>

</div>


