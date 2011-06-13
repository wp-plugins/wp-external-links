<?php
/*
Plugin Name: WP External Links
Plugin URI: http://www.freelancephp.net/wp-external-links-plugin
Description: Manage external links on your site: open in new window/tab, set link icon, add "external", add "nofollow" and more.
Author: Victor Villaverde Laan
Version: 0.35
Author URI: http://www.freelancephp.net
License: Dual licensed under the MIT and GPL licenses
*/

/**
 * Class WP_External_Links
 * @category WordPress Plugins
 */
class WP_External_Links {

	/**
	 * Current version
	 * @var string
	 */
	var $version = '0.35';

	/**
	 * Used as prefix for options entry and could be used as text domain (for translations)
	 * @var string
	 */
	var $domain = 'WP_External_Links';

	/**
	 * Name of the options
	 * @var string
	 */
	var $options_name = 'WP_External_Links_options';

	/**
	 * Options to be saved
	 * @var array
	 */
	var $options = array(
		'target' => '_none',
		'use_js' => 1,
		'external' => 1,
		'nofollow' => 1,
		'filter_whole_page' => 1,
		'filter_posts' => 1,
		'filter_comments' => 1,
		'filter_widgets' => 1,
		'fix_js' => 0,
		'fix_xhtml' => 0,
		'icon' => 0,
		'no_icon_class' => 'no-ext-icon',
		'no_icon_same_window' => 0,
		'class_name' => 'ext-link',
		'widget_logic_filter' => 0,
	);

	/**
	 * Regexp
	 * @var array
	 */
	var $regexp_patterns = array(
		'a' => '/<a[^A-Za-z](.*?)>(.*?)<\/a[\s+]*>/is',
		'css' => '/<link(.*?)wp-external-links-css(.*?)\/>[\s+]*/i',
		'body' => '/<body(.*?)>(.*?)<\/body[\s+]*>/is',
		'script' => '/<script(.*?)>(.*?)<\/script[\s+]*>/is',
	);


	/**
	 * PHP4 constructor
	 */
	function WP_External_Links() {
		$this->__construct();
	}

