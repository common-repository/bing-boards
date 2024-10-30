<div class="submitbox" id="submitpost">

	<div id="minor-publishing">

		<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
		<div style="display:none;">
			<?php submit_button( __( 'Save' ), 'button', 'save' ); ?>
		</div>
		<?php if ( $is_editable ): ?>
		<div id="minor-publishing-actions">
			<div id="save-action">
				<input type="submit" name="save" id="save-post" value="<?php esc_attr_e( 'Save Draft' ); ?>" class="button" />
				<span class="spinner"></span>
			</div>
		</div>
		<?php endif; ?>

		<div class="clear"></div>
		<div id="misc-publishing-actions">
			<div class="misc-pub-section">
				<label for="post_status"><?php _e( 'Status:' ) ?></label>
				<span id="post-status-display"><?php echo esc_html( $status );?></span>
			</div>
			<div class="clear"></div>
			<?php
			$datef = __( 'M j, Y @ G:i' );
			$stamp = __( 'Updated on: <b>%1$s</b>', 'bing-boards' );
			$date  = date_i18n( $datef, strtotime( $post->post_date ) );
			?>
			<div class="misc-pub-section curtime">
			<span id="timestamp">
				<?php printf( $stamp, $date ); ?>
			</span>
			</div>
		</div>
	</div>

	<div id="major-publishing-actions">
		<div id="delete-action">
			<?php
			if ( $can_delete ) {
				if ( ! EMPTY_TRASH_DAYS )
					$delete_text = __( 'Delete Permanently' );
				else
					$delete_text = __( 'Move to Trash' );
				?>
				<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?>"><?php echo $delete_text; ?></a><?php
			} ?>
		</div>

		<div id="publishing-action">
			<span class="spinner"></span>
			<?php if ( $can_publish ) : ?>
				<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish' ) ?>" />
				<?php submit_button( $submit_text, 'primary button-large can-api', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
				<p class="bing-error no-can-api"><?php _e( "You can't submit this panel because the selected author doesn't have a Bing User Key. Add it the user profile.", 'bing-boards' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="clear"></div>
	</div>
</div>
