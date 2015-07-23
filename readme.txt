=== WP External Links (nofollow new window seo) ===
Contributors: freelancephp
Tags: links, external, icon, target, _blank, _new, _none, rel, nofollow, new window, new tab, javascript, xhtml, seo
Requires at least: 3.6.0
Tested up to: 4.2.3
Stable tag: 1.80

Open external links in a new window or tab, adding "nofollow", set link icon, styling, SEO friendly options and more. Easy install and go.

== Description ==

Configure settings for all external links on your site.

= Features =
* Open in new window or tab
* Add "nofollow"
* Choose from 20 icons
* Set other link options (like classes, title etc)
* Make it SEO friendly
* Compatible with WPMU (Multisite)

= Easy to use =
After activating the plugin all options are already set to make your external links SEO friendly. Optionally you can also set the target for opening in a new window or tab or styling options, like adding an icon.

= On the fly =
The plugin will change the output of the (external) links on the fly. So when you deactivate the plugin, all contents will remain the same as it was before installing the plugin.

= Sources =
* [Documentation](http://wordpress.org/extend/plugins/wp-external-links/other_notes/)
* [FAQ](http://wordpress.org/extend/plugins/wp-external-links/faq/)
* [Github](https://github.com/freelancephp/WP-External-Links)

= Like this plugin? =
[Send your review](http://wordpress.org/support/view/plugin-reviews/wp-external-links-plugin).


== Installation ==

1. Go to `Plugins` in the Admin menu
1. Click on the button `Add new`
1. Search for `WP External Links` and click 'Install Now' OR click on the `upload` link to upload `wp-external-links.zip`
1. Click on `Activate plugin`

== Frequently Asked Questions ==

= How to treat internal links as external links? =

You could add `rel="external"` to those internal links that should be treated as external. The plugin settings will also be applied to those links.

= Why are links to my own domain treated as external links? =

Only links pointing to your WordPress site (`wp_url`) are by default threaded as internal links.
There is an option to mark all links to your domain (and subdomains) as internal links.

= How to treat links to subdomains as internal links? =

Add your main domain to the option "Ingore links (URL) containing..." and they will not be treated as external.

= How to create a redirect for external links? =

By using the `wpel_external_link` filter. Add this code to functions.php of your theme:

`function redirect_external_link($created_link, $original_link, $label, $attrs = array()) {
    $href = $attrs['href'];

    // create redirect url
    $href_new = get_bloginfo('wpurl') . '/redirect.php?url=' . urlencode($attrs['href']);

    return str_replace($href, $href_new, $created_link);
}

add_filter('wpel_external_link', 'redirect_external_link', 10, 4);`

= Set a font icon for external links, like [Font Awesome Icons](http://fortawesome.github.io/Font-Awesome/)? =

Use the `wpel_external_link` filter and add this code to functions.php of your theme:

`function set_font_icon_on_external_link($created_link, $original_link, $label, $attrs = array()) {
    $label_with_font = $label . ' <i class="fa fa-external-link"></i>';
    return str_replace($label, $label_with_font, $created_link);
}

add_filter('wpel_external_link', 'set_font_icon_on_external_link', 10, 4);`

The CSS of Font Awesome Icons alse needs to be loaded. To do so also add this code:

`function add_font_awesome_style() {
    wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css');
}

add_action('wp_enqueue_scripts', 'add_font_awesome_style');`

= How to open external links in popup browser window with a certain size? =

By adding this JavaScript code to your site:

`jQuery(function ($) {

    $('a[rel*="external"]').click(function (e) {
        // open link in popup window
        window.open($(this).attr('href'), '_blank', 'width=800, height=600');

        // stop default and other behaviour
        e.preventDefault();
        e.stopImmediatePropagation();
    });

});`

See more information on the [window.open() method](http://www.w3schools.com/jsref/met_win_open.asp).

= How to add an confirm (or alert) when opening external links? =

Add this JavaScript code to your site:

`jQuery(function ($) {

    $('a[rel*="external"]').click(function (e) {
        if (!confirm('Are you sure you want to open this link?')) {
            // cancelled
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });

});`

= How to make all internal links "follow"? =

By using the `wp_internal_link` filter. Add this code to functions.php of your theme:

`function set_follow_to_internal_link($link, $label, $attrs) {
    return str_replace('nofollow', 'follow', $link);
}

add_filter('wpel_internal_link', 'set_follow_to_internal_link', 10, 3);
`


[Do you have a question? Please ask me](http://www.freelancephp.net/contact/)

== Screenshots ==

1. Link Icon on the Site
1. Admin Settings Page

== Documentation ==

After activating the plugin all options are already set to make your external links SEO friendly. Optionally you can also set the target for opening in a new window or tab or styling options, like adding an icon.

= Action hook: wpel_ready =
The plugin also has a hook when ready, f.e. to add extra filters:
`function extra_filters($filter_callback, $object) {
	add_filter('some_filter', $filter_callback);
}

add_action('wpel_ready', 'extra_filters');`

= Filter hook 1: wpel_external_link =
The `wpel_external_link` filter gives you the possibility to manipulate output of all external links, like:
`add_filter('wpel_external_link', 'wpel_special_external_link', 10, 5);

function wpel_special_external_link($created_link, $original_link, $label, $attrs, $is_ignored_link) {
	// skip links that contain the class "not-external"
	if (isset($attrs['class']) && strpos($attrs['class'], 'not-external') !== false) {
		return $original_link;
	}

	return '<b>'. $created_link .'</b>';
}`

Now all external links will be processed and wrapped around a `<b>`-tag. And links containing the class "not-external" will not be processed by the plugin at all (and stay the way they are).

= Filter hook 2: wpel_external_link_attrs =

The `wpel_external_link_attrs` filter can be used to manipulate attributes of external links.
`add_filter('wpel_external_link_attrs', 'wpel_custom_title', 10, 3);

function wpel_custom_title($attrs, $original_attrs, $label) {
    if (empty($attrs['title']) && isset($attrs['href'])) {
        $attrs['title'] = $attrs['href'];
    }

    return $attrs;
}`

In this example when an external links has an empty title, the title will contain the url.

= Filter hook 3: wpel_ignored_external_link =
With the `wpel_ignored_external_link` filter you can manipulate the output of the ignored external links.
`add_filter('wpel_ignored_external_link', 'wpel_custom_ignored_link', 10, 3);

function wpel_custom_ignored_link($link, $label, $attrs) {
    return '<del>'. $link  .'</del>';
}`

In this case all ignored links will be marked as deleted (strikethrough).

= Filter hook 4: wpel_internal_link =
With the `wpel_internal_link` filter you can manipulate the output of all internal links on your site. F.e.:
`add_filter('wpel_internal_link', 'special_internal_link', 10, 3);

function special_internal_link($link, $label, $attrs) {
    return '<b>'. $link  .'</b>';
}`

In this case all internal links will be made bold.


See [FAQ](https://wordpress.org/plugins/wp-external-links/faq/) for more possibilities of using these filters.


= Credits =
* [jQuery Tipsy Plugin](http://plugins.jquery.com/project/tipsy) made by [Jason Frame](http://onehackoranother.com/)
* [phpQuery](http://code.google.com/p/phpquery/) made by [Tobiasz Cudnik](http://tobiasz123.wordpress.com)
* [Icon](http://findicons.com/icon/164579/link_go?id=427009) made by [FatCow Web Hosting](http://www.fatcow.com/)

== Changelog ==

= 1.80 =
* Added filter hook wpel_external_link_attrs to change attributes before creating the link
* Added filter hook wpel_ignored_external_links
* Removed phpQuery option
* Moved ignore selectors option

= 1.70 =
* Added option to ignore all subdomains

= 1.62 =
* Fixed php error when using phpQuery option

= 1.61 =
* Fixed deprecated split() function
* Fixed deprecated $wp_version

= 1.60 =
* Added option to replace "follow" values of external links with "nofollow"
* Updated FAQ with custom solutions

= 1.56 =
* Fixed bug jQuery as dependency for js scripts
* Fixed bug "no-icon class in same window" working with javascript
* Fixed bug setting defaults on installation

= 1.55 =
* Fixed bug JS error: Uncaught TypeError: undefined is not a function
* Fixed bug PHP error for links without href attribute ("undefined index href")
* Replaced deprecated jQuery .live() to .on()  (contribution by Alonbilu)

= 1.54 =
* Fixed bug opening links containing html tags (like <b>)

= 1.53 =
* Fixed bug also opening ignored URL's on other tab/window when using javascript
* Changed javascript open method (data-attribute)

= 1.52  =
* Added filter hook wpel_internal_link
* Fixed use_js option bug
* Fixed bug loading non-existing stylesheet
* Minified javascripts

= 1.51 =
* Fixed also check url's starting with //
* Fixed wpel_external_link also applied on ignored links

= 1.50 =
* Removed stylesheet file to save extra request
* Added option for loading js file in wp_footer
* Fixed bug with data-* attributes
* Fixed bug url's with hash at the end
* Fixed PHP errors

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
