<?php

$error = '';

$api = $this->_container->api();
$da = $this->_container->data_architecture();

$panel_title =  html_entity_decode( $panel->post_title );
// If panel title is bigger than allowed
if ( strlen( $panel_title ) > $api->PANEL_TITLE_MAX_LENGTH || empty( $panel_title ) )
	$error = 'panel-error';

$panel_title = ! empty( $panel_title ) ? $panel_title : __( 'Untitled', 'bing-boards' );


// If link title is bigger than allowed
$link_title = html_entity_decode( get_post_meta( $panel->ID, $da->LINK_ANCHOR_META, true ) );
if ( empty( $link_title ) || strlen( $link_title ) > $api->LINK_TITLE_MAX_LENGTH )
	$error = 'panel-error';

$panel_content = html_entity_decode( $panel->post_excerpt );
// If body is bigger than allowed
if ( empty( $panel_content ) || strlen( $panel_content ) > $api->BODY_MAX_LENGTH )
	$error = 'panel-error';

if ( empty( $media['thumbnail'] ) )
	$error = 'panel-error';

?>
<div class="bing-panel <?php echo $error;?>" id="bing-panel-<?php echo $panel->ID; ?>">
	<span class="icon icon-menu"></span>
	<div class="thumbnail" data-thumb="<?php echo esc_attr( $media['thumbnail'] ); ?>" data-id="<?php echo $panel->ID; ?>">
		<?php if ( ! empty( $media['thumbnail_id'] ) ): ?>
			<?php echo get_the_post_thumbnail( $panel->ID, 'thumbnail' ); ?>
		<?php elseif ( ! empty( $media['thumbnail'] ) ): ?>
			<img width="120" src='<?php echo esc_url( $media['thumbnail'] ); ?>'>
		<?php endif; ?>
	</div>

	<div class="content">
		<a href="#" class="bing-panel-permalink" data-id="<?php echo $panel->ID; ?>"><?php echo esc_html( $panel_title ); ?></a>

		<p><?php echo esc_html( $panel_content ); ?></p>
	</div>
	<span class="icon icon-close" data-id="<?php echo $panel->ID; ?>"></span>
	<div class="clear"></div>
</div>
