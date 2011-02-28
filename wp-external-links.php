<?php
/*
Plugin Name: WP External Links
Plugin URI: http://www.freelancephp.net/
Description: Manage the external links on your site: opening in a new window, set link icon, set "external", set "nofollow", set css-class.
Author: Victor Villaverde Laan
Version: 0.11
Author URI: http://www.freelancephp.net
License: Dual licensed under the MIT and GPL licenses
*/

/**
 * Class WP_External_Links
 * @category WordPress Plugins
 */
class WP_External_Links {

	/**
	 * Used as prefix for options entry and could be used as text domain (for translations)
	 * @var string
	 */
	var $domain = 'WP_External_Links';

	/**
	 * @var string
	 */
	var $options_name = 'WP_External_Links_options';

	/**
	 * @var array
	 */
	var $options = array(
			'new_window' => TRUE,
			'use_js' => TRUE,
			'target' => '_blank',
			'external' => TRUE,
			'nofollow' => TRUE,
			'icon' => 1,
			'class_name' => 'external-link',
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

			// set js file
			if ( $this->options[ 'use_js' ] ) {
				wp_enqueue_script( 'external-links', plugins_url( 'js/external-links.js', __FILE__ ), array( 'jquery' ), '0.11' );
			}
		}
	}

	/**
	 * Callback wp_head
	 */
	function wp_head() {
?>
<?php if ( $this->options[ 'use_js' ] ): ?>
<!-- JS External Links Plugin -->
<script language="javascript">
/* <![CDATA[ */
var gExtLinks = {
	baseUrl: '<?php echo get_bloginfo( 'url' ) ?>',
	target: '<?php echo $this->options[ 'target' ] ?>'
};
/* ]]> */
</script>
<!-- /JS External Links Plugin -->
<?php endif; ?>
<?php if ( $this->options[ 'class_name' ] AND $this->options[ 'icon' ] > 0 ): ?>
<!-- Style External Links Plugin -->
<style type="text/css">
	.<?php echo $this->options[ 'class_name' ] ?> {
		 background: url( <?php echo plugins_url( 'images/external-'. $this->options[ 'icon' ] .'.png', __FILE__ ) ?> ) no-repeat right center;
		 padding-right: 15px;
	};
</style>
<!-- /Style External Links Plugin -->
<?php endif; ?>
<?php
	}

	/**
	 * Filter content
	 */
	function filter_content( $content ) {
		$a_pattern = '/<[aA](.*?)>(.*?)<\/[aA][\s+]*>/i';
		return preg_replace_callback( $a_pattern, array( $this, 'parse_link' ), $content );
	}

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

			// set class
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
	$( 'input#new_window' ).change(function(){
		var anim = $( this ).attr( 'checked' ) ? 'slideDown' : 'slideUp';
		$( 'div.new_window_options' )[ anim ]();
	})
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
								<div class="new_window_options">
								<p><label><input type="checkbox" name="<?php echo $this->options_name ?>[use_js]" value="1" <?php checked( '1', (int) $options['use_js'] ); ?> />
									<span><?php _e( 'Use JavaScript method (for XHTML Strict compliance)', $this->domain ) ?></span></label>
								</p><p><span><?php _e( 'Target:', $this->domain ) ?></span>
									<br/><label><input type="radio" name="<?php echo $this->options_name ?>[target]" value="_blank" <?php checked( '_blank', $options['target'] ); ?> />
									<span><?php _e( '_blank, opens every external link in a new window (or tab)', $this->domain ) ?></span></label>
									<br/><label><input type="radio" name="<?php echo $this->options_name ?>[target]" value="_new" <?php checked( '_new', $options['target'] ); ?> />
									<span><?php _e( '_new, opens all external link in the same new window (or tab)', $this->domain ) ?></span></label>
								</p>
								</div>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Add "external"', $this->domain ) ?></th>
							<td><label><input type="checkbox" id="<?php echo $this->options_name ?>[external]" name="<?php echo $this->options_name ?>[external]" value="1" <?php checked('1', (int) $options['external']); ?> />
								<span><?php _e( 'Automatically add "external" to the rel-attribute of external links <code>rel="external"</code>', $this->domain ) ?></span></label>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Add "nofollow"', $this->domain ) ?></th>
							<td><label><input type="checkbox" id="<?php echo $this->options_name ?>[nofollow]" name="<?php echo $this->options_name ?>[nofollow]" value="1" <?php checked('1', (int) $options['nofollow']); ?> />
								<span><?php _e( 'Automatically add "nofollow" to the rel-attribute of external links <code>rel="nofollow"</code> (except to links that already contain "follow")', $this->domain ) ?></span></label>
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
							<th><?php _e( 'Classname', $this->domain ) ?></th>
							<td><label><input type="text" id="<?php echo $this->options_name ?>[class_name]" name="<?php echo $this->options_name ?>[class_name]" value="<?php echo $options['class_name']; ?>" />
								<span><?php _e( 'Add this classname to external links (or leave blank)', $this->domain ) ?></span></label></td>
						</tr>
						<tr>
							<th><?php _e( 'Show icon', $this->domain ) ?>
	 						</th>
							<td>
								<div style="width:74%;float:right"><?php _e( 'Icon Example:', $this->domain ) ?>
									<br/>
									<br/><img src="<?php echo plugins_url( 'images/link-icon-example.png', __FILE__ ) ?>"	/>
									<br/>
									<br/><span class="description"><?php _e( 'Note: icon only works if classname is given', $this->domain ) ?></span>
								</div>
								<div style="width:25%;float:left">
								<label><input type="radio" name="<?php echo $this->options_name ?>[icon]" value="0" <?php checked('0', (int) $options['icon']); ?> />
								<span><?php _e( 'No icon', $this->domain ) ?></span></label>
						<?php for ( $x = 1; $x <= 17; $x++ ): ?>
							<br/><label><input type="radio" name="<?php echo $this->options_name ?>[icon]" value="<?php echo $x ?>" <?php checked( $x, (int) $options['icon'] ); ?> />
								<img src="<?php echo plugins_url( 'images/external-'. $x .'.png', __FILE__ ) ?>" /></label>
						<?php endfor; ?>
								</div>
							</td>
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
				<h3 class="hndle"><?php _e( 'About' ) ?> WP External Links</h3>
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
			$this->options['class_name'] = $saved_options['class_name'];
		}
	}

} // end class WP_External_Links


/**
 * Create WP_External_Links instance
 */
$WP_External_Links = new WP_External_Links;

?>