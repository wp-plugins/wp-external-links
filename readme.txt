=== WP External Links ===
Contributors: freelancephp
Tags: links, external, new window, icon, target, _blank, _new, _top, _none, rel, nofollow, javascript, xhtml strict
Requires at least: 2.7.0
Tested up to: 3.1.0
Stable tag: 0.32

Manage external links on your site: open in new window/tab, set link icon, add "external", add "nofollow" and more.

== Description ==

Manage the external links on your site.

= Features =
* Open in a new window/tab
* Set link icon
* Add "external"
* Add "nofollow"
* Set no-icon class
* Set additional classes (for your own styling)

Supports PHP4.3+ and up to latest WP version.

== Installation ==
1. Go to `Plugins` in the Admin menu
1. Click on the button `Add new`
1. Search for `WP External Links` and click 'Install Now' or click on the `upload` link to upload `wp-external-links.zip`
1. Click on `Activate plugin`

== Frequently Asked Questions ==

= I have a problem when defining links with JavaScript. What to do?  =
When having problems defining links in JavaScript like:
`document.write( "<a href=\"http:://google.com\">Google</a>" );`

You could use single quotes for defining the string and therefore remove the double quotes, like:
`document.write( '<a href="http:://google.com">Google</a>' );`

Or you could prevent the plugin filtering the link by escaping the last slash (`</a>`) like:
`document.write( '<a href="http:://google.com">Google<\/a>' );`

In the last case when using the JavaScript method and jQuery the link would still be opened in the target given on the options page.

[Do you have a question? Please ask me](http://www.freelancephp.net/contact/)

== Screenshots ==

1. Link Icon on the Site
1. Admin Settings Page

== Other notes ==

= Credits =
* Title icon on Admin Options Page was made by [FatCow Web Hosting](http://www.fatcow.com/) taken form [iconfinder](http://findicons.com/icon/164579/link_go?id=427009)

== Changelog ==

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

== Upgrade Notice ==

= 0.32 =
* For jQuery uses live() function so also opens dynamicly created links in given target
* Fixed bug of changing `<abbr>` tag
* Small cosmetical adjustments
