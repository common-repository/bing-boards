/**
 * @file The custom scripts file for Bing Boards.
 * This file must load after all vendor dependencies.
 * @version 1.0
 */

(function (window, $, undefined) {

	$(document).ready(function () {

		/**
		 * @since 1.0
		 * @desc This object stores all css selector strings used to interact with the live editor panel.
		 */

		var editor = {
			board_title : '#bing-live-editor-board-title',
			char_board  : '#bing-board-count',
			char_content: '#bing-content-count',
			char_title  : '#bing-title-count',
			char_link   : '#bing-link-count',
			content     : '#panel-content',
			id          : '#bing-live-editor',
			image       : '#panel-image',
			image_wrap  : '#panel-image-wrap',
			inputs      : '.panel-inputs',
			insert      : '#bing-insert-media',
			last_mod    : '#bing-last-modified',
			link_edit   : '#panel-link-edit',
			link_kill   : '#panel-link-kill',
			link_picker : '#bing-link-picker',
			link_set    : '#panel-link-set',
			link_url    : '#panel-link-url',
			link_anchor : '#panel-link-anchor',
			panel_id    : '#panel-id',
			pick_search : '#blp-search',
			pick_title  : '#blp-title',
			pick_url    : '#blp-url',
			post_id     : '#post_ID',
			save        : '#bing-live-editor-save',
			serialize   : '.bing-serialize-me',
			slider      : '#panel-slider',
			thumb_embed : '#thumb-embed',
			thumb_id    : '#thumb-id',
			title       : '#panel-title'
		};

		/**
		 * @since 1.0
		 * @desc This object groups together the Bing Boards plugin.
		 */

		var bb = {

			/**
			 * @namespace bb.data
			 * @since 1.0
			 * @desc This object groups together character limits, browser/application state and other data used in the Bing Boards plugin.
			 */

			data: {
				char_limit_content   : BingBoards.body_char_limit,
				char_limit_title     : BingBoards.panel_title_char_limit,
				char_limit_board     : BingBoards.board_title_char_limit,
				char_limit_link      : BingBoards.link_title_char_limit,
				char_min_search_terms: BingBoards.search_term_min_chars,
				char_max_search_terms: BingBoards.search_term_max_chars,
				panels_limit         : BingBoards.panels_limit,
				doing_ajax           : false,
				last_serialized      : [],
				panels               : [],
				v_height             : 0,
				v_width              : 0,
				user_has_bing_key    : BingBoards.user_has_bing_key,
				submit_errors        : BingBoards.submit_errors,
				first_save           : false
			},

			/**
			 * @namespace bb.el
			 * @since 1.0
			 * @desc This object groups together reused DOM elements as cached jQuery objects for use in the Bing Boards plugin.
			 */

			el : {
				body          : $("body"),
				editor        : $(editor.id).clone(),
				list          : $('#bing-panels-list'),
				new_panel     : $('.bing-new-panel-button-container'),
				overlay       : {},
				panel_options : $('.bing-new-panel-options'),
				title         : $('#title'),
				titlewrap     : $('#titlewrap'),
				submit		  : $('input.can-api'),
				nosubmit      : $('p.no-can-api')
			},

			/**
			 * @namespace bb.fn
			 * @since 1.0
			 * @desc This object groups together all custom functions used in the Bing Boards plugin.
			 */

			fn : {

				/**
				 * @function bb.fn.array_diff
				 * @since 1.0
				 * @desc bb.fn.array_diff is a javaScript equivalent of PHP's array_diff, which computes the difference of arrays.
				 * @param arr1 {Array} The arrays for comparison.
				 */

				array_diff : function (arr1) {
					var retArr = {},
						argl = arguments.length,
						k1 = '',
						i = 1,
						k = '',
						arr = {};

					arr1keys: for (k1 in arr1) {
						for (i = 1; i < argl; i++) {
							arr = arguments[i];
							for (k in arr) {
								if (arr[k] === arr1[k1]) {
									continue arr1keys;
								}
							}
							retArr[k1] = arr1[k1];
						}
					}

					return retArr;
				},

				/**
				 * @function bb.fn.ays
				 * @since 1.0
				 * @desc bb.fn.ays compares the live editors last saved state to the current state and prompts the user for confirmation if unsaved data is detected.
				 */

				ays : function () {
					var current = bb.fn.serialize(),
						diff = $.extend(bb.fn.array_diff(bb.data.last_serialized, current), bb.fn.array_diff(current, bb.data.last_serialized));

					if ($.isEmptyObject(diff))
						return true;

					return confirm(BingBoards.txt_ays)
				},

				/**
				 * @function bb.fn.can_api
				 * @since 1.0
				 * @desc bb.fn.can_api determines whether a board contains errors and either notifies as such or enables submission.
				 */

				can_api : function(){

					if ( !bb.data.first_save )
						return;

					var error = null,
						search_terms_inputs = $( "input.bing-search-term" ),
						panels_list = $( '.bing-panel'),
						title_val = bb.el.title.val();


					if (title_val == '')
						error = bb.data.submit_errors[0];

					if (bb.el.title.hasClass('bad'))
						error = bb.data.submit_errors[1];

					if (!bb.data.user_has_bing_key)
						error = bb.data.submit_errors[2];

					if ($('.panel-error').length)
						error = bb.data.submit_errors[3];

					if (!panels_list.length)
						error = bb.data.submit_errors[4];

					// Check that search terms are not empty
					var search_terms = $.map( search_terms_inputs ,function (val,i) { return $( val ).val(); } ).join('');
					if ($.trim(search_terms) == '')
						error = bb.data.submit_errors[5];

					search_terms = $.map( search_terms_inputs,function (val,i) { var s = $( val ).val(); if ( s.length && ( s.length < bb.data.char_min_search_terms || s.length > bb.data.char_max_search_terms ) ) return s; else return ''; } ).join('');
					if ($.trim(search_terms) != '')
						error = bb.data.submit_errors[6];

					if (panels_list.length > bb.data.panels_limit)
						error = bb.data.submit_errors[7];


					if (error !== null){
						bb.el.nosubmit.html(error);
						bb.el.nosubmit.show();
						bb.el.submit.attr('disabled', 'disabled');
						return false;
					}else{
						bb.el.submit.removeAttr('disabled');
						bb.el.nosubmit.hide();
						return true;
					}

				},

				/**
				 * @function bb.fn.carousel
				 * @since 1.0
				 * @desc bb.fn.carousel sets up the carousel in the live editor by harvesting the panels list, creating a ul from that data, injecting to the live editor and then kicking jcarousel on it.
				 */

				carousel : function () {

					bb.fn.get_panels();

					var $ul = $('#panel-slider ul');

					$ul.empty();

					$(bb.data.panels).each(function () {
						var li = '<li><a class="carousel-link cl-' + this.id + '" data-id="' + this.id + '" href="#" title="' + this.title + '">';

						if (this.img.length)
							li += '<img src="' + this.img + '" alt="thumb" />';
						li += '</a></li>';
						$ul.append(li);
					});

					$ul.append('' +
						'<li>' +
						'<a href="#" id="carousel-new-panel">' +
						'<span>' + BingBoards.txt_create_new_panel + '</span>' +
						'</a>' +
						'</li>'
					);

					$(editor.slider).jcarousel();

				},

				/**
				 * @function bb.fn.carousel_current
				 * @since 1.0
				 * @desc bb.fn.carousel_current sends us to the current item being edited in the live editors thumbnail carousel.
				 */

				carousel_current : function () {
					$(editor.slider).jcarousel('scroll', $(editor.slider + ' .current'), false);
				},

				/**
				 * @function bb.fn.carousel_nav
				 * @since 1.0
				 * @desc bb.fn.carousel_nav scrolls the carousel in either direction on carousel nav click.
				 * @param e {Object} The jQuery event object.
				 */

				carousel_nav : function (e) {
					e.preventDefault();
					var dir = '-';
					if (e.currentTarget.id == 'panel-slider-right')
						dir = '+';
					$(editor.slider).jcarousel('scroll', dir + '=1');
				},

				/**
				 * @function bb.fn.carousel_new
				 * @since 1.0
				 * @desc bb.fn.carousel_new creates a new carousel.
				 */

				carousel_new : function () {

					if (!bb.fn.ays())
						return;

					bb.fn.dialog();
					bb.fn.carousel();
					$(editor.id).addClass('from-scratch, first-run').show();
					bb.el.overlay.show();
					bb.fn.save_panel();
				},

				/**
				 * @function bb.fn.char_count
				 * @since 1.0
				 * @desc bb.fn.char_count handles adding warning/error classes to passed inputs on passed character count limits.
				 * @param $this {Object} Optional jquery object to use as the input for processing.
				 * @param counter {String} Optional css selector string to specify as the display counter. Required if $this is passed.
				 * @param limit {Number} Optional limit as number. Required if $this is passed.
				 */

				char_count : function ($this, counter, limit) {

					if($this){
						var $counter = $(counter),
							count = $this.val().length,
							remaining = limit - count;

						$counter.show();

						if(count > limit){
							$counter
								.text(remaining)
								.removeClass('good')
								.addClass('bad');
							$this
								.addClass('bad');
						} else {
							$counter
								.text(remaining)
								.removeClass('bad')
								.addClass('good');
							$this
								.removeClass('bad');
						}

					} else {
						var $title = $(editor.title),
							$content = $(editor.content),
							$link_title_input = $(editor.pick_title),
							$link_title = $(editor.link_edit),
							$board_title = bb.el.title;

						if($board_title.val().length > bb.data.char_limit_board)
							$board_title.addClass('bad');
						else
							$board_title.removeClass('bad');


						if($title.val().length > bb.data.char_limit_title){
							$title.addClass('bad');
						}

						if($content.val().length > bb.data.char_limit_content)
							$content.addClass('bad');

						if($link_title_input.length && $link_title_input.val().length > bb.data.char_limit_link)
							$link_title_input.addClass('bad');
						else if($link_title_input.length)
							$link_title_input.removeClass('bad');

						if($link_title.length && $link_title.text().length >  bb.data.char_limit_link)
							$link_title.addClass('bad');
						else if($link_title.length)
							$link_title.removeClass('bad');

					}
					bb.fn.validate_empty();
					bb.fn.can_api();
				},

				/**
				 * @function bb.fn.char_hide
				 * @since 1.0
				 * @desc bb.fn.char_hide hides the passed character count display.
				 * @param counter {String} Css selector string to specify the counter to hide.
				 */

				char_hide : function (counter) {

					$(counter).hide();

				},

				/**
				 * @function bb.fn.close_editor
				 * @since 1.0
				 * @desc bb.fn.close_editor handles closing the live editor.
				 */

				close_editor : function () {
					if (bb.fn.ays()) {
						bb.fn.hide_link_picker(false);
						$(editor.id).hide();
						bb.el.overlay.hide();
					}
				},

				/**
				 * @function bb.fn.close_frame
				 * @since 1.0
				 * @desc bb.fn.close_frame handles closing the custom wp media editor used by the live editor.
				 */

				close_frame : function () {

					var media,
						$thumb_id = $(editor.thumb_id);

					switch (bb.frame.state().get('id')) {
						case 'insert':
							media = bb.frame.state().get('selection').first();

							if (!media) return;

							media = media.toJSON();

							$thumb_id.val(media.id);

							var new_img = $('<img>' ).attr('src', media.url);
							$(editor.image_wrap).empty().append(new_img);

							break;
						case 'embed':
							media = bb.frame.state().props.toJSON();
							if (!media) return;

							if (!media.embed_html && !media.bad_url) {
								$thumb_id.val(media.url);
								var new_img = $('<img>' ).attr('src', media.url);
								$(editor.image_wrap).empty().append(new_img);
							} else if (!media.bad_url) {
								var $panel_title = $(editor.title),
									$panel_desc = $(editor.content),
									$panel_link_test = $(editor.link_kill);

								$(editor.image_wrap).empty().append(media.embed_html);
								$(editor.thumb_embed).val(media.embed_html);
								$thumb_id.val(media.thumb_url);

								if (!$panel_title.val().length)
									$panel_title.val(media.embed_title);

								if (!$panel_desc.val().length)
									$panel_desc.val(media.embed_desc);

								if (!$panel_link_test.length) {
									$(editor.link_url).val(media.embed_url);
									$(editor.link_anchor).val(media.embed_title);
									$(editor.link_edit).text(bb.fn.html_decode(media.embed_title)).removeClass('no-link');
									$(editor.inputs).append('<span id="panel-link-kill" class="icon-close"></span>');
								}

							}

							break;
					}

					if (!media) return;

					$(editor.id).addClass('has-image');
					$(editor.insert).text(BingBoards.txt_replace_image);

					bb.fn.save_panel();
				},

				/**
				 * @function bb.fn.content_update
				 * @since 1.0
				 * @desc bb.fn.content_update handles resizing and char counting for the textarea of the live editor.
				 * @param $this {Object} The jquery object of the textarea.
				 */

				content_update : function ($this) {
					if(bb.tests.isie10()){
						if($this.val() == $this.data('pl'))
							$this.val('')
					}
					bb.fn.resize_textarea($this);
					bb.fn.char_count($this, editor.char_content, bb.data.char_limit_content);
				},

				/**
				 * @function bb.fn.create_panel
				 * @since 1.0
				 * @desc bb.fn.create_panel sets up the live editor for the type of usage the user has requested, either from scratch or from post.
				 * @param e {Object} The jQuery event object.
				 */

				create_panel : function (e) {
					e.preventDefault();

					var type = 'from-post';

					if (e.currentTarget.id == 'new-panel-from-scratch')
						type = 'from-scratch';

					bb.fn.dialog();
					$(editor.id).addClass(type).show();
					bb.el.overlay.show();

					if (type == 'from-scratch') {
						bb.fn.save_panel();
						$(editor.id).addClass('first-run');
						$(editor.char_link).text('').removeClass('bad good');
					} else {
						bb.fn.snapshot();
						$(editor.board_title).text(BingBoards.txt_new_panel_from_post);
						bb.fn.carousel();
						bb.fn.post_scrape_search();
						$(editor.id).addClass('first-run');
					}
					bb.el.panel_options.toggle();
				},

				/**
				 * @function bb.fn.delete_panel
				 * @since 1.0
				 * @desc bb.fn.delete_panel performs an ajax call to delete the passed panel.
				 * @param panel_id {Number} The integer id of the panel to delete.
				 */

				delete_panel : function (panel_id) {

					if (confirm(BingBoards.txt_ays_delete)) {
						bb.fn.show_overlay();
						$.post(
							ajaxurl,
							{
								'action'   : 'bing-delete-panel',
								'panel_id' : panel_id,
								'nonce'    : BingBoards.delete_panel_nonce
							},
							function (response) {
								if (response.success) {
									bb.fn.hide_overlay();
									$('#bing-panel-' + panel_id).remove();
									bb.fn.can_api();
								} else {
									bb.fn.hide_overlay();
									alert(response.message);
								}
							}
						);
					}
				},

				/**
				 * @function bb.fn.dialog
				 * @since 1.0
				 * @desc bb.fn.dialog sets up a fresh live editor.
				 */

				dialog : function () {
					$(editor.id).remove();
					bb.el.body.append(bb.el.editor.clone());
					$(editor.board_title).text($('#title').val());
					bb.fn.legacy();
				},

				/**
				 * @function bb.fn.disable_save
				 * @since 1.0
				 * @desc bb.fn.disable_save locks the live editor save button.
				 */

				disable_save : function () {
					$(editor.save).attr("disabled", "disabled");
				},

				/**
				 * @function bb.fn.enable_save
				 * @since 1.0
				 * @desc bb.fn.enable_save enables the live editor save button.
				 */

				enable_save : function () {
					$(editor.save).removeAttr('disabled');
				},

				/**
				 * @function bb.fn.get_panels
				 * @since 1.0
				 * @desc bb.fn.get_panels harvests the panel list and creates an array for use in carousel creation.
				 */

				get_panels : function () {

					bb.data.panels = [];

					$('.bing-panel').each(function () {
						var panel = {},
							$this = $(this),
							$title_link = $this.find('.bing-panel-permalink'),
							title = $title_link.text(),
							id = $title_link.data('id'),
							img = '';

						if ($this.find('.thumbnail').length)
							img = $this.find('.thumbnail').data('thumb');

						panel['id'] = id;
						panel['title'] = title;
						panel['img'] = img;

						bb.data.panels.push(panel);
					});
				},

				/**
				 * @function bb.fn.get_panels
				 * @since 1.0
				 * @desc bb.fn.get_panels harvests the panel list and creates an array for use in carousel creation.
				 * @param $this {Object} the frame object
				 * @param attr {Object} the scan attributes
				 */

				get_embed : function ($this, attr) {

					var state = $this,
						url = $this.props.get('url'),
						urlc = url.toLowerCase(),
						deferred = $.Deferred(),
						input = $( '.media-frame .embed-url input' ),
						embed = $( 'div.embed-link-settings' );

					if ( !( urlc.indexOf( "youtube" ) >= 0 || urlc.indexOf( "youtu.be" ) >= 0 || urlc.indexOf( "vimeo" ) >= 0 ) ) {
						embed.empty();

						state.props.set( {
							url        : false,
							embed_title: false,
							thumb_url  : false,
							embed_url  : false,
							embed_html : false,
							bad_url    : true
						} );

						input.addClass( 'embed-error' );

						deferred.resolve();
						return;
					}

					bb.fn.show_overlay();

					attr.scanners.push(deferred.promise());

					$.post(
						ajaxurl,
						{
							'action' : 'bing-oembed',
							'url'    : url,
							'nonce'  : BingBoards.embed_nonce
						},
						function (response) {

							bb.fn.hide_overlay();
							if ( response.success ) {

								state.set( {
									type: 'bing-embed'
								} );

								if ( response.data.html ) {
									state.props.set( {
										embed_title: response.data.title,
										thumb_url  : response.data.thumbnail,
										embed_desc : response.data.description,
										embed_url  : response.data.url,
										embed_html : response.data.html,
										bad_url    : false
									} );

									embed.empty();
									embed.append( response.data.html );

									input.removeClass( 'embed-error' );

								} else {
									state.props.set( {
										url        : false,
										embed_title: false,
										thumb_url  : false,
										embed_url  : false,
										embed_html : false,
										bad_url    : true
									} );
									input.addClass( 'embed-error' );
								}

								deferred.resolve();

							} else {
								deferred.reject();
							}
						}
					);
				},

				/**
				 * @function bb.fn.hide_link_picker
				 * @since 1.0
				 * @desc bb.fn.hide_link_picker hides the link picker for the live editor.
				 * @param inserting {Boolean} passed bool to notify whether we are currently inserting the link or not.
				 */

				hide_link_picker : function (inserting) {
					if ($(editor.link_picker).is('.modified') && !inserting) {
						if(confirm(BingBoards.txt_ays)) {
							$(editor.id).removeClass('blp-active');
							$(editor.link_picker).hide();
						}
					} else {
						$(editor.id).removeClass('blp-active');
						$(editor.link_picker).removeClass('modified').hide();
					}

				},

				/**
				 * @function bb.fn.hide_overlay
				 * @since 1.0
				 * @desc bb.fn.hide_overlay removes the admin overlay behind the live editor from the dom.
				 */

				hide_overlay : function () {
					$('.panel-overlay').remove();
				},

				/**
				 * @function bb.fn.html_decode
				 * @since 1.0
				 * @desc bb.fn.html_decode decodes html.
				 * @param value {String} The string to decode.
				 */

				html_decode : function (value) {
					return $('<div/>').html(value).text();
				},

				/**
				 * @function bb.fn.insert_board_title_counter
				 * @since 1.0
				 * @desc bb.fn.insert_board_title_counter appends a character counter to the board title input.
				 */

				insert_board_title_counter : function () {
					bb.el.titlewrap
						.append('<span id="' + editor.char_board.substring(1) + '" class="character-warn"></span>')
				},

				/**
				 * @function bb.fn.insert_link
				 * @since 1.0
				 * @desc bb.fn.insert_link inserts the title and url of a selected link into the live editor.
				 */

				insert_link : function () {
					var $url_input = $(editor.pick_url),
						$title_input = $(editor.pick_title),
						url = $url_input.val(),
						title = $title_input.val();

					if (!url.length)
						return;

					if (!title.length)
						title = url;

					$(editor.link_url).val(url);
					$(editor.link_anchor).val(title);

					$(editor.link_edit).text(bb.fn.html_decode(title)).removeClass('no-link');

					if (!$(editor.link_kill).length)
						$(editor.inputs).append('<span id="panel-link-kill" class="icon-close"></span>');

					bb.fn.enable_save();

					bb.fn.hide_link_picker(true);
				},

				/**
				 * @function bb.fn.legacy
				 * @since 1.0
				 * @desc bb.fn.legacy contains javascript needed to support older browsers.
				 */

				legacy : function () {
					$(editor.title + ', ' + editor.content).placeholder();
				},

				/**
				 * @function bb.fn.link_picker_tests
				 * @since 1.0
				 * @desc bb.fn.link_picker_tests tests for modifications and enter key events on the link picker.
				 * @param e {Object} The jQuery event object.
				 */

				link_picker_tests: function(e){
					if (e.keyCode == 10 || e.keyCode == 13){
						e.stopPropagation();
						e.preventDefault();
					}
					if(!$(editor.link_picker).is('.modified'))
						$(editor.link_picker).addClass('modified');
				},

				/**
				 * @function bb.fn.load_link_picker
				 * @since 1.0
				 * @desc bb.fn.load_link_picker loads the link picker modal on top of the live editor.
				 */

				load_link_picker : function () {

					$(editor.id).addClass('blp-active');
					$(editor.pick_url).val($(editor.link_url).val());
					$(editor.pick_title).val($(editor.link_anchor).val());
					$(editor.link_picker).show();
					bb.fn.char_count(null);
					bb.fn.post_link_search();
				},

				/**
				 * @function bb.fn.load_panel
				 * @since 1.0
				 * @desc bb.fn.load_panel performs an ajax call and loads the response data into the live editor.
				 * @param id {Number} The panel id as integer.
				 * @param spin {Boolean} Whether or not to run the loading spinner.
				 * @param data {Object} Optional object to repopulate the panel list with all panels for this board.
				 */

				load_panel : function (id, spin, data) {

					if (spin)
						bb.fn.show_overlay();

					$.post(
						ajaxurl,
						{
							'action'   : 'bing-get-panel',
							'panel_id' : id
						},
						function (response) {
							if (response.success) {
								$(editor.id).hide();
								if (data)
									bb.el.list.html(data);

								bb.fn.dialog();
								bb.fn.hide_overlay();

								var $content = $(editor.content),
									$editor = $(editor.id),
									$linkedit = $(editor.link_edit);

								$editor.show();
								bb.el.overlay.show();
								$(editor.panel_id).val(response.data.ID);
								$(editor.title).val(bb.fn.html_decode(response.data.post_title));
								$content.val(bb.fn.html_decode(response.data.post_excerpt));
								$(editor.last_mod).text(response.data.last_modified);
								$(editor.link_set + ', ' + editor.link_kill).remove();
								$linkedit.show().addClass('no-link');

								bb.fn.resize_textarea($content);
								bb.fn.carousel();

								$('.cl-' + response.data.ID).parent().addClass('current');

								bb.fn.carousel_current();

								if (response.data.link_url) {
									var link_title;

									if (response.data.link_anchor)
										link_title = bb.fn.html_decode(response.data.link_anchor);
									else
										link_title = response.data.link_url;

									$linkedit.text(bb.fn.html_decode(link_title)).removeClass('no-link');

									$(editor.inputs).append('<span id="panel-link-kill" class="icon-close"></span>');

									$(editor.link_url).val(response.data.link_url);

									if (response.data.link_anchor)
										$(editor.link_anchor).val(bb.fn.html_decode(response.data.link_anchor));
								}

								bb.fn.validate_empty();
								bb.fn.char_count(null);

								if (response.data.thumbnail_embed) {
									$editor.addClass('has-image');
									$(editor.insert).text(BingBoards.txt_replace_image).prepend('<span class="wp-media-buttons-icon"></span>');

									$(editor.image_wrap).empty().append(response.data.thumbnail_embed);
									$(editor.thumb_embed).val(response.data.thumbnail_embed);
									$(editor.thumb_id).val(response.data.thumbnail);

								} else if (response.data.thumbnail) {

									var new_img = $('<img>' ).attr('src', response.data.thumbnail);
									$(editor.image_wrap).empty().append(new_img);

									$editor.addClass('has-image');
									$(editor.insert).text(BingBoards.txt_replace_image).prepend('<span class="wp-media-buttons-icon"></span>');
								}

								if (response.data.thumbnail_id)
									$(editor.thumb_id).val(response.data.thumbnail_id);

								$('p.while-scrapping').hide();

								bb.fn.snapshot();

							} else {
								alert(response.message);
							}
						}
					);

				},

				/**
				 * @function bb.fn.post_link_search
				 * @since 1.0
				 * @desc bb.fn.post_link_search performs a post search ajax call when the link picker is in post link scraping mode.
				 * @param search_term {String} The link picker post search term.
				 */

				post_link_search : function (search_term) {

					if (bb.data.doing_ajax)
						return;

					$('#blp-posts-wrapper').spin('small', '#333');
					$('#blp-posts').empty();

					if (!search_term)
						search_term = "";

					bb.data.doing_ajax = true;

					$.post(
						ajaxurl,
						{
							'action' : 'bing-posts-for-linking',
							's'      : search_term
						},
						function (response) {
							$('#blp-posts-wrapper').spin(false);
							if (response.success) {
								$.each(response.data, function (index, value) {
									var li = '<li><a href="#" data-title="' + value.title + '" data-link="' + value.link + '" class="bing-post-link">';

									if (value.img !== undefined && value.img.length)
										li += '<span class="scrape-thumb"><img src="' + value.img + '" data-id="' + value.ID + '" /></span>';
									else
										li += '<span class="scrape-thumb"></span>';

									li += '<span class="scrape-title">' + value.title + '</span></a>';

									$('#blp-posts').append(li);
								});
								$('#blp-posts').fadeIn(200);
							} else {
								alert(response.message);
							}

							bb.data.doing_ajax = false;
						}
					);

				},

				/**
				 * @function bb.fn.post_scrape_search
				 * @since 1.0
				 * @desc bb.fn.post_scrape_search performs a post search ajax call when the live editor is in post scraping mode.
				 * @param search_term {String} The post scraper search term.
				 */

				post_scrape_search : function (search_term) {
					if (bb.data.doing_ajax)
						return;

					$('#posts-wrapper').spin('small', '#333');
					$('#posts-for-scrapping').empty();

					if (!search_term)
						search_term = "";

					bb.data.doing_ajax = true;

					$.post(
						ajaxurl,
						{
							'action'   : 'bing-posts-for-scrapping',
							'board_id' : $(editor.post_id).val(),
							's'        : search_term
						},
						function (response) {
							if (response.success) {
								$('#posts-wrapper').spin(false);
								$.each(response.data, function (index, value) {
									var li = '<li><a href="#" data-id="' + value.ID + '" class="bing-post-scrap">';

									if (value.img !== undefined && value.img.length)
										li += '<span class="scrape-thumb"><img src="' + value.img + '" data-id="' + value.ID + '" /></span>';
									else
										li += '<span class="scrape-thumb"></span>';

									li += '<span class="scrape-title">' + value.title + '</span></a>';

									$('#posts-for-scrapping').append(li);
								});
								$('#posts-for-scrapping').fadeIn(200);
							} else {
								$('#posts-wrapper').spin(false);
								alert(response.message);
							}

							bb.data.doing_ajax = false;
						}
					);
				},

				/**
				 * @function bb.fn.remove_link
				 * @since 1.0
				 * @desc bb.fn.remove_link deletes a post link from the live editor.
				 */

				remove_link : function () {
					$(editor.link_set + ', ' + editor.link_kill).remove();
					$(editor.link_edit).text(BingBoards.txt_choose_link).addClass('no-link');
					$(editor.link_url + ', ' + editor.link_anchor).val('');
					bb.fn.enable_save();
					bb.fn.validate_empty();
				},

				/**
				 * @function bb.fn.resize_textarea
				 * @since 1.0
				 * @desc bb.fn.resize_textarea resizes the live editor textarea as needed to fill the maximum space it can before switching to scrollable.
				 * @param $content {Object} The jquery object of the textarea.
				 */

				resize_textarea : function ($content) {
					$content.css({'height' : 'auto'});
					if ($content.val() === '')
						return;

					var c_height = $content[0].scrollHeight;

					if(bb.tests.webkit())
						c_height = c_height - 16;

					if (c_height < 180)
						$content.height(c_height).css('overflow', 'hidden');
					else
						$content.height(180).css('overflow', 'auto');
				},

				/**
				 * @function bb.fn.save_panel
				 * @since 1.0
				 * @desc bb.fn.save_panel performs an ajax call to save the current live editors data.
				 */

				save_panel : function () {

					bb.fn.show_overlay();

					var panel_id = $(editor.panel_id).val(),
						menu_order = 0;

					if (!panel_id.length)
						menu_order = $('.bing-panel-permalink').length + 1;
					else
						menu_order = $('.bing-panel-permalink[data-id="' + panel_id + '"]').index('.bing-panel-permalink') + 1;

					$.post(
						ajaxurl,
						{
							'action'            : 'bing-add-panel',
							'nonce'             : BingBoards.add_panel_nonce,
							'panel_id'          : panel_id,
							'panel_title'       : bb.fn.html_decode($(editor.title).val()),
							'panel_content'     : bb.fn.html_decode($(editor.content).val()),
							'panel_link_url'    : $(editor.link_url).val(),
							'panel_link_anchor' : bb.fn.html_decode($(editor.link_anchor).val()),
							'thumb_id'          : $(editor.thumb_id).val(),
							'thumb_embed'       : $(editor.thumb_embed).val(),
							'board_id'          : $(editor.post_id).val(),
							'menu_order'        : menu_order
						},
						function (response) {
							bb.fn.hide_overlay();
							if (response.success) {
								bb.el.list.html(response.data.panels);
								bb.fn.setup_panel();
								$(editor.last_mod).text(response.data.last_modified);
								$(editor.panel_id).val(response.data.id);
								$('.cl-' + response.data.id).parent().addClass('current');

								bb.fn.carousel_current();
								bb.fn.snapshot();

							} else {
								alert(response.message);
							}
						}
					);
				},

				/**
				 * @function bb.fn.scrape_post
				 * @since 1.0
				 * @desc bb.fn.scrape_post scrapes a post and loads as the last item in the bing panel list, finalizing by loading that post into the live editor.
				 * @param $this {Object} The selected post link as a jquery object.
				 */

				scrape_post : function ($this) {
					bb.fn.show_overlay();
					$.post(
						ajaxurl,
						{
							'action'     : 'bing-scrap-local-post',
							'board_id'   : $(editor.post_id).val(),
							'post_id'    : $this.attr('data-id'),
							'menu_order' : $('.bing-panel-permalink').length + 1,
							'nonce'      : BingBoards.scrap_post_nonce
						},
						function (response) {
							if (response.success) {
								var id = $(response.data).find('.bing-panel-permalink').last().data('id');
								bb.fn.load_panel(id, false, response.data);

							} else {
								bb.fn.hide_overlay();
								alert(response.message);
							}
						}
					);
				},

				/**
				 * @function bb.fn.send_panels
				 * @since 1.0
				 * @desc bb.fn.send_panels Sends the panels to bing.
				 */

				send_panels : function () {
					$.post(
						ajaxurl,
						{
							'action' : 'bing-panels-sort',
							'nonce'  : BingBoards.sort_nonce,
							'panels' : $("#bing-panels-list").sortable("toArray")
						}
					);
				},

				/**
				 * @function bb.fn.serialize
				 * @since 1.0
				 * @desc bb.fn.serialize Custom serialize variant to form an array of all live editor inputs.
				 */

				serialize : function () {

					var serialized = [];

					$(editor.serialize).each(function (i, v) {
						var obj = $(v);
						var name = obj.attr('name');
						serialized[name] = obj.val();
					});

					bb.fn.validate_empty();

					return serialized;
				},

				/**
				 * @function bb.fn.setup_panel
				 * @since 1.0
				 * @desc bb.fn.setup_panel Executes a function stack to be run on live editor open.
				 */

				setup_panel : function () {
					bb.fn.resize_textarea($(editor.content));
					bb.fn.char_count(null);
					bb.fn.carousel();
				},

				/**
				 * @function bb.fn.show_overlay
				 * @since 1.0
				 * @desc bb.fn.show_overlay creates an overlay element, appends to body and runs a loading spinner for various editor ajax calls.
				 */

				show_overlay : function () {

					bb.el.body
						.append('' +
							'<div class="panel-overlay">' +
							'<div class="panel-loader" style="height:' + bb.data.v_height + 'px; width:' + bb.data.v_width + 'px">' +
							'</div>' +
							'</div>'
						);

					$(".panel-loader")
						.append('<span class="spinbg"></span>')
						.spin('large', '#fff');
				},

				/**
				 * @function bb.fn.snapshot
				 * @since 1.0
				 * @desc bb.fn.snapshot takes a snapshot of the current live editor state and stores it in the last serialized variable.
				 */

				snapshot : function () {
					bb.data.last_serialized = bb.fn.serialize();
				},

				/**
				 * @function bb.fn.toggle_button
				 * @since 1.0
				 * @desc bb.fn.toggle_button toggles the new live editor button dropdown.
				 * @param e {Object} The jquery event object.
				 * @param $this {Object} The link toggle as a jquery object.
				 */

				toggle_button : function (e, $this) {

					e.preventDefault();

					if ($this.hasClass('no-round-bottom'))
						$this.removeClass('no-round-bottom');
					else
						$this.addClass('no-round-bottom');

					bb.el.panel_options.toggle();
				},

				/**
				 * @function bb.fn.update_data
				 * @since 1.0
				 * @desc bb.fn.update_data updates variables used throughout the code when required.
				 */

				update_data : function () {
					bb.data.v_height = $(window).height();
					bb.data.v_width = $(window).width();
				},

				/**
				 * @function bb.fn.use_link
				 * @since 1.0
				 * @desc bb.fn.use_link applies the newly selected link pickers link to hidden inputs in the live editor.
				 * @param $this {Object} The selected link as a jquery object.
				 */

				use_link: function ( $this ) {
					var url = $this.data( 'link' ),
						title = $this.data( 'title' ),
						$url_input = $( editor.pick_url ),
						$title_input = $( editor.pick_title );

					$url_input.val( url );
					$title_input.val( title );
				},

				/**
				 * @function bb.fn.validate_empty
				 * @since 1.0
				 * @desc bb.fn.validate_empty adds an empty class to all inputs in the live editor if they are indeed empty.
				 */

				validate_empty : function () {
					var $title = $(editor.title),
						$content = $(editor.content),
						$image = $(editor.image_wrap),
						$image_parent = $image.parents('.panel-media'),
						$link_edit = $(editor.link_edit);

					if($title.val() === '')
						$title.addClass('empty');
					else
						$title.removeClass('empty');

					if($content.val() === '')
						$content.addClass('empty');
					else
						$content.removeClass('empty');

					if($image.html() === '')
						$image_parent.addClass('empty');
					else
						$image_parent.removeClass('empty');

					if($link_edit.is('.no-link'))
						$link_edit.addClass('empty');
					else
						$link_edit.removeClass('empty');
				}

			},

			/**
			 * @namespace bb.frame
			 * @since 1.0
			 * @desc bb.frame stores a customized variant of the wp media editor.
			 */

			frame : wp.media({
				frame    : 'post',
				state    : 'insert',
				multiple : false,
				library  : { type : 'image' },
				editing  : true
			}),

			/**
			 * @namespace bb.tests
			 * @since 1.0
			 * @desc bb.tests stores various tests used in the bing js.
			 */

			tests : {
				isie10: function(){
					return !!(("onpropertychange" in document && !!window.matchMedia));
				},
				webkit: function(){
					return !!('webkitRequestAnimationFrame' in window);
				}
			},

			/**
			 * @function bb.init
			 * @since 1.0
			 * @desc bb.init is our main initialize function and contains all event handlers and dom ready function calls.
			 */

			init : function () {

				bb.fn.can_api();

				bb.el.body
					.append($(editor.id))
					.append('<div class="bing-overlay"></div>');

				bb.el.overlay = $('.bing-overlay');

				bb.fn.update_data();
				bb.fn.get_panels();
				bb.fn.resize_textarea($(editor.content));
				bb.fn.legacy();
				bb.fn.insert_board_title_counter();
				bb.fn.char_count(null);

				bb.el.list
					.sortable({ handle : '.icon-menu' })
					.disableSelection()
					.on("sortupdate", function () {
						bb.fn.send_panels();
					})
					.on('click', '.bing-panel-permalink, .thumbnail', function (e) {
						e.preventDefault();
						bb.fn.load_panel($(this).attr('data-id'), true, null);
					})
					.on('click', '.bing-panel .icon-close', function (e) {
						e.preventDefault();
						bb.fn.delete_panel($(this).attr('data-id'));
					});

				bb.frame
					.states
					.get('embed')
					.on('scan', function (attributes) {
						bb.fn.get_embed(this, attributes);
					});

				bb.frame
					.on('close', function () {
						bb.fn.close_frame();
					});

				bb.el.new_panel
					.on("click", "#bing-new-panel-button", function (e) {
						bb.fn.toggle_button(e, $(this));
					})
					.on("click", "#new-panel-from-scratch, #new-panel-from-post", function (e) {
						bb.fn.create_panel(e);
					});

				bb.el.body
					.on("keyup focus", editor.content, function () {
						bb.fn.content_update($(this));
					})
					.on("blur", editor.content, function () {
						bb.fn.char_hide(editor.char_content);
					})
					.on('keyup', editor.serialize, function () {
						bb.fn.enable_save();
					})
					.on('keyup focus', editor.title, function () {
						bb.fn.char_count($(this), editor.char_title, bb.data.char_limit_title);
					})
					.on('blur', editor.title, function () {
						bb.fn.char_hide(editor.char_title);
					})
					.on('keyup focus', editor.pick_title, function () {
						bb.fn.char_count($(this), editor.char_link, bb.data.char_limit_link);
					})
					.on('keydown', editor.pick_title + ', ' + editor.pick_url + ', ' + editor.pick_search, function (e) {
						bb.fn.link_picker_tests(e);
					})
					.on('blur', editor.pick_title, function () {
						bb.fn.char_hide(editor.char_link);
					})
					.on('keyup focus', '#title', function () {
						bb.fn.char_count($(this), editor.char_board, bb.data.char_limit_board);
					})
					.on('keyup focus', 'input.bing-search-term', function () {
						bb.fn.can_api();
					})
					.on('blur', '#title', function () {
						bb.fn.char_hide(editor.char_board);
					})
					.on('click', editor.insert, function (e) {
						e.preventDefault();
						bb.frame.open();
						bb.fn.enable_save();
					})
					.on('click', editor.save, function () {
						bb.fn.show_overlay();
						bb.fn.save_panel();
						bb.fn.disable_save();
					})
					.on('click', editor.link_edit, function (e) {
						e.preventDefault();
						bb.fn.load_link_picker();
					})
					.on('click', '#bing-close-le', function (e) {
						e.preventDefault();
						e.stopPropagation();
						bb.fn.hide_link_picker(false);
					})
					.on('keyup', editor.pick_search, function () {
						bb.fn.post_link_search($(this).val());
					})
					.on('click', '.bing-post-link', function (e) {
						e.preventDefault();
						bb.fn.use_link($(this));
						bb.fn.char_count($(editor.pick_title), editor.char_link, bb.data.char_limit_link);
					})
					.on('click', '#bing-link-insert', function (e) {
						e.preventDefault();
						bb.fn.insert_link();
						bb.fn.char_count(null);
					})
					.on('click', '#panel-slider-left, #panel-slider-right', function (e) {
						bb.fn.carousel_nav(e);
					})
					.on('click', '.bing-post-scrap', function (e) {
						e.preventDefault();
						bb.fn.scrape_post($(this));
					})
					.on('keyup', '#panel-scrapper-search', function () {
						bb.fn.post_scrape_search($(this).val());
					})
					.on('click', '.carousel-link', function (e) {
						e.preventDefault();
						if (bb.fn.ays())
							bb.fn.load_panel($(this).data('id'), true, null);
					})
					.on('click', editor.link_kill, function () {
						bb.fn.remove_link();
					})
					.on('click', 'a.live-editor-close, .bing-overlay', function (e) {
						e.preventDefault();
						bb.fn.close_editor();
					})
					.on('click', '#carousel-new-panel', function (e) {
						e.preventDefault();
						bb.fn.carousel_new();
					})
					.on('change', '#post_author_override', function(e){
						$.post(
							ajaxurl,
							{
								'action'  : 'bing-user-has-key',
								'user_id' : $(this).val(),
								'nonce'   : BingBoards.user_has_bing_key_nonce
							},
							function (response) {
								if (response.success){
									bb.data.user_has_bing_key = response.data.user_has_bing_key;
									bb.data.submit_errors[2]  = response.data.message;
									bb.fn.can_api();
								}

							}
						);

					})
					.on( 'submit', 'form#post', function ( e ) {

						bb.data.first_save = true;
						var result = bb.fn.can_api();

						if ( !result ) {
							$( '#publish' )
								.removeClass( 'button-primary-disabled' );
							
							$( '#save-post' )
								.removeClass( 'button-disabled')
								.next('.spinner')
								.attr( 'style', '' );

							$( '#publishing-action .spinner' )
								.hide();
						}

						return result;

					} );

				$(window).resize(function () {
					bb.fn.update_data();
				});

			}

		};

		bb.init();

	});

})(window, jQuery);
