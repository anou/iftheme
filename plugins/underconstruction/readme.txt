=== underConstruction ===
Contributors: Jeremy Massel
Donate link: http://masseltech.com/donate/
Tags: construction, under construction, private, preview, security, coming soon
Requires at least: 2.7
Tested up to: 3.4.2
Stable tag: 1.08

Creates a 'Coming Soon' page that will show for all users who are not logged in

== Description ==

Creates a 'Coming Soon' page that will show for all users who are not logged in. Useful for developing a site on a live server, without the world being able to see it

== Installation ==

1. Upload the folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. If you want to change the look of the page that is displayed, click Settings->Under Construction and change the settings there.

== Frequently Asked Questions ==

= I'm finished with it, and disabled the plugin, but the "under construction" message is still showing up! =
If you've disabled the plugin, it won't show anything anymore. To be extra super-sure, try deleting the plugin files. Usually, though, the issue is that you're seeing a cached version of the page. Try force-refreshing your browser, and then try clearing your cache on the server and force refreshing again. If you have a caching plugin like W3 Total Cache, make sure you clear that too!

= I can't see the under construction page! =
As long as you're logged in, you won't be able to see it. That's a feature! This way, while you're logged in you can work as usual. To preview what it looks like, either a) log out, or b) try viewing it in another browser

= What kind of HTML can I put in? =
You enter the contents of the entire HTML file. You can include inline styles, or links to external style sheets and external images.

= I have an idea for your plugin! =
That's great. I'm always open to user input, and I'd like to add anything I think will be useful to a lot of people. Visit the homepage for this plugin and leave a comment, and I'll add the functionality as soon as I can.

= I found a bug! =
Oops. That's sure awkward. If you find a problem with this plugin that you can reproduce, if you wouldn't mind leaving a message on the homepage for this plugin with how you made it break, I'd really like to try and fix it! Also, this is incompatible with a couple plugins out there, specifically ones that change the default login url.

= This plugin has helped me a lot, how can I support it? =
I've had a few people ask me this. If you like it, please go to WordPress.org and rate it! Then more people can enjoy it. If you REALLY like it, you can always buy me a coffee. :) There's a donate link on my site.

= You didn't answer my question here =
Sorry, I get a lot of questions. But visit the homepage for this plugin and leave me a comment. They go right to my inbox, and well I might not be able to for a few days, I promise I'll get back to you.

== Changelog ==

= 1.08 =
* Fixed an embarrassingly old bug caused by using the old plugin registration API. It would result in a "has_cap is deprecated…" warning. Sorry 'bout that folks.

= 1.07 =
* Fixed a bug where a warning could get emitted causing errors to be printed to the screen

= 1.06 =
* Added the ability to allow certain IP addresses to see the site
* Added the ability to have a 301 redirect instead of having the "Coming Soon" page
* Added the ability to restrict what level of user can log into the site (Thanks Gerry for the feature request!)

= 1.05 =
* Fixed an issue where single quotes were being escaped on custom HTML pages. They will now be unquoted when printed to the screen

= 1.04 =
* Made UC a bit more of a 'good citizen' in terms of storing its options. No options will be left behind when deleting the plugin now, and when deactivating, all options are compressed to one archive record. If reactivating the plugin, the options will be returned to how they were before deactivation.
* Fixed a bug where the custom text fields might say "empty" by default
* Ensured compatibility with 3.0-alpha
* Added a warning message if javascript is disabled in the management screen.

= 1.03 =
* Added the ability to switch on and off while keeping the plugin active
* Added the ability to send different HTTP headers (503 or 200)
* Added the ability to simply customize the default text, or display the default page
* Tweaked the page slightly to be a little prettier

= 1.02 =
* Fixed a bug where clearing the text wouldn't cause it to revert to the default.

= 1.01 =
* Fixed a bug where deactivation would trigger an error

= 1.0 =
* First version

== Upgrade Notice ==

= 1.05 =
* Fixed an issue where single quotes were being escaped on custom HTML pages. They will now be unquoted when printed to the screen

= 1.04 =
* Fixed a bug where options may not be deleted after deleting plugin. 
* Improved options storage.
* Fixed a bug where the custom text fields might say "empty" by default.
* Ensured compatibility with 3.0-alpha.
* Added a warning message if javascript is disabled in the management screen.

= 1.03 =
* Added the ability to switch on and off while keeping the plugin active
* Added the ability to send different HTTP headers (503 or 200)
* Added the ability to simply customize the default text, or display the default page
* Tweaked the page slightly to be a little prettier

= 1.02 =
* Fixed a bug where clearing the text wouldn't cause it to revert to the default.

= 1.01 =
* Fixed a bug where deactivation would trigger an error

= 1.0 =
* First version

== Screenshots == 
1. The default page that is displayed (this can be overridden)
2. The editing screen with the default page selected
3. The editing screen with the custom text option selected
4. The editing screen with the custom HTML option selected
