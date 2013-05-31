=== WP External Links ===
Contributors: freelancephp
Tags: links, external, icon, target, _blank, _new, _none, rel, nofollow, new window, new tab, javascript, xhtml, seo
Requires at least: 3.2.0
Tested up to: 3.5.1
Stable tag: 1.41

Open external links in a new window or tab, adding "nofollow", set link icon, styling, SEO friendly options and more. Easy install and go.

== Description ==

Configure settings for all external links on your site.

= Features =
* Open external links in new window or tab
* Add "nofollow"
* Set link title
* Set link icon
* Set classes (for your own styling)
* Set no-icon class
* SEO friendly

= Easy to use =
After activating the plugin all options are already set to make your external links SEO friendly. Optionally you can also set the target for opening in a new window or tab or styling options, like adding an icon.

[See more documentation](http://wordpress.org/extend/plugins/wp-external-links/other_notes/).

= Support =
This plugin has the same [requirements](http://wordpress.org/about/requirements/) as WordPress.
If you are experiencing any problems, just take a look at the [FAQ](http://wordpress.org/extend/plugins/wp-external-links/faq/) or report it in the [support section](http://wordpress.org/support/plugin/wp-external-links). You can also send me a mail with [this contactform](http://www.freelancephp.net/contact/).

= Like this plugin? =
This plugin is free and does not need any donations. You could show your appreciation by rating this plugin and/or [posting a comment](http://www.freelancephp.net/wp-external-links-plugin/) on my blog.


== Installation ==

1. Go to `Plugins` in the Admin menu
1. Click on the button `Add new`
1. Search for `WP External Links` and click 'Install Now' OR click on the `upload` link to upload `wp-external-links.zip`
1. Click on `Activate plugin`

== Frequently Asked Questions ==

[Do you have a question? Please ask me](http://www.freelancephp.net/contact/)

== Screenshots ==

1. Link Icon on the Site
1. Admin Settings Page

== Documentation ==

After activating the plugin all options are already set to make your external links SEO friendly. Optionally you can also set the target for opening in a new window or tab or styling options, like adding an icon.

= Action hook =
The plugin also has a hook when ready, f.e. to add extra filters:
`function extra_filters($filter_callback, $object) {
	add_filter('some_filter', $filter_callback);
}
add_action('wpel_ready', 'extra_filters');`

= Filter hook =
The wpel_external_link filter gives you the possibility to manipulate output of the mailto created by the plugin, like:
`function special_external_link($created_link, $original_link, $label, $attrs = array()) {
	// skip links that contain the class "not-external"
	if (isset($attrs['class']) && strpos($attrs['class'], 'not-external') !== false) {
		return $original_link;
	}

	return '<b>'. $created_link .'</b>';
}
add_filter('wpel_external_link', 'special_external_link', 10, 4);`

Now all external links will be processed and wrapped around a `<b>`-tag. And links containing the class "not-external" will not be processed by the plugin at all (and stay the way they are).

= Credits =
* [jQuery Tipsy Plugin](http://plugins.jquery.com/project/tipsy) made by [Jason Frame](http://onehackoranother.com/)
* [phpQuery](http://code.google.com/p/phpquery/) made by [Tobiasz Cudnik](http://tobiasz123.wordpress.com)
* [Icon](http://findicons.com/icon/164579/link_go?id=427009) made by [FatCow Web Hosting](http://www.fatcow.com/)

== Changelog ==

= 1.41 =
* Fixed Bug: wpmel_external_link filter hook was not working correctly

= 1.40 =
* Added action hook wpel_ready
* Added filter hook wpel_external_link
* Added output flush on wp_footer
* Fixed Bug: spaces before url in href-attribute not recognized as external link
* Fixed Bug: external links not processed (regexpr tag conflict starting with an a, like <aside> or <article>)
* Cosmetic changes: added "Admin Settings", replaced help icon, restyled tooltip texts, removed "About this plugin" box

= 1.31 =
* Fixed passing arguments by reference using & (deprecated for PHP 5.4+)
* Fixed options save failure by adding a non-ajax submit fallback

= 1.30 =
* Re-arranged options in metaboxes
* Added option for no icons on images

= 1.21 =
* Fixed phpQuery bugs (class already exists and loading stylesheet)
* Solved php notices

= 1.20 =
* Added option to ignore certain links or domains
* Solved tweet button problem by adding link to new ignore option
* Made JavaScript method consistent to not using JS
* Solved PHP warnings
* Solved bug adding own class
* Changed bloginfo "url" to "wpurl"

= 1.10 =
* Resolved old parsing method (same as version 0.35)
* Option to use phpQuery for parsing (for those who didn't experience problems with version 1.03)

= 1.03 =
* Workaround for echo DOCTYPE bug (caused by attributes in the head-tag)

= 1.02 =
* Solved the not working activation hook

= 1.01 =
* Solved bug after live testing

= 1.00 =
* Added option for setting title-attribute
* Added option for excluding filtering certain external links
* Added Admin help tooltips using jQuery Tipsy Plugin
* Reorginized files and refactored code to PHP5 (no support for PHP4)
* Added WP built-in meta box functionallity (using the `WP_Meta_Box_Page` Class)
* Reorganized saving options and added Ajax save method (using the `WP_Option_Forms` Class)
* Removed Regexp and using phpQuery
* Choose menu position for this plugin (see "Screen Options")
* Removed possibility to convert all `<a>` tags to xhtml clean code (so only external links will be converted)
* Removed "Solve problem" options

= 0.35 =
* Widget Logic options bug

= 0.34 =
* Added option only converting external `<a>` tags to XHTML valid code
* Changed script attribute `language` to `type`
* Added support for widget_content filter of the Logic Widget plugin

= 0.33 =
* Added option to fix js problem
* Fixed PHP / WP notices

= 0.32 =
* For jQuery uses live() function so also opens dynamicly created links in given target
* Fixed bug of changing `<abbr>` tag
* Small cosmetical adjustments

= 0.31 =
* Small cosmetical adjustments

= 0.30 =
* Improved Admin Options, f.e. target option looks more like the Blogroll target option
* Added option for choosing which content should be filtered

= 0.21 =
* Solved bug removing icon stylesheet

= 0.20 =
* Put icon styles in external stylesheet
* Can use "ext-icon-..." to show a specific icon on a link
* Added option to set your own No-Icon class
* Made "Class" optional, so it's not used for showing icons anymore
* Added 3 more icons

= 0.12 =
* Options are organized more logical
* Added some more icons

= 0.11 =
* JavaScript uses window.open() (tested in FireFox Opera, Safari, Chrome and IE6+)
* Also possible to open all external links in the same new window
* Some layout changes on the Admin Options Page

= 0.10 =
* Features: opening in a new window, set link icon, set "external", set "nofollow", set css-class
* Replaces external links by clean XHTML <a> tags
* Internalization implemented (no language files yet)
