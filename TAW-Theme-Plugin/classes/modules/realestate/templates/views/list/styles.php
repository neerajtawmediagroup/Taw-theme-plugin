<?php
/**
 * List View Styles
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
.taw-property-list-view {
	display: flex;
	flex-direction: column;
	gap: 20px;
}
.taw-property-list-item {
	background: #ffffff;
	border: 1px solid #e5e5e5;
	border-radius: 8px;
	overflow: hidden;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
.taw-property-list-content {
	display: flex;
	gap: 20px;
	padding: 20px;
}
.taw-property-list-item .taw-property-photo {
	flex: 0 0 200px;
	overflow: hidden;
	border-radius: 4px;
}
.taw-property-list-item .taw-property-photo img {
	width: 100%;
	height: 150px;
	object-fit: cover;
	display: block;
}
.taw-property-details {
	flex: 1;
}
.taw-property-list-item .taw-property-title {
	margin: 0 0 12px 0;
	font-size: 20px;
	line-height: 1.4;
}
.taw-property-list-item .taw-property-title a {
	text-decoration: none;
	color: #1d2327;
}
.taw-property-list-item .taw-property-title a:hover {
	color: #2271b1;
}
.taw-property-excerpt {
	margin-top: 12px;
	color: #646970;
	line-height: 1.6;
}
@media (max-width: 768px) {
	.taw-property-list-content {
		flex-direction: column;
	}
	.taw-property-list-item .taw-property-photo {
		flex: 0 0 auto;
	}
	.taw-property-list-item .taw-property-photo img {
		height: auto;
	}
}
</style>

