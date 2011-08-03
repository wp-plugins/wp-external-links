<?php defined( 'ABSPATH' ) OR die( 'No direct access.' );
if ( ! class_exists( 'WP_External_Links' ) ):

/**
 * Class WP_External_Links
 * @category WordPress Plugins
 */
final class WP_External_Links {

	/**
	 * Admin object
	 * @var Admin_External_Links
	 */
	private $admin = NULL;


	/**
	 * Constructor
	 */
	public function __construct() {
		// set admin object
		$this->admin = new Admin_External_Links();

		// add actions
		add_action( 'wp', array( $this, 'call_wp' ) );
	}

	/**
	 * Quick helper method for getting saved option values
	 * @param string $key
	 * @param string $option_name  Optional, default looking in 'general' settings
	 * @return mixed
	 */
	public function get_opt( $key, $option_name = 'general' ) {
		return $this->admin->form->value( $key, NULL, $option_name );
	}

	/**
	 * wp callback
	 */
	public function call_wp() {
		if ( ! is_admin() && ! is_feed() ) {
			// Include phpQuery
			require_once( 'phpQuery/phpQuery.php' );

			// add wp_head for setting js vars and css style
			add_action( 'wp_head', array( $this, 'call_wp_head' ) );

			// add stylesheet
			wp_enqueue_style( 'wp-external-links', plugins_url( 'css/external-links.css', WP_EXTERNAL_LINKS_FILE ), FALSE, WP_EXTERNAL_LINKS_VERSION );

			// set js file
			if ( $this->get_opt( 'use_js' ) )
				wp_enqueue_script( 'wp-external-links', plugins_url( 'js/external-links.js', WP_EXTERNAL_LINKS_FILE ), array( 'jquery' ), WP_EXTERNAL_LINKS_VERSION );

			// filters
			if ( $this->get_opt( 'filter_page' ) ) {
				// filter body
				ob_start( array( $this, 'call_filter_content' ) );

			} else {
				// set filter priority
				$priority = 1000000000;

				// content
				if ( $this->get_opt( 'filter_posts' ) ) {
					add_filter( 'the_title', array( $this, 'call_filter_content' ), $priority );
					add_filter( 'the_content', array( $this, 'call_filter_content' ), $priority );

					add_filter( 'get_the_excerpt', array( $this, 'call_filter_content' ), $priority );
					// redundant:
					//add_filter( 'the_excerpt', array( $this, 'call_filter_content' ), $priority );
				}

				// comments
				if ( $this->get_opt( 'filter_comments' ) ) {
					add_filter( 'get_comment_text', array( $this, 'call_filter_content' ), $priority );
					// redundant:
					//add_filter( 'comment_text', array( $this, 'call_filter_content' ), $priority );

					add_filter( 'comment_excerpt', array( $this, 'call_filter_content' ), $priority );
					// redundant:
					//add_filter( 'get_comment_excerpt', array( $this, 'call_filter_content' ), $priority );

					add_filter( 'comment_url', array( $this, 'call_filter_content' ), $priority );
					add_filter( 'get_comment_author_url', array( $this, 'call_filter_content' ), $priority );
					add_filter( 'get_comment_author_link', array( $this, 'call_filter_content' ), $priority );
					add_filter( 'get_comment_author_url_link', array( $this, 'call_filter_content' ), $priority );
				}

				// widgets
				if ( $this->get_opt( 'filter_widgets' ) ) {
					if ( $this->admin->check_widget_content_filter() ) {
						// only if Widget Logic plugin is installed and 'widget_content' option is activated
						add_filter( 'widget_content', array( $this, 'call_filter_content' ), $priority );
					} else {
						// filter text widgets
						add_filter( 'widget_title', array( $this, 'call_filter_content' ), $priority );
						add_filter( 'widget_text', array( $this, 'call_filter_content' ), $priority );
					}
				}
			}
		}
	}

	/**
	 * wp_head callback
	 */
	public function call_wp_head() {
		if ( $this->get_opt( 'use_js' ) AND $this->get_opt( 'target' ) != '_none' ):
			// set exclude class
			$excludeClass = ( $this->get_opt( 'no_icon_same_window', 'style' ) AND $this->get_opt( 'no_icon_class', 'style' ) )
							? $this->get_opt( 'no_icon_class', 'style' )
							: '';
?>
<script type="text/javascript">/* <![CDATA[ */
/* WP External Links Plugin */
var wpExtLinks = { baseUrl: '<?php echo get_bloginfo( 'url' ) ?>',target: '<?php echo $this->get_opt( 'target' ) ?>',excludeClass: '<?php echo $excludeClass ?>' };
/* ]]> */</script>
<?php
		endif;
	}

