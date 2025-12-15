<?php
/**
 * Grid View Styles
 *
 * @var int $columns Number of columns
 *
 * @package TAW_Theme
 * @subpackage RealEstate
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style>
.taw-property-grid {
	display: grid;
	gap: 24px;
	grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr);
}
@media (max-width: 1024px) {
	.taw-property-grid {
		grid-template-columns: repeat(2, 1fr);
	}
}
@media (max-width: 768px) {
	.taw-property-grid {
		grid-template-columns: 1fr;
	}
}
.taw-property-item {
	background: #ffffff;
	border: 1px solid #e5e5e5;
	border-radius: 8px;
	overflow: hidden;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
	transition: box-shadow 0.3s ease;
}
.taw-property-item:hover {
	box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.taw-property-photo {
	width: 100%;
	overflow: hidden;
}
.taw-property-photo img {
	width: 100%;
	height: auto;
	display: block;
}
.taw-property-content {
	padding: 16px;
}
.taw-property-title {
	margin: 0 0 12px 0;
	font-size: 18px;
	line-height: 1.4;
}
.taw-property-title a {
	text-decoration: none;
	color: #1d2327;
}
.taw-property-title a:hover {
	color: #2271b1;
}
.taw-property-meta {
	margin: 0 0 8px 0;
	font-size: 14px;
	color: #646970;
}
.taw-property-meta span {
	margin-right: 12px;
}
.taw-property-rooms {
	margin: 0 0 8px 0;
	font-size: 14px;
	color: #646970;
}
.taw-property-rooms span {
	margin-right: 12px;
}
.taw-property-address {
	margin: 0;
	font-size: 13px;
	color: #8c8f94;
}
</style>

