<?php if ( !empty( $key_error ) ): ?>
	<div id="message" class="error">
		<p><?php echo $key_error; ?></p>
	</div>
<?php endif; ?>

<style>
	.bing-about .bing-section {
		margin-bottom: 15px;
		margin-top: 15px;
		border-bottom: 1px solid #eee;
		padding-bottom: 15px;
	}
	.bing-about .bing-section:last-child {
		border: none;
	}

</style>

<div class="wrap">

	<div class="bing-about">

		<div class="bing-section">

			<h2><?php _e( "Get started with Bing Boards", "bing-boards" ); ?></h2>

			<p>If you are new to Bing Boards, request an invitation at <a href="http://www.bing.com/boards#emailSignup" target="_blank">http://www.bing.com/boards</a>.  Bing will review your request and email confirmation and instructions on how to get your key within a few days.</p>

			<p><form method="post">
				<label for="bing_key">Enter your bing key here:</label>
				<input type="text" name="bing_key" value="<?php echo esc_attr( $current_user_key ); ?>">
				<input type="submit" value="<?php _e( 'Save' ); ?>">
			</form></p>

		</div>

		<div class="bing-section">

			<h2>How it works</h2>

			<p>We're looking for unique, visual content that has a point of view. Bing Boards must have rich images and descriptive text that's sourced from your blog.</p>

			<p>Once you've submitted a board, a Bing editor will take a look to ensure your board is right for Bing. If we have any questions, we'll contact you. Once your board is good to go, we'll add relevant keywords so that Bing users can find your board when they search.</p>

			<p>In addition to keywords related to your board, we'll also add keywords for your blog. That way, if a Bing user searches for you or your blog by name, they'll find your board, too.</p>

		</div>

		<div class="bing-section">

			<h2>Bing Boards guidelines</h2>

			<h3>Share your point of view</h3>

			<p>Make it personal! Let your passion for the topic speak for itself. That means a real person writes these boards. Real identities only.</p>

			<h3>Make it visual</h3>

			<p>Great boards need great images. Say it with pictures. No stock photography.</p>

			<h3>Keep it clean</h3>

			<p>Nothing salacious, slanderous, or explicitly sexual in nature.</p>

			<h3>Give credit</h3>

			<p>It's OK to share things you've found around the web, but credit the person who made it.</p>

			<h3>These aren't ads</h3>

			<p>If someone paid you to create content for them, it doesn't belong here. That includes sponsored content.</p>

			<p><strong>Thanks for using the WordPress plugin for Bing Boards!</strong></p>

			<p>More questions? Email <a href="mailto:yourteam@bing.com">yourteam@bing.com</a> at any time for help.</p>

		</div>

	</div>


</div>
