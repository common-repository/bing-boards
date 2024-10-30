<?php
if ( $can_publish ):
	
	for ( $i = 0; $i < $search_term_count; $i ++ ) {
		$term = ! empty( $search_terms[$i] ) ? $search_terms[$i] : '';
		echo sprintf( '%d. <input type="text" name="bing_search_terms[]" value="%s" class=" bing-search-term" /><br/>', $i+1, $term );
	}

?>



<?php else: ?>

	<p><?php echo implode( ", ", $search_terms ); ?></p>

<?php endif; ?>

<p class="description"><?php _e( 'Insert up to 5 search terms.', 'bing-boards' ); ?></p>