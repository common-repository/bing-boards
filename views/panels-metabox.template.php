<div id="bing-panels-list">
	<?php

	foreach ( $panels as $panel ) {

		$this->_container->template_loader()->load_template( 'panels-metabox-single-panel.template.php',
			array(
				'panel'       => $panel,
				'post'        => $post,
				'media'       => $this->_container->data_wrangler()->get_panel_media( $panel->ID ),
				'is_editable' => $is_editable
			) );
	}
	?>
</div>

<div class="bing-new-panel-button-container">
	<input type="button" class="button button-primary button-large" name="bing-new-panel-button" id="bing-new-panel-button" value="<?php _e( 'Add New Panel ', 'bing-boards' ); ?>&#x25BE;" />
	<ul class="bing-new-panel-options">
		<li><a href="#" id="new-panel-from-scratch"><?php _e( 'From Scratch', 'bing-boards' ); ?></a></li>
		<li><a href="#" id="new-panel-from-post"><?php _e( 'From a Post', 'bing-boards' ); ?></a></li>
	</ul>
</div>

<div class="panel-preloader"><div></div></div>