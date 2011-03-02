<?php
/*
Plugin Name: WP External Links
Plugin URI: http://www.freelancephp.net/
Description: Manage the external links on your site: opening in a new window, set link icon, set "external", set "nofollow", set css-class.
Author: Victor Villaverde Laan
Version: 0.20
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
	var $version = '0.20';

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
			'new_window' => TRUE,
			'use_js' => TRUE,
			'target' => '_blank',
			'external' => TRUE,
			'nofollow' => TRUE,
			'icon' => 1,
			'no_icon_class' => 'no-ext-icon',
			'class_name' => 'ext-link',
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
		load_plugin_textdomain( $this->domain, dirname( __FILE__ ) . '/lang/', basename( dirname(__FILE__) ) . '/lang/' );

		// add actions
		add_action( 'init', array( $this, 'init' ), 10000 );
		add_action( 'admin_init', array( $this, 'admin_init' ), 10000 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 10000 );

		// add filters
		add_action( 'wp', array( $this, 'wp' ), 10000 );

		// set uninstall hook
		if ( function_exists( 'register_deactivation_hook' ) )
			register_deactivation_hook( __FILE__, array( $this, 'deactivation' ));
	}

	/**
	 * Callback wp
	 */
	function wp() {
		if ( ! is_admin() && ! is_feed() ) {
			ob_start( array( $this, 'filter_content' ) );
		}
	}

	/**
	 * Callback admin_menu
	 */
	function admin_menu() {
		if ( function_exists( 'add_options_page' ) AND current_user_can( 'manage_options' ) ) {
			// add options page
			$page = add_options_page( 'External Links', 'External Links',
								'manage_options', __FILE__, array( $this, 'options_page' ) );
		}
	}

	/**
	 * Callback init
	 */
	function init() {
		if ( ! is_admin() ) {
			// add wp_head for setting js vars and css style
			add_action( 'wp_head', array( $this, 'wp_head' ) );

			// add stylesheet
			wp_enqueue_style( 'wp-external-links', plugins_url( 'css/external-links.css', __FILE__ ), FALSE, $this->version );

			// set js file
			if ( $this->options[ 'use_js' ] ) {
				wp_enqueue_script( 'wp-external-links', plugins_url( 'js/external-links.js', __FILE__ ), array( 'jquery' ), $this->version );
			}
		}
	}

	/**
	 * Callback wp_head
	 */
	function wp_head() {
		if ( $this->options[ 'use_js' ] ):
?>
<script language="javascript">/* <![CDATA[ */
/* WP External Links Plugin */
var gExtLinks = {
	baseUrl: '<?php echo get_bloginfo( 'url' ) ?>',
	target: '<?php echo $this->options[ 'target' ] ?>'
};
/* ]]> */</script>
<?php
		endif;
	}

	/**
	 * Filter content
	 * @param string $content
	 * @return string
	 */
	function filter_content( $content ) {
		// remove style when no icon classes are found
		if ( strpos( $content, 'ext-icon-' ) == FALSE ) {
			// remove style with id wp-external-links-css
			$content = preg_replace( '/<link(.*?)wp-external-links-css(.*?)\/>[\s+]*/i','' ,$content );
		}

		// get <a> elements
		$a_pattern = '/<[aA](.*?)>(.*?)<\/[aA][\s+]*>/i';
		return preg_replace_callback( $a_pattern, array( $this, 'parse_link' ), $content );
	}

	/**
	 * Make a clean <a> code
	 * @param array $match Result of a preg call in filter_content()
	 * @return string Clean <a> code
	 */
	function parse_link( $match ) {
		$attrs = shortcode_parse_atts( $match[ 1 ] );

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
			if ( $this->options[ 'new_window' ] AND ! $this->options[ 'use_js' ] )
				$attrs[ 'target' ] = $this->options[ 'target' ];
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
<script language="javascript">
jQuery(function( $ ){
	// remove message
	$( '.settings-error' )
		.hide()
		.slideDown( 600 )
		.delay( 3000 )
		.slideUp( 600 );

	// option slide effect
	$( 'input#new_window' )
		.change(function(){
			var anim = $( this ).attr( 'checked' ) ? 'slideDown' : 'slideUp';
			$( 'div.new_window_options' )[ anim ]();
		})
		.change();
})
</script>
	<div class="wrap">
		<div class="icon32" id="icon-options-custom" style="background:url( <?php echo plugins_url( 'images/icon.png', __FILE__ ) ?> ) no-repeat 50% 50%"><br></div>
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
							<th><?php _e( 'Open in new window', $this->domain ) ?></th>
							<td>
								<label><input type="checkbox" name="<?php echo $this->options_name ?>[new_window]" id="new_window" value="1" <?php checked( '1', (int) $options['new_window'] ); ?> />
								<span><?php _e( 'Open external links in a new window (or tab)', $this->domain ) ?></span></label>
								<div class="new_window_options" style="display:none">
									<p><label><input type="checkbox" name="<?php echo $this->options_name ?>[use_js]" value="1" <?php checked( '1', (int) $options['use_js'] ); ?> />
										<span><?php _e( 'Use JavaScript method (for XHTML Strict compliance)', $this->domain ) ?></span></label>
									</p>
									<p><span><?php _e( 'Target:', $this->domain ) ?></span>
										<br/><label><input type="radio" name="<?php echo $this->options_name ?>[target]" value="_blank" <?php checked( '_blank', $options['target'] ); ?> />
										<span><?php _e( '<code>_blank</code>, opens every external link in a new window (or tab)', $this->domain ) ?></span></label>
										<br/><label><input type="radio" name="<?php echo $this->options_name ?>[target]" value="_new" <?php checked( '_new', $options['target'] ); ?> />
										<span><?php _e( '<code>_new</code>, opens all external link in the same new window (or tab)', $this->domain ) ?></span></label>
									</p>
								</div>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Add "external"', $this->domain ) ?></th>
							<td><label><input type="checkbox" id="<?php echo $this->options_name ?>[external]" name="<?php echo $this->options_name ?>[external]" value="1" <?php checked('1', (int) $options['external']); ?> />
								<span><?php _e( 'Automatically add <code>"external"</code> to the rel-attribute of external links', $this->domain ) ?></span></label>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Add "nofollow"', $this->domain ) ?></th>
							<td><label><input type="checkbox" id="<?php echo $this->options_name ?>[nofollow]" name="<?php echo $this->options_name ?>[nofollow]" value="1" <?php checked('1', (int) $options['nofollow']); ?> />
								<span><?php _e( 'Automatically add <code>"nofollow"</code> to the rel-attribute of external links (except to links that already contain <code>"follow"</code>)', $this->domain ) ?></span></label>
							</td>
						</tr>
						</table>
					</fieldset>
					<p class="description"><?php _e( 'This plugin automatically cleans up the code of <code>&lt;a&gt;</code> tags and makes them XHTML valid' ) ?></p>
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
										<label title="<?php _e( 'Choose this icon to show in all external links. You can also add the class \'ext-icon-'. $x .'\' to a specific link.' ) ?>"><input type="radio" name="<?php echo $this->options_name ?>[icon]" value="<?php echo $x ?>" <?php checked( $x, (int) $options['icon'] ); ?> />
										<img src="<?php echo plugins_url( 'images/external-'. $x .'.png', __FILE__ ) ?>" /></label>
										<?php if ( $x % 5 == 0 ): ?>
									</div>
									<div style="width:15%;float:left">
										<?php endif; ?>
									<?php endfor; ?>
									</div>
									<div style="width:29%;float:left;"><?php _e( 'Example:', $this->domain ) ?>
										<br/><img src="<?php echo plugins_url( 'images/link-icon-example.png', __FILE__ ) ?>"	/>
									</div>
									<br style="clear:both" />
								</div>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'No-Icon Class', $this->domain ) ?></th>
							<td><label><input type="text" id="<?php echo $this->options_name ?>[no_icon_class]" name="<?php echo $this->options_name ?>[no_icon_class]" value="<?php echo $options['no_icon_class']; ?>" />
								<span><?php _e( 'Use this class when a link should not show any icon', $this->domain ) ?></span></label></td>
						</tr>
						<tr>
							<th><?php _e( 'Additional Class (optional)', $this->domain ) ?></th>
							<td><label><input type="text" id="<?php echo $this->options_name ?>[class_name]" name="<?php echo $this->options_name ?>[class_name]" value="<?php echo $options['class_name']; ?>" />
								<span><?php _e( 'Add extra class to external links (or leave blank)', $this->domain ) ?></span></label></td>
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
				<h3 class="hndle"><?php _e( 'About' ) ?> WP External Links (v<?php echo $this->version ?>)</h3>
				<div class="inside">
					<ul>
						<li><a href="<?php echo plugins_url( 'readme.txt', __FILE__ ) ?>" target="_blank">readme.txt</a></li>
						<li><a href="http://www.freelancephp.net/wp-external-links-plugin/" target="_blank"><?php _e( 'Plugin on ', $this->domain ) ?> FreelancePHP.net</a></li>
						<li><a href="http://wordpress.org/extend/plugins/wp-external-links/" target="_blank"><?php _e( 'Plugin on ', $this->domain ) ?> WordPress.org</a></li>
						<li><a href="http://www.freelancephp.net/contact/" target="_blank"><?php _e( 'Contact the author', $this->domain ) ?></a></li>
					</ul>
				</div>
			</div>

			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'Other Plugins', $this->domain ) ?></h3>
				<div class="inside">
					<ul>
						<li><a href="http://www.freelancephp.net/email-encoder-php-class-wp-plugin/" target="_blank">WP Email Encoder Bundle</a></li>
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

		if ( ! empty( $saved_options ) ) {
			// set saved option values
			if ( isset( $saved_options['new_window'] ) AND ! isset( $saved_options['target'] ) ) {
				// convert values from version <= 0.11
				$new_window = ( empty( $saved_options['new_window'] ) ) ? $this->options['new_window'] : $saved_options['new_window'];
				$this->options['new_window'] = ( $new_window != 1 );
				$this->options['use_js'] = ( $new_window == 4 OR $new_window == 5 );
				$this->options['target'] = ( $new_window == 2 OR $new_window == 4 ) ? '_blank' : '_new';
			} else {
				$this->options['new_window'] = ! empty( $saved_options['new_window'] );
				$this->options['use_js'] = ! empty( $saved_options['use_js'] );
				$this->options['target'] = $saved_options['target'];
			}
			$this->options['external'] = ! empty( $saved_options['external'] );
			$this->options['nofollow'] = ! empty( $saved_options['nofollow'] );
			$this->options['icon'] = $saved_options['icon'];
			$this->options['no_icon_class'] = ( ! isset( $saved_options['no_icon_class'] ) )
											? $this->options['no_icon_class']
											: $saved_options['no_icon_class'];
			$this->options['class_name'] = $saved_options['class_name'];
		}
	}

} // end class WP_External_Links


/**
 * Create WP_External_Links instance
 */
$WP_External_Links = new WP_External_Links;

?>