	/**
	 * PHP5 constructor
	 */
	function __construct() {
		// set option values
		$this->_set_options();

		// load text domain for translations
		load_plugin_textdomain( $this->domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// set uninstall hook
		if ( function_exists( 'register_deactivation_hook' ) )
			register_deactivation_hook( __FILE__, array( $this, 'deactivation' ));

		// add actions
		add_action( 'wp', array( $this, 'wp' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Callback wp
	 */
	function wp() {
		if ( ! is_admin() && ! is_feed() ) {
			// add wp_head for setting js vars and css style
			add_action( 'wp_head', array( $this, 'wp_head' ) );

			// add stylesheet
			wp_enqueue_style( 'wp-external-links', plugins_url( 'css/external-links.css', __FILE__ ), FALSE, $this->version );

			// set js file
			if ( $this->options[ 'use_js' ] ) {
				wp_enqueue_script( 'wp-external-links', plugins_url( 'js/external-links.js', __FILE__ ), array( 'jquery' ), $this->version );
			}

			if ( $this->options[ 'filter_whole_page' ] ) {
				ob_start( array( $this, 'filter_page' ) );
			} else {
				// set filter priority
				$priority = 1000000000;

				// content
				if ( $this->options[ 'filter_posts' ] ) {
					add_filter( 'the_title', array( $this, 'filter_content' ), $priority );
					add_filter( 'the_content', array( $this, 'filter_content' ), $priority );
					add_filter( 'the_excerpt', array( $this, 'filter_content' ), $priority );
					add_filter( 'get_the_excerpt', array( $this, 'filter_content' ), $priority );
				}

				// comments
				if ( $this->options[ 'filter_comments' ] ) {
					add_filter( 'comment_text', array( $this, 'filter_content' ), $priority );
					add_filter( 'comment_excerpt', array( $this, 'filter_content' ), $priority );
					add_filter( 'comment_url', array( $this, 'filter_content' ), $priority );
					add_filter( 'get_comment_author_url', array( $this, 'filter_content' ), $priority );
					add_filter( 'get_comment_author_link', array( $this, 'filter_content' ), $priority );
					add_filter( 'get_comment_author_url_link', array( $this, 'filter_content' ), $priority );
				}

				// widgets ( only text widgets )
				if ( $this->options[ 'filter_widgets' ] ) {
					add_filter( 'widget_title', array( $this, 'filter_content' ), $priority );
					add_filter( 'widget_text', array( $this, 'filter_content' ), $priority );

					// Only if Widget Logic plugin is installed and 'widget_content' option is activated
					add_filter( 'widget_content', array( $this, 'filter_content' ), $priority );
				}
			}
		}
	}

	/**
	 * Callback admin_menu
	 */
	function admin_menu() {
		if ( function_exists( 'add_options_page' ) AND current_user_can( 'manage_options' ) ) {
			// add options page
			$page = add_options_page( __( 'External Links', $this->domain ), __( 'External Links', $this->domain ),
								'manage_options', __FILE__, array( $this, 'options_page' ) );
		}
	}

	/**
	 * Callback wp_head
	 */
	function wp_head() {
		if ( $this->options[ 'use_js' ] AND $this->options[ 'target' ] != '_none' ):
			$excludeClass = ( $this->options[ 'no_icon_same_window' ] AND ! empty( $this->options[ 'no_icon_class' ] ) )
							? $this->options[ 'no_icon_class' ]
							: '';
?>
<script type="text/javascript">/* <![CDATA[ */
/* WP External Links Plugin */
var wpExtLinks = { baseUrl: '<?php echo get_bloginfo( 'url' ) ?>',target: '<?php echo $this->options[ 'target' ] ?>',excludeClass: '<?php echo $excludeClass ?>' };
/* ]]> */</script>
<?php
		endif;
	}

	/**
	 * Filter complete html page
	 * @param array $match
	 * @return string
	 */
	function filter_page( $content ) {
		// only replace links in <body> part
		return preg_replace_callback( $this->regexp_patterns[ 'body' ], array( $this, '_callback_page_filter' ), $content );
	}

	function _callback_page_filter( $match ) {
		return $this->filter_content( $match[ 0 ] );
	}

	/**
	 * Filter content
	 * @param string $content
	 * @return string
	 */
	function filter_content( $content ) {
		if ( $this->options[ 'fix_js' ] ) {
			// fix js proble by replacing </a> by <\/a>
			$content = preg_replace_callback( $this->regexp_patterns[ 'script' ], array( $this, 'fix_js' ), $content );
		}

		// replace links in body
		$content = preg_replace_callback( $this->regexp_patterns[ 'a' ], array( $this, 'parse_link' ), $content );

		// remove style when no icon classes are found
		if ( strpos( $content, 'ext-icon-' ) === FALSE ) {
			// remove style with id wp-external-links-css
			$content = preg_replace( $this->regexp_patterns[ 'css' ], '', $content );
		}

		return $content;
	}

	function fix_js( $match ) {
		return str_replace( '</a>', '<\/a>', $match[ 0 ] );
	}

	/**
	 * Make a clean <a> code
	 * @param array $match Result of a preg call in filter_content()
	 * @return string Clean <a> code
	 */
	function parse_link( $match ) {
		$attrs = $match[ 1 ];
		$attrs = stripslashes( $attrs );
		$attrs = shortcode_parse_atts( $attrs );

		$href_tolower = strtolower( $attrs[ 'href' ] );
		$rel_tolower = ( isset( $attrs[ 'rel' ] ) ) ? strtolower( $attrs[ 'rel' ] ) : '';

		// check url
		if ( isset( $attrs[ 'href' ] ) AND ( strpos( $rel_tolower, 'external' ) !== FALSE
												OR  ( strpos( $href_tolower, strtolower( get_bloginfo( 'url' ) ) ) === FALSE )
														AND ( substr( $href_tolower, 0, 7 ) == 'http://'
																OR substr( $href_tolower, 0, 8 ) == 'https://'
																OR substr( $href_tolower, 0, 6 ) == 'ftp://' ) ) ){
			// set rel="external" (when not already set)
			if ( $this->options[ 'external' ] AND strpos( $rel_tolower, 'external' ) === FALSE ){
				$attrs[ 'rel' ] = ( empty( $attrs[ 'rel' ] ) )
								? 'external'
								: $attrs[ 'rel' ] .' external';
			}

			// set rel="nofollow" when doesn't have "follow" (or already "nofollow")
			if ( $this->options[ 'nofollow' ] AND strpos( $rel_tolower, 'follow' ) === FALSE ){
				$attrs[ 'rel' ] = ( empty( $attrs[ 'rel' ] ) )
								? 'nofollow'
								: $attrs[ 'rel' ] .' nofollow';
			}

			// set icon class, unless no-icon class isset or another icon class ('ext-icon-...') is found
			if ( $this->options[ 'icon' ] > 0 AND ( empty( $this->options[ 'no_icon_class' ] ) OR strpos( $attrs[ 'class' ], $this->options[ 'no_icon_class' ] ) === FALSE ) AND strpos( $attrs[ 'class' ], 'ext-icon-' ) === FALSE  ){
				$icon_class = 'ext-icon-'. $this->options[ 'icon' ];

				$attrs[ 'class' ] = ( empty( $attrs[ 'class' ] ) )
									? $icon_class
									: $attrs[ 'class' ] .' '. $icon_class;
			}

			// set user-defined class
			if ( ! empty( $this->options[ 'class_name' ] ) AND ( empty( $attrs[ 'class' ] ) OR strpos( $attrs[ 'class' ], $this->options[ 'class_name' ] ) === FALSE ) ){
				$attrs[ 'class' ] = ( empty( $attrs[ 'class' ] ) )
									? $this->options[ 'class_name' ]
									: $attrs[ 'class' ] .' '. $this->options[ 'class_name' ];
			}

			// set target
			if ( $this->options[ 'target' ] != '_none' AND ! $this->options[ 'use_js' ] AND ( ! $this->options[ 'no_icon_same_window' ] OR empty( $this->options[ 'no_icon_class' ] ) OR strpos( $attrs[ 'class' ], $this->options[ 'no_icon_class' ] ) === FALSE ) )
				$attrs[ 'target' ] = $this->options[ 'target' ];

		} elseif ( $this->options[ 'fix_xhtml' ] ) {
			return $match[ 0 ];
		}

		// create element code
		$link = '<a ';

		foreach ( $attrs AS $key => $value )
			$link .= $key .'="'. $value .'" ';

		// remove last space
		$link = substr( $link, 0, -1 );

		$link .= '>'. $match[ 2 ] .'</a>';

		return $link;
	}

	/**
	 * Callback admin_init
	 */
	function admin_init() {
		// register settings
		register_setting( $this->domain, $this->options_name );

		// set dashboard postbox
		wp_admin_css( 'dashboard' );
		wp_enqueue_script( 'dashboard' );
	}

	/**
	 * Admin options page
	 */
	function options_page() {
?>
<script type="text/javascript">
jQuery(function( $ ){
	// remove message
	$( '.settings-error' )
		.hide()
		.slideDown( 600 )
		.delay( 3000 )
		.slideUp( 600 );

	// option filter whole page
	$( 'input#filter_whole_page' )
		.change(function(){
			var $i = $( 'input#filter_posts, input#filter_comments, input#filter_widgets' );

			if ( $( this ).attr( 'checked' ) ) {
				$i.attr( 'disabled', true )
					.attr( 'checked', true );
			} else {
				$i.attr( 'disabled', false )
			}
		})
		.change();

	// slide postbox
	$( '.postbox' ).find( '.handlediv, .hndle' ).click(function(){
		var $inside = $( this ).parent().find( '.inside' );

		if ( $inside.css( 'display' ) == 'block' ) {
			$inside.css({ display:'block' }).slideUp();
		} else {
			$inside.css({ display:'none' }).slideDown();
		}
	});

	// note
	$( '#fix_xhtml' ).change(function(){
		$( '#xhtml_convert_all' ).css( 'display', $( this ).attr( 'checked' ) ? 'none' : 'block' );
	}).change();
});
</script>
	<div class="wrap">
		<div class="icon32" id="icon-options-custom" style="background:url( <?php echo plugins_url( 'images/icon-wp-external-links.png', __FILE__ ) ?> ) no-repeat 50% 50%"><br></div>
		<h2><?php _e( 'External Links Settings' ) ?></h2>

		<form method="post" action="options.php">
			<?php
				settings_fields( $this->domain );
				$this->_set_options();
				$options = $this->options;
			?>

		<div class="postbox-container metabox-holder meta-box-sortables" style="width:69%;">
		<div style="margin:0 5px;">
			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'General Settings', $this->domain ) ?></h3>
				<div class="inside">
					<fieldset class="options">
						<table class="form-table">
						<tr>
							<th><?php _e( 'Set target for external links', $this->domain ) ?></th>
							<td class="target_external_links">
								<label><input type="radio" name="<?php echo $this->options_name ?>[target]" class="field_target" value="_blank" <?php checked( '_blank', $options['target'] ); ?> />
								<span><?php _e( '<code>_blank</code>, open every external link in a new window or tab', $this->domain ) ?></span></label>
								<br/><label><input type="radio" name="<?php echo $this->options_name ?>[target]" class="field_target" value="_top" <?php checked( '_top', $options['target'] ); ?> />
								<span><?php _e( '<code>_top</code>, open in current window or tab, with no frames', $this->domain ) ?></span></label>
								<br/><label><input type="radio" name="<?php echo $this->options_name ?>[target]" class="field_target" value="_new" <?php checked( '_new', $options['target'] ); ?> />
								<span><?php _e( '<code>_new</code>, open new window the first time and use this window for each external link', $this->domain ) ?></span></label>
								<br/><label><input type="radio" name="<?php echo $this->options_name ?>[target]" class="field_target" value="_none" <?php checked( '_none', $options['target'] ); ?> />
								<span><?php _e( '<code>_none</code>, open in current window or tab', $this->domain ) ?></span></label>
							</td>
						</tr>
						<tr>
							<th>&nbsp;</th>
							<td>
								<label><input type="checkbox" name="<?php echo $this->options_name ?>[use_js]" class="field_use_js" value="1" <?php checked( '1', (int) $options['use_js'] ); ?> />
								<span><?php _e( 'Use JavaScript method (recommended, XHTML Strict compliant)', $this->domain ) ?></span></label>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Add to "rel" attribute', $this->domain ) ?></th>
							<td><label><input type="checkbox" id="<?php echo $this->options_name ?>[external]" name="<?php echo $this->options_name ?>[external]" value="1" <?php checked('1', (int) $options['external']); ?> />
								<span><?php _e( 'Add <code>"external"</code> to the rel-attribute of external links', $this->domain ) ?></span></label>
								<br/><label><input type="checkbox" id="<?php echo $this->options_name ?>[nofollow]" name="<?php echo $this->options_name ?>[nofollow]" value="1" <?php checked('1', (int) $options['nofollow']); ?> />
								<span><?php _e( 'Add <code>"nofollow"</code> to the rel-attribute of external links (unless link has <code>"follow"</code>)', $this->domain ) ?></span></label>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Options have effect on', $this->domain ) ?></th>
							<td>
								<label><input type="checkbox" name="<?php echo $this->options_name ?>[filter_whole_page]" id="filter_whole_page" value="1" <?php checked( '1', (int) $options['filter_whole_page'] ); ?> />
								<span><?php _e( 'All contents (the whole <code>&lt;body&gt;</code>)', $this->domain ) ?></span></label>
								<br/>&nbsp;&nbsp;<label><input type="checkbox" name="<?php echo $this->options_name ?>[filter_posts]" id="filter_posts" value="1" <?php checked( '1', (int) $options['filter_posts'] ); ?> />
										<span><?php _e( 'Post contents', $this->domain ) ?></span></label>
								<br/>&nbsp;&nbsp;<label><input type="checkbox" name="<?php echo $this->options_name ?>[filter_comments]" id="filter_comments" value="1" <?php checked( '1', (int) $options['filter_comments'] ); ?> />
										<span><?php _e( 'Comments', $this->domain ) ?></span></label>
								<br/>&nbsp;&nbsp;<label><input type="checkbox" name="<?php echo $this->options_name ?>[filter_widgets]" id="filter_widgets" value="1" <?php checked( '1', (int) $options['filter_widgets'] ); ?> />
										<span><?php if ( $this->options[ 'widget_logic_filter' ] ) { _e( 'All widgets (uses the <code>widget_content</code> filter of the Widget Logic plugin)', $this->domain ); } else { _e( 'All text widgets', $this->domain ); } ?></span></label>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Solve problems...', $this->domain ) ?></th>
							<td><label><input type="checkbox" name="<?php echo $this->options_name ?>[fix_js]" id="fix_js" value="1" <?php checked( '1', (int) $options['fix_js'] ); ?> />
								<span><?php _e( 'Auto-fix javascript problem (by replacing <code>&lt;/a&gt;</code> with <code>&lt;\/a&gt;</code> in js)', $this->domain ) ?></span></label>
								<br/><label><input type="checkbox" name="<?php echo $this->options_name ?>[fix_xhtml]" id="fix_xhtml" value="1" <?php checked( '1', (int) $options['fix_xhtml'] ); ?> />
								<span><?php _e( 'Only convert <strong>external</strong> links of the selected content to valid XHTML code (instead of all &lt;a&gt;-tags)', $this->domain ) ?></span></label>
								<br/>
								<br/><span id="xhtml_convert_all" class="description"><?php _e( 'Note: all <code>&lt;a&gt;</code> tags in the selected contents will be converted to XHTML valid code' ) ?></span>
							</td>
						</tr>
						</table>
					</fieldset>
					<p class="submit">
						<input class="button-primary" type="submit" value="<?php _e( 'Save Changes' ) ?>" />
					</p>
				</div>
			</div>

			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'Style Settings', $this->domain ) ?></h3>
				<div class="inside">
					<fieldset class="options">
						<table class="form-table">
						<tr>
							<th><?php _e( 'Show icon', $this->domain ) ?>
	 						</th>
							<td>
								<div>
									<div style="width:15%;float:left">
										<label><input type="radio" name="<?php echo $this->options_name ?>[icon]" value="0" <?php checked('0', (int) $options['icon']); ?> />
										<span><?php _e( 'No icon', $this->domain ) ?></span></label>
									<?php for ( $x = 1; $x <= 20; $x++ ): ?>
										<br/>
										<label title="<?php echo sprintf( __( 'Icon %1$s: choose this icon to show for all external links or add the class \'ext-icon-%1$s\' to a specific link.' ), $x ) ?>"><input type="radio" name="<?php echo $this->options_name ?>[icon]" value="<?php echo $x ?>" <?php checked( $x, (int) $options['icon'] ); ?> />
										<img src="<?php echo plugins_url( 'images/external-'. $x .'.png', __FILE__ ) ?>" /></label>
										<?php if ( $x % 5 == 0 ): ?>
									</div>
									<div style="width:15%;float:left">
										<?php endif; ?>
									<?php endfor; ?>
									</div>
									<div style="width:29%;float:left;"><span class="description"><?php _e( 'Example:', $this->domain ) ?></span>
										<br/><img src="<?php echo plugins_url( 'images/link-icon-example.png', __FILE__ ) ?>"	/>
									</div>
									<br style="clear:both" />
								</div>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'No-Icon Class', $this->domain ) ?></th>
							<td><label><input type="text" id="<?php echo $this->options_name ?>[no_icon_class]" name="<?php echo $this->options_name ?>[no_icon_class]" value="<?php echo $options['no_icon_class']; ?>" />
								<span><?php _e( 'Use this class when a link should not show any icon', $this->domain ) ?></span></label>
								<br/><label><input type="checkbox" name="<?php echo $this->options_name ?>[no_icon_same_window]" id="no_icon_same_window" value="1" <?php checked( '1', (int) $options['no_icon_same_window'] ); ?> />
								<span><?php _e( 'Always open no-icon links in current window', $this->domain ) ?></span></label>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Additional Classes (optional)', $this->domain ) ?></th>
							<td><label><input type="text" id="<?php echo $this->options_name ?>[class_name]" name="<?php echo $this->options_name ?>[class_name]" value="<?php echo $options['class_name']; ?>" />
								<span><?php _e( 'Add extra classes to external links (or leave blank)', $this->domain ) ?></span></label></td>
						</tr>
						</table>
					</fieldset>
					<p class="submit">
						<input class="button-primary" type="submit" value="<?php _e( 'Save Changes' ) ?>" />
					</p>
				</div>
			</div>
		</div>
		</div>

		<div class="postbox-container metabox-holder meta-box-sortables" style="width:29%;">
		<div style="margin:0 5px;">
			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'About' ) ?>...</h3>
				<div class="inside">
					<h4><img src="<?php echo plugins_url( 'images/icon-wp-external-links.png', __FILE__ ) ?>" width="16" height="16" /> WP External Links (v<?php echo $this->version ?>)</h4>
					<p><?php _e( 'Manage external links on your site: open in new window/tab, set link icon, add "external", add "nofollow" and more.', $this->domain ) ?></p>
					<ul>
						<li><a href="http://www.freelancephp.net/contact/" target="_blank"><?php _e( 'Questions or suggestions?', $this->domain ) ?></a></li>
						<li><?php _e( 'If you like this plugin please send your rating at WordPress.org.' ) ?></li>
						<li><a href="http://wordpress.org/extend/plugins/wp-external-links/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/wp-external-links-plugin/" target="_blank">FreelancePHP.net</a></li>
					</ul>
				</div>
			</div>

			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'Other Plugins', $this->domain ) ?></h3>
				<div class="inside">
					<h4><img src="<?php echo plugins_url( 'images/icon-wp-mailto-links.png', __FILE__ ) ?>" width="16" height="16" /> WP Mailto Links</h4>
					<p><?php _e( 'Manage mailto links on your site and protect emails from spambots, set mail icon and more.', $this->domain ) ?></p>
					<ul>
						<?php if ( is_plugin_active( 'wp-mailto-links/wp-mailto-links.php' ) ): ?>
							<li><?php _e( 'This plugin is already activated.', $this->domain ) ?> <a href="<?php echo get_bloginfo( 'url' ) ?>/wp-admin/options-general.php?page=wp-mailto-links/wp-mailto-links.php"><?php _e( 'Settings' ) ?></a></li>
						<?php elseif( file_exists( WP_PLUGIN_DIR . '/wp-mailto-links/wp-mailto-links.php' ) ): ?>
							<li><a href="<?php echo get_bloginfo( 'url' ) ?>/wp-admin/plugins.php?plugin_status=inactive"><?php _e( 'Activate this plugin.', $this->domain ) ?></a></li>
						<?php else: ?>
							<li><a href="<?php echo get_bloginfo( 'url' ) ?>/wp-admin/plugin-install.php?tab=search&type=term&s=WP+Mailto+Links+freelancephp&plugin-search-input=Search+Plugins"><?php _e( 'Get this plugin now', $this->domain ) ?></a></li>
						<?php endif; ?>
						<li><a href="http://wordpress.org/extend/plugins/wp-mailto-links/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/wp-mailto-links-plugin/" target="_blank">FreelancePHP.net</a></li>
					</ul>

