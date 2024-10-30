<div id="bing-live-editor">
	<div class="topbar">
		<div class="alignleft">
			<span id="bing-live-editor-board-title"></span>
		</div>
		<div class="alignright">
			<span id="bing-last-modified"></span>
			<input type="button" class="button button-primary" disabled id="bing-live-editor-save" value="<?php _e( 'Save' ); ?>">
			<a href="#" class="live-editor-close"><span class="icon-cancel-circle"></span></a>
		</div>
	</div>
	<div class="panel-left">
		<div class="panel-content">
			<input type="hidden" name="panel-id" id="panel-id" value="" class="bing-serialize-me"/>
			<div class="panel-inputs">
				<div class="bing-title">
					<input type="text" name="panel-title" id="panel-title" placeholder="<?php _e( 'Panel Title', 'bing-boards' ) ?>" class="bing-serialize-me"/>
					<span id="bing-title-count" class="character-warn"></span>
				</div>
				<div class="bing-content">
					<textarea class="bing-serialize-me" name="panel-content" id="panel-content" placeholder="<?php echo sprintf( __( 'Write your panel description here. It should be conversational in tone, engaging and tie in to the larger narrative of the board. Keep the description under %d characters.', 'bing-boards' ), $body_max_length ); ?>" data-pl="<?php echo sprintf( __( 'Write your panel description here. It should be conversational in tone, engaging and tie in to the larger narrative of the board. Keep the description under %d characters.', 'bing-boards' ), $body_max_length ); ?>"></textarea>
					<span id="bing-content-count" class="character-warn"></span>
				</div>
				<a href="#" id="panel-link-edit" class="no-link"><?php _e( 'Click here to add a link for this panel >', 'bing-boards' ) ?></a>
			</div>
			<input type="hidden" name="panel-link-url" id="panel-link-url" class="bing-serialize-me"/>
			<input type="hidden" name="panel-link-anchor" id="panel-link-anchor" class="bing-serialize-me"/>
		</div>
		<div class="panel-scrapper">
			<p class="scraper-heading"><?php _e( 'Search for and choose a post.', 'bing-boards' ); ?></p>
			<label id="search-label"><?php _e( "Search" ); ?></label>
			<input type="text" name="panel-scrapper-search" id="panel-scrapper-search"/>
			<p class="description" id="panel-no-search"></p>
			<div id="posts-wrapper">
				<ul id="posts-for-scrapping">
					<li></li>
				</ul>
			</div>
		</div>
		<?php if ( ! empty( $user_data ) ): ?>
			<div class="panel-author">
				<div id="post-author" class="group">
					<div class="panel-author-thumb">
						<img src="<?php echo esc_url( $user_data['photourl'] ) ?>" alt="author" />
					</div>
					<div class="panel-author-meta">
					<span class="panel-author-byline"><?php _e( 'by', 'bing-boards' ); ?>
						<a href="<?php echo esc_url( $user_data['homepageurl'] ) ?>" target="_blank"><?php echo esc_html( $user_data['name'] ) ?></a></span>
						<span class="panel-author-about"><?php echo esc_html( $user_data['title'] ) ?></span>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<div class="panel-slider-wrap">
			<a href="#" id="panel-slider-left" class="slider-nav icon-arrow-left"></a>
			<div id="panel-slider">
				<ul>
					<li></li>
				</ul>
			</div>
			<a href="#" id="panel-slider-right" class="slider-nav icon-arrow-right"></a>
		</div>
	</div>
	<div class="panel-media">
		<div>
			<div id="panel-image-wrap"></div>
			<input type="hidden" name="thumb-id" id="thumb-id" value="" class="bing-serialize-me"/>
			<input type="hidden" name="thumb-embed" id="thumb-embed" value="" class="bing-serialize-me"/>
			<div class="add-media wp-media-buttons">
				<a href="#" class="button add_media" id="bing-insert-media" title="Add Media"><span class="wp-media-buttons-icon"></span><?php _e( 'Add Media' ); ?>
				</a>
				<p class="while-editting"><?php _e( 'Select a nice high resolution image file or video link. Images look their best at the following dimensions: 440px x 510px.', 'bing-boards' ) ?></p>
				<p class="while-scrapping"><?php _e( 'Select a post, then choose media.', 'bing-boards' ) ?></p>
			</div>
		</div>
	</div>
	<div class="clear"></div>
</div>

<div id="bing-link-picker">
	<div class="blp-header">
		<?php _e( 'Insert/Edit Link', 'bing-boards' ) ?>
		<a id="bing-close-le" href="#"><span class="icon-cancel-circle"></span></a>
	</div>
	<div class="blp-manual">
		<span class="blp-notice"><?php _e( "Enter the destination url.", 'bing-boards' ); ?></span>
		<label id="search-label"><?php _e( "URL", 'bing-boards' ); ?></label>
		<input type="text" class="blp-input" name="blp-url" id="blp-url"/>
		<div class="clear"></div>
		<label id="search-label"><?php _e( "Title", 'bing-boards' ); ?></label>
		<div class="bing-link-title">
			<input type="text" class="blp-input" name="blp-title" id="blp-title"/>
			<span id="bing-link-count" class="character-warn"></span>
		</div>
		<div class="blp-posts-submit">
			<input type="button" class="button button-primary" id="bing-link-insert" value="<?php _e( 'Insert Link' ); ?>">
		</div>
	</div>
	<div class="blp-post">
		<span class="blp-notice"><?php _e( "Or link to existing content.", 'bing-boards' ); ?></span>
		<label id="search-label"><?php _e( "Search", 'bing-boards' ); ?></label>
		<input type="text" name="blp-search" class="blp-input" id="blp-search"/>
	</div>
	<div id="blp-posts-wrapper">
		<ul id="blp-posts">
			<li></li>
		</ul>
	</div>
</div>