	/**
	 * Filter content
	 * @param string $content
	 * @return string
	 */
	public function call_filter_content( $content ) {
		// Workaround: remove <head>-attributes before using phpQuery
		$regexp_head = '/<head(.*?)>/is';
		$clean_head = '<head>';

		// set simple <head> without attributes
		preg_match( $regexp_head, $content, $matches );
		$original_head = $matches[ 0 ];
		$content = str_replace( $original_head, $clean_head, $content );

		//phpQuery::$debug = true;

		// set document
		$doc = phpQuery::newDocument( $content );

		// @todo
		/*
		$regexp_xml = '/<\?xml(.*?)\?>/is';
		$regexp_xhtml = '/<!DOCTYPE(.*?)xhtml(.*?)>/is';

		if ( preg_match( $regexp_xml, $content ) > 0 ) {
			$doc = phpQuery::newDocumentXML( $content, get_bloginfo( 'charset' ) );
		} elseif ( preg_match( $regexp_xhtml, $content ) > 0 ) {
			$doc = phpQuery::newDocumentXHTML( $content, get_bloginfo( 'charset' ) );
		} else {
			$doc = phpQuery::newDocumentHTML( $content, get_bloginfo( 'charset' ) );
		}
		*/

		// remove style when no icon classes are found
		if ( strpos( $content, 'ext-icon-' ) === FALSE ) {
			// remove icon css
			$css = $doc->find( 'link#wp-external-links-css' )->eq(0);
			$css->remove();
		}

		$excl_sel = $this->get_opt( 'filter_excl_sel' );

		// set excludes
		if ( ! empty( $excl_sel ) ) {
			$excludes = $doc->find( $excl_sel );
			$excludes->filter( 'a' )->attr( 'excluded', true );
			$excludes->find( 'a' )->attr( 'excluded', true );
		}

		// get <a>-tags
		$links = $doc->find( 'a' );

		// set links
		$count = count( $links );

		for( $x = 0; $x < $count; $x++ ) {
			$a = $links->eq( $x );
	
			if ( ! $a->attr( 'excluded' ) )
				$this->set_link( $links->eq( $x ) );
		}

		// remove excluded
		if ( ! empty( $excl_sel ) ) {
			$excludes = $doc->find( $excl_sel );
			$excludes->filter( 'a' )->removeAttr( 'excluded' );
			$excludes->find( 'a' )->removeAttr( 'excluded' );
		}

		// get document content
		$content = (string) $doc;

		// recover original <head> with attributes
		$content = str_replace( $clean_head, $original_head, $content );

		return $content;
	}

	/**
	 * Set link...
	 * @param Node $a
	 * @return Node
	 */
	public function set_link( $a ) {
		$href = strtolower( $a->attr( 'href' ) . '' );
		$rel = strtolower( $a->attr( 'rel' ) . '' );

		// check if it is an external link and not excluded
		if ( ! $this->is_external( $href, $rel ) )
			return $a;

		// add "external" to rel-attribute
		if ( $this->get_opt( 'external' ) ){
			$this->add_attr_value( $a, 'rel', 'external' );
		}

		// add "nofollow" to rel-attribute, when doesn't have "follow"
		if ( $this->get_opt( 'nofollow' ) AND strpos( $rel, 'follow' ) === FALSE ){
			$this->add_attr_value( $a, 'rel', 'nofollow' );
		}

		// set title
		$title = str_replace( '%title%', $a->attr( 'title' ), $this->get_opt( 'title' ) );
		$a->attr( 'title', $title );

		// add icon class, unless no-icon class isset or another icon class ('ext-icon-...') is found
		if ( $this->get_opt( 'icon', 'style' ) > 0 AND ( ! $this->get_opt( 'no_icon_class', 'style' ) OR strpos( $a->attr( 'class' ), $this->get_opt( 'no_icon_class', 'style' ) ) === FALSE ) AND strpos( $a->attr( 'class' ), 'ext-icon-' ) === FALSE  ){
			$icon_class = 'ext-icon-'. $this->get_opt( 'icon', 'style' );
			$a->addClass( $icon_class );
		}

		// add user-defined class
		if ( $this->get_opt( 'class_name', 'style' ) ){
			$a->addClass( $this->get_opt( 'class_name', 'style' ) );
		}

		// set target
		if ( $this->get_opt( 'target' ) != '_none' AND ! $this->get_opt( 'use_js' ) AND ( ! $this->get_opt( 'no_icon_same_window', 'style' ) OR ! $this->get_opt( 'no_icon_class', 'style' ) OR strpos( $a->attr( 'class' ), $this->get_opt( 'no_icon_class', 'style' ) ) === FALSE ) )
			$a->attr( 'target', $this->get_opt( 'target' ) );

		return $a;
	}

	/**
	 * Add value to attribute
	 * @param Node   $node
	 * @param string $attr
	 * @param string $value
	 * @return New value
	 */
	private function add_attr_value( $node, $attr, $value ) {
		$old_value = $node->attr( $attr );

		if ( empty( $old_value ) )
			$old_value = '';

		$split = split( ' ', strtolower( $old_value ) );

		if ( in_array( $value, $split ) ) {
			$value = $old_value;
		} else {
			$value = ( empty( $old_value ) )
								? $value
								: $old_value .' '. $value;
		}

		$node->attr( $attr, $value );

		return $value;
	}

	/**
	 * Check if link is external
	 * @param string $href
	 * @param string $rel
	 * @return boolean
	 */
	private function is_external( $href, $rel ) {
		return ( isset( $href ) AND ( strpos( $rel, 'external' ) !== FALSE
												OR  ( strpos( $href, strtolower( get_bloginfo( 'url' ) ) ) === FALSE )
														AND ( substr( $href, 0, 7 ) == 'http://'
																OR substr( $href, 0, 8 ) == 'https://'
																OR substr( $href, 0, 6 ) == 'ftp://' ) ) );
	}

} // End WP_External_Links Class

endif;