					<h4><img src="<?php echo plugins_url( 'images/icon-email-encoder-bundle.png', __FILE__ ) ?>" width="16" height="16" /> Email Encoder Bundle</h4>
					<p><?php _e( 'Protect email addresses on your site from spambots and being used for spamming by using one of the encoding methods.', $this->domain ) ?></p>
					<ul>
						<?php if ( is_plugin_active( 'email-encoder-bundle/email-encoder-bundle.php' ) ): ?>
							<li><?php _e( 'This plugin is already activated.', $this->domain ) ?> <a href="<?php echo get_bloginfo( 'url' ) ?>/wp-admin/options-general.php?page=email-encoder-bundle/email-encoder-bundle.php"><?php _e( 'Settings' ) ?></a></li>
						<?php elseif( file_exists( WP_PLUGIN_DIR . '/email-encoder-bundle/email-encoder-bundle.php' ) ): ?>
							<li><a href="<?php echo get_bloginfo( 'url' ) ?>/wp-admin/plugins.php?plugin_status=inactive"><?php _e( 'Activate this plugin.', $this->domain ) ?></a></li>
						<?php else: ?>
							<li><a href="<?php echo get_bloginfo( 'url' ) ?>/wp-admin/plugin-install.php?tab=search&type=term&s=Email+Encoder+Bundle+freelancephp&plugin-search-input=Search+Plugins"><?php _e( 'Get this plugin now', $this->domain ) ?></a></li>
						<?php endif; ?>
						<li><a href="http://wordpress.org/extend/plugins/email-encoder-bundle/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/email-encoder-php-class-wp-plugin/" target="_blank">FreelancePHP.net</a></li>
					</ul>
				</div>
			</div>
		</div>
		</div>
		</form>
		<div class="clear"></div>
	</div>
<?php
	}

	/**
	 * Deactivation plugin method
	 */
	function deactivation() {
		delete_option( $this->options_name );
		unregister_setting( $this->domain, $this->options_name );
	}

	/**
	 * Set options from save values or defaults
	 */
	function _set_options() {
		// set options
		$saved_options = get_option( $this->options_name );

		// set all options
		if ( ! empty( $saved_options ) ) {
			foreach ( $this->options AS $key => $option ) {
				// skip when no saved value for radio 'target'
				if ( ! isset( $saved_options[ $key ] ) AND $key == 'target' )
					continue;

				$this->options[ $key ] = ( empty( $saved_options[ $key ] ) ) ? '' : $saved_options[ $key ];
			}
		}

		// set widget_content filter of Widget Logic plugin
		$widget_logic_opts = get_option( 'widget_logic' );
		if ( is_array( $widget_logic_opts ) AND key_exists( 'widget_logic-options-filter', $widget_logic_opts ) ) {
			$this->options[ 'widget_logic_filter' ] = ( $widget_logic_opts[ 'widget_logic-options-filter' ] == 'checked' ) ? 1 : 0;
		}
	}

} // end class WP_External_Links


/**
 * Create WP_External_Links instance
 */
$WP_External_Links = new WP_External_Links;

/*?> // ommit closing tag, to prevent unwanted whitespace at the end of the parts generated by the included files */