=== Wysija Newsletters ===
Contributors: wysija, benheu
Tags: newsletter, newsletters, newsletter signup, newsletter widget, subscribers, post notification, email subscription, email alerts, automatic newsletter, auto newsletter, autoresponder, follow up, email marketing, email, emailing, subscription
Requires at least: 3.3
Tested up to: 3.5
Stable tag: 2.4.4
Send newsletters, post notifications or autoresponders from WordPress easily, and beautifully.

== Description ==

Create newsletters, post notifications and autoresponders. Drop your posts, images, social icons in your newsletter. Change fonts and colors on the fly. Manage all your subscribers. A new and simple newsletter solution for WordPress. Finally!

We built it with the idea that newsletters in WordPress should be easy. Not hard. Forget MailChimp, Aweber, etc. We're the good guys inside your WordPress.

= Check out this 2 minute video. =

http://vimeo.com/35054446

= Post notifications video. =

http://vimeo.com/46247528

= Features =

* Drag & drop visual editor, an HTML-free experience
* Post notifications, like Feedburner, Subscribe2 or MailChimp's RSS-to-Email
* [Selection of over 30 themes](http://www.wysija.com/newsletter-templates-wordpress/). Photoshop files included
* Get stats for each newsletter: opens, clicks, unreads, unsubscribes
* Add a subscription form in your sidebar or pages
* Your newsletters look the same in Gmail, iPhone, Android, Outlook, Yahoo, Hotmail, etc.
* Your WordPress users have their own list
* Import subscribers from MailChimp, Aweber, etc.
* One click import from Tribulant, Satollo, Subscribe2, etc.
* Single or double opt-in, your choice
* Send with your web host, Gmail or SMTP
* Segment your lists based on opened, clicked & bounced
* Autoresponders, i.e. "Send email 3 days after someone subscribes"
* Unlimited number of lists
* Sending in free version is limited to 2000 subscribers

= Premium version =

[Wysija Premium](http://www.wysija.com/wordpress-newsletter-plugin-premium/) offers these nifty extra features:

* Send to more than 2000 subscribers
* Stats for individual subscribers (opened, clicked)
* Total clicks for each link in your newsletter
* Access to Premium themes
* Automated bounce handling. Keeps your list clean, avoid being labeled a spammer
* Unlimited spam score tests with mail-tester.com
* Improve deliverability with DKIM signature
* We trigger your email queue, like a real cron job
* Don't reinstall. Simply activate!
* Priority support

[Visit our Premium page](http://www.wysija.com/wordpress-newsletter-plugin-premium/).

= Future releases =

* Subscriber profiles, ie. gender, city, or whatever you want
* Possibility to insert your own HTML in newsletter
* Display a list of past newsletters sent in a page of your site (shortcode)

= Support =

We got a dedicated website just to help you out. And we're quite quick to reply.

[support.wysija.com](http://support.wysija.com/)

= Translations in your language =

[Get a Premium license in exchange for your translation](http://support.wysija.com/knowledgebase/translations-in-your-language/)

* Arabic
* Catalan
* Chinese
* Croatian
* Czech
* Danish
* Dutch
* French
* German
* Greek
* Hungarian
* Indonesian
* Italian
* Japanese
* Norwegian
* Polish
* Portuguese PT
* Portuguese BR
* Romanian
* Russian
* Serbian
* Slovak
* Spanish
* Swedish
* Turkish

== Installation ==

There are 3 ways to install this plugin:

= 1. The super easy way =
1. In your Admin, go to menu Plugins > Add
1. Search for `Wysija`
1. Click to install
1. Activate the plugin
1. A new menu `Wysija` will appear in your Admin

= 2. The easy way =
1. Download the plugin (.zip file) on the right column of this page
1. In your Admin, go to menu Plugins > Add
1. Select the tab "Upload"
1. Upload the .zip file you just downloaded
1. Activate the plugin
1. A new menu `Wysija` will appear in your Admin

= 3. The old and reliable way (FTP) =
1. Upload `wysija-newsletters` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. A new menu `Wysija` will appear in your Admin

== Frequently Asked Questions ==

= Got questions? =

Our [support site](http://support.wysija.com/) has plenty of articles and a ticketing system.

= Submit your feature request =

We got a User Voice page where you can [add or vote for new features](http://wysija.uservoice.com/forums/150107-feature-request).

== Screenshots ==

1. Sample newsletters.
2. The drag & drop editor.
3. Subscriber management.
4. Newsletter statistics.
5. Subscriber statistics (Premium version).
6. Sending method configuration in Settings.
7. Importing subscribers with a CSV.

== Changelog ==

= 2.4.4 - 2013-04-22 =
* added translations of the "loading..." message in forms
* added download link to theme's .zip file in theme detail pages
* added possibility to hide our update page's kitten. It hurt some feelings
* added protection on looping install process resulting in duplication of default list or newsletter
* fixed sending autoresponders twice after importing same csv file

= 2.4.3 - 2013-04-03 =
* improved subscription form rendering and support of unicode/special characters
* improved security on queueing emails
* fixed missing confirmation message when subscribing to forms (introduced in 2.4.2)
* fixed scheduling issue when sending every month on a given day
* fixed form editor issues related to data encoding/decoding
* fixed post notification will not activate on step3 of the newsletter edition
* fixed scheduled emails generating a queueing error on step3 of the newsletter edition
* fixed import into a list associated with a retro-active autoresponder was not put into the queue
* fixed retro-active autoresponder delay calculation

= 2.4.2 - 2013-03-27 =

* fixed issue in form editor. Using quotes in confirmation message prevented Form from being saved.
* fixed autoresponders being automatically queued if modified and saved on step3 of the newsletter edition this resulting in a sql error

= 2.4.1 - 2013-03-26 =

* fixed post notification being queued immediately after changes being saved on step3 related to retroactive autoresponders.

= 2.4 - 2013-03-25 =

* added ability to edit HTML in text blocks of visual editor (beta)
* added a form manager in settings, with a drag and drop interface
* added ability for users to share their usage data with Wysija's team
* added a dozen newsletter themes
* added image resizing of images uploaded in previous versions
* added autosave on browser back button. No more lost changes
* improved sending status with progress bar
* improved translations
* improved autoresponders: now retroactive and will be sent to newly imported subscribers too
* fixed when sending directly a newsletter which was set as scheduled in step 3
* fixed dozens of small bugs
* impressed by your determination in reading the full change log

= 2.3.5 - 2013-03-15 =

* fixed unsubscribe and subscriptions links lead now to view in browser version if the subscriber doesn't exists in the DB anymore (instead of a white screen)
* fixed error when trying to delete a duplicated list
* fixed view in browser link
* fixed how spammy is your newsletter and Mandrill
* fixed variable type issue leading in some case scenario to a Fatal Error in the frontend subscription widget
* fixed removed autofill value in HTML version of subscription form
* improved memory usage on subscribers import and export processes

= 2.3.4 - 2013-03-07 =

* added default value from WordPress' logged in user in the subscription form
* added dropdown selection of statuses (publish, private, scheduled, draft) for WordPress' Posts to be dropped into the visual editor
* added option to Wysija's CRON (task scheduler) to deactivate the schedule tasks checks on any page view
* fixed unsubscribe date in the frontend subscriptions management is now translated with date_i18n (thanks Anna :))
* fixed unsubscribe link in preview emails
* fixed subscribers count when double optin is deactivated
* fixed unsubscribe link with Google Analytics

= 2.3.3 - 2013-03-04 =

* added drag and drop private or scheduled posts in the visual editor
* fixed more than one post notification going out monthly, weekly or daily
* fixed warning in MS settings
* fixed translation issues for comments checkbox for instance
* fixed little notice when deleting list
* fixed buddypress multiple checkbox on registration form
* fixed on duplicate of a post notification reset the articles already sent in the original so that it starts from scratch
* fixed import ignoring rows with invalid data to avoid import failure
* fixed missing title and description of widget
* fixed multisite only you'll see once the update screen as a network admin
* improved logging options

= 2.3.2 - 2013-02-20 =

* fixed scheduling issue for automatic newsletters
* fixed message output in case of cron problems
* fixed wordpress images gallery pagination
* fixed occasional internal server error on some users with PHP running as FastCGI

= 2.3.1 - 2013-02-07 =

* added correction of our commit through svn some files were missing
* added "shortcodes" for newsletter. Add more than first and last name, like dates, links Supported in subject line too.
* added custom roles to autoresponders so you can send to more than just the default roles (admin, editor, author, etc.)
* added single sending method for all sites in Multisite. See new "MS" tab in settings for more
* added DKIM optional upgrade to 1024 bits to comply with Gmail (Premium feature)
* added support for Premium behind firewall with no possible requests to wysija.com
* fixed images uploaded in Wysija are now resized to 600px. Next release will include images from media library.
* fixed lightbox (popups) width for right to left languages
* fixed subscription form on WordPress user registration
* fixed load translation error on Windows server
* fixed wrong count for the issue number tag [number] in daily, weekly and monthly post notification
* fixed immediate custom post notification wrongly triggered
* fixed browser view error for rare scenarios
* fixed SendGrid web API not able to send newsletters to subscribers with first name and/or last name
* fixed WordPress' images browser
* fixed breakfast, with a buttered toast and nice latte

= 2.3 - 2013-02-07 =

* svn error please update your version to the latest one

= 2.2.3 - 2013-01-19 =

* fixed weekly post notifications not having all of the articles of the week, but just of the day

= 2.2.2 - 2013-01-18 =

* fixed immediate single post notification not being triggerred

= 2.2.1 - 2013-01-18 =

* fixed translation issue in confirmation page, message was forced in English
* fixed display bug in settings, SMTP fields (port, authentication, secure connection) showing where not needed
* fixed manual bounce processing button not working
* fixed issue number [number] tag not having the right value
* fixed small frontend conflict with jquery 1.9.0 and above
* fixed missing filter in newsletters statistic for the Not Sent status
* fixed post notification could send some past articles in one specific case scenario
* fixed wrong count of subscribers in backend interfaces
* fixed still sending to subscribers manually removed from a list in the backend
* fixed number of WordPress users don't match with the number in our WordPress Users list
* added support for German umlaut in email addresses

= 2.2 - 2013-01-11 =

* added script detector in debug mode to help resolve plugin & theme conflicts
* added checkbox option in WordPress registration form. See Advanced Settings.
* added on auto newsletter duplication reset the [number] tag
* added a safeguard for manually deleted activation email in database
* added support for SendGrid's web API to avoid blocked SMTP ports
* added a sending status load bar for currently sending newsletter
* improved "subscribe in comments" option for better Akismet integration
* improved iframe.css inclusion for MS. All child sites take the main site's styles by default
* renamed list "WordPress Synched" to "WordPress users" for clarity
* fixed "HTML version" which was not working for visitors
* fixed subscription form "HTML version" missing hidden fields in post/page widget
* fixed newsletters themes installation with unsafe paths
* fixed missing page/post title when subscribing without ajax
* fixed encoding issue in HTML and PHP version of the subscription form in the widget
* fixed save issue of subscriber's status in Subscriber's detail page in admin
* fixed over 25 mini bugs
* fixed lunch and went for a well deserved beer

= 2.1.9 - 2012-12-11 =
* added checkbox to comments in post for visitors to optin. Activate in Settings > Advanced
* improved default newsletter into simple 5 min. guide
* improved over a dozen confusing labels and strings
* improved compatibility with domain mapping
* added hook wysija_preview to browser version of newsletter (thx to Matt)
* fixed autoload new posts on scroll in the newsletter WordPress post widget
* fixed missing total click stats in newsletter stats
* fixed saving changes when going back to Step 2 from Step 3
* added sending autoresponders to subscribers added via the admin
* removed 3 messages after installation. Nobody reads them
* removed bulk add to synch list
* removed bulk unsubscribe to all. Too dangerous.
* went for a walk in the park with friends to celebrate this new version

= 2.1.8 - 2012-11-27 =
* added get HTML version of form. See in Widgets.
* improved Wysija homemade cron, available in Settings > Advanced
* removed validation for first name & last name on subscriber profile
* fixed incompatibility with "Root Relative URLs" plugin
* fixed conflict with plugin "Magic Members"
* fixed crashed on some servers on install
* fixed in newsletters listing, wrong list appearing in automatic newsletter
* fixed disappeared bounce email field in Settings > Advanced for free users
* fixed Internet Explorer issue on WordPress Articles selection widget
* fixed issue on IE8 where a draggable item was not disappearing after being dropped
* fixed WordPress Synched list wrong count when sending
* fixed image not being fetched from post content when inserting a WordPress post
* fixed not sending auto newsletter with event "after a new user is added to your site" when double optin was off
* fixed various plugins conflicting with our subscription form inserted into the content of a post or page

= 2.1.7 - 2012-11-09 =
* added Wysija custom cron option in Advanced Settings as an alternative to wp-cron
* fixed translation missing for "unsubscribe", "view in your browser" and "manage your subscription" links
* fixed escaping quotes on subject in step 3 send preview
* fixed wrong total of subscribers when sending
* fixed bounced tab appearing empty for free users
* fixed wrong selection in WordPress posts widget after a search(in visual editor)
* fixed security issue with swf uploading module

= 2.1.6 - 2012-11-04 =
* added basic Custom Post Type support in WordPress post widget
* added resend an Activation Email for another list even when already subscribed
* added posts autoload on scroll when adding single post in newsletter visual editor
* fixed PHP Notice: step2 of newsletter creation
* fixed PHP Notice: on debug class
* fixed our debug hijacking WP_DEBUG in the backend (thanks Ryann)
* fixed deprecated in bounce handling
* fixed scrollbar issue in WordPress Post popup on Chrome & Safari
* fixed conflict with Simple Links plugin
* fixed toolbar tabs disappearing in some languages (will be improved)
* fixed bounce error not properly displayed prevented saving settings

= 2.1.5 - 2012-10-16 =
* fixed Notice: Use of undefined constant WYSIJA_DBG - assumed 'WYSIJA_DBG' in [...]/wp-content/plugins/wysija-newsletters/core/model.php on line 842
* fixed bulk add subscriber to list when unsubscribed
* fixed private list removed on edit your subscriber profile
* fixed shortcodes not being properly stripped from post excerpt
* fixed line breaks being stripped from posts
* fixed text alignment issues in Outlook
* fixed font styling issues in email
* fixed auto newsletter for new subscriber when single optin
* fixed new subscriber notification when single optin
* fixed send preview email on automatic post notification newsletter
* fixed not sending followup when updating subscriptions

= 2.1.4 - 2012-09-26 =
* fixed missing "from name" when using Elastic Email
* fixed rare issue where Social bookmarks & Automatic latest posts were not saved
* fixed double scrollbars appearing on article selection popup
* fixed dkim wrong key
* fixed filled up sent on parameter without having sent the newsletter

= 2.1.3 - 2012-09-18 =

* added restAPI for elasticemail when detected in the smtp configuration
* improved install making sure that no crazy plugins will harm our initial setup (symptoms: Too many redirect crash or posting to social networks)
* fixed SQL comments inserted as tables in some weird server...
* fixed error 500 on update procedure of 2.1 when some roles were not existing. (add_cap on none object fatal error)
* improved install process not creating new sql connection, only using wpdb's one.
* fixed synched plugins (Subscribe2 etc...) when there was just the main list
* removed global css and javascript
* fixed issue where the widget would not save
* improved IE9 compatibility
* fixed excerpt function to keep line breaks
* fixed links with #parameters GA incompatibility -> Thanks Adam

= 2.1.2 - 2012-09-05 =

* major speed improvement and cache plugin compatibility
* added utf-8 encoding in iframe loaded subscription form.
* added security check for translated links (dutch translation issue with view in browser link)
* removed _nonce non sense in the visitors subscription forms.
* fixed loading issue in subscription form
* fixed styling issue in subscription form
* fixed accents issue in subscription form
* fixed DKIM activation settings not being saved
* fixed non translated unsubscribe and view in browser links
* fixed warning showing up on some servers configuration when sending a preview of the newsletter
* fixed popups in IE8 and improved overall display
* fixed openssl_error_string function breaking our settings screen on some configurations.
* fixed error with dkim on server without openssl functions
* fixed bounce error with the rule unsubscribe user

= 2.1.1 - 2012-09-02 =

* fixed update 2.1 error : Duplicate column name "is_public" may have caused some big slow down on some servers and some auto post to facebook (deepest apologies).
* fixed Outlook issue where text blocks would not have the proper width

= 2.1 - 2012-08-31 =

* added ability for subscribers to change their email and lists.
* added "View it in your browser" option.
* added advanced access rights with capabilities for subscribers management, newsletter management, settings and subscription widget.
* added new WordPress 3.3 plupload used when possible to use.
* added mail-tester.com integration for Premium (fight against spam).
* added DKIM signature for Premium to improve deliverability.
* added the possibility to preview your newsletter without images in visual editor.
* added background colors for blocks within the visual editor.
* added alternate background colors for automatic latest post widget.
* added possibility to add total number of subscribers in widget with shortcode.
* added widget option "Display label within for Email field".
* improved email rendering and email clients compatibility including the new Outlook 2013
* improved image upload with ssl.
* improved compatibility with access rights plugins like "Advanced Access Manager" or "User Role Editor".
* improved import system with clearer message.
* improved subscription widget, added security if there is no list selected.
* improved Auto newsletter edition, warning added before pausing it.
* improved popups for the visual editor (themes, images, add link,...)
* updated TinyMCE to latest version, the editor now reflects the newsletter styles
* compatibility with [Magic Action Box](http://wordpress.org/extend/plugins/magic-action-box/).
* fixed links style in headings.
* fixed no default value in optin form when JS disabled.
* fixed issue with automatic latest post widget where one article could appear more than once.

= 2.0.9.5 - 2012-08-15 =

* fixed post notification hook when post's status change from publish to draft and back to publish.
* fixed firewall 2 avoid troubles with image uploader automatically
* fixed problem of confirmation page on some servers when pretty links activated on wysijap post. Default is params link now.

= 2.0.9 - 2012-08-03 =

* improved debug mode with different level for different needs
* added logging function to monitor post notification process for instance
* improved send immediately post notification (in some case the trigger was not working... using different WordPress hook now)
* fixed post notification interface (step1 and step3) not compatible with WordPress lower than 3.3
* fixed issue when duplicating sent post notifications. You should not be able to copy a child email and then change it's type like an automatic newsletter etc...
* fixed zip format error when uploading your own theme (this error was happenning on various browsers)

= 2.0.8 - 2012-07-27 =

* added default style for subscription notification which was lost
* fixed php error on subscription form creation
* fixed php error on helper back

= 2.0.7 - 2012-07-21 =

* fixed strict error appearing on servers below php version 5.4
* fixed on export to a csv translate fields and don't get the columns namekeys
* added non translated 'Loading...' string on subscription's frontend

= 2.0.6 - 2012-07-20 =

* fixed unreliable WP_PLUGIN_URL when dealing with https constants now using plugins_url() instead
* fixed automatic newsletter resending itself on unsubscribe
* fixed when unsubscribing and registering to some lists, you will not be re-registered to your previous lists
* fixed issue with small height images not displaying in email
* fixed issue with post excerpt in automatic posts
* improved php 5.4 strictness compatibility

= 2.0.5 - 2012-07-13 =

* added extended check of caching plugin activation
* added security to disallow directory browsing
* added subscription form working now with Quick-cache and Hyper cache(Already working with WP Super Cache && W3 Total Cache)
* added onload attribute on iframe subscription form which seems more reliable
* added independant cron manager wysija_cron.php
* added cleaning the queue of deleted users or deleted emails through phpmyadmin for instance
* added theme menu erasing Wysija's menu when in the position right below ours

= 2.0.4 - 2012-07-05 =

* added for dummies check that list exists or subscription form widget not editable
* fixed problem with plugin wordpress-https when doing ajax subscription
* fixed issue with scheduled articles not being sent in post notification
* fixed rare issue when inserting a WordPress post would trigger an error
* fixed issue wrong count of ignored emails when importing
* fixed multi forms several send confirmation emails on one subscribing request
* fixed subject title in email template

= 2.0.3 - 2012-06-26 =

* fixed theme activation not working
* fixed google analytics code on iframe subscription forms
* fixed post notification bug with wrong category selected when fetching articles
* fixed issue regarding category selection in auto responder / post notifications
* fixed dollar sign being stripped in post titles
* fixed warning and notices when adding a list
* fixed on some server unsubscribe page or confirmation page redirecting to 404
* improved iframe system works now with short url and multiple forms

= 2.0.2 - 2012-06-21 =

* fixed missing title on widget when cache plugin activated
* fixed update procedure to Wysija version "2.0" failed! on some MySQL servers
* fixed W3C validation for subscription form with empty action: replace with #wysija
* fixed forbidden iframe subfolder corrected to a home url with params
* improved theme installation with PclZip
* fixed missing previously sent auto newsletter on newsletters page
* fixed broken url for images uploaded in WordPress 3.4
* fixed "nl 2 br" on unsubscribed notification messages for admins
* added meta noindex on iframe forms to avoid polluting Google Analytics
* added validation of lists on subscription form
* fixed issue with image alignment in automatic newsletters
* fixed url & alternative text encoding in header/footer
* fixed images thumbs not displaying in Images tab
* fixed popups' CSS due to WordPress 3.4 update
* fixed issues when creating new lists from segment

= 2.0.1 - 2012-06-16 =

* fixed subscribers not added to the lists on old type of widget

= 2.0 - 2012-06-15 =

* Added post notifications
* Added auto responders
* Added scheduling (send in future)
* allow subscribers to select lists
* embed subscription form outside your WordPress site (find code in the widget)
* Subscription forms compatibility with W3 Total Cache and WP Supercache
* Load social bookmarks from theme automatically
* Several bug fixes and micro improvements
* Ability to send snail mail

= 1.1.5 - 2012-05-21 =

* improved report after importing csv
* fixed Warning: sprintf() /helpers/back.php on some environnements
* fixed roles for creating newsletters or managing subscribers "parent roles can edit as well as child roles if a child role is selected"
* fixed cron wysija's frequencies added in a cleaner way to avoid conflict with other plugins
* fixed w3c validation on confirmation and unsubscription page
* improved avoiding duplicates on environment with high sending frequencies
* removed php show errors lost in resolveConflicts

= 1.1.4 - 2012-05-14 =

* added last name to recipient name in header
* fixed automatic redirection for https links in newsletter
* fixed conflict with Advanced Custom Fields (ACF) plugin in the newsletter editor
* fixed conflict with the WpToFacebook plugin
* fixed validation on import of addresses with trim
* fixed dysfunctional unsubscribe link when Google Analytics campaign inserted
* added alphanumeric validation on Google Analytics input
* display clicked links in stats without Google Analytics parameters
* fixed page/post newsletter subscription widget when javascript conflict returns base64 string
* fixed WP users synch when subscriber with same email already exists
* fixed encoded url recorded in click stats
* added sending status In Queue to differentiate with Not Sent
* fixed automatic bounce handling
* added custom roles and permissions

= 1.1.3 - 2012-03-31 =

* fixed unsubscribe link redirection
* fixed rare issue preventing Mac users from uploading images
* added Norwegian translation
* added Slovak translation

= 1.1.2 - 2012-03-26 =

* fixed automatically recreates the subscription page when accidentally deleted
* fixed more accurate message about folder permissions in wp-content/uploads
* fixed possibility to delete synchronisable lists
* fixed pagination on subscribers lists' listing
* fixed google analytics tracking code
* fixed relative path to image in newsletter now forced to absolute path
* fixed widget alignment when labels not within field default value is now within field
* fixed automatic bounce handling error on some server.
* fixed scripts enqueuing in frontend, will print as long as there is a wp_footer function call in your theme
* fixed theme manager returns error on install
* fixed conflict with the SmallBiz theme
* fixed conflict with the Events plugin (wp-events)
* fixed conflict with the Email Users plugin (email-users)
* fixed outlook 2007 rendering issue

= 1.1.1 - 2012-03-13 =

* fixed small IE8 and IE9 compatibility issues
* fixed fatal error for new installation
* fixed wysija admin white screen on wordpres due to get_current_screen function
* fixed unsubscribe link disappearing because of qtranslate fix
* fixed old separators just blocked the email wizard
* fixed unsubscribe link disappearing because of default color
* fixed settings panel redirection
* fixed update error message corrected :"An error occured during the update" sounding like update failed even though it succeeded
* fixed rendering of aligned text
* fixed daily report email information
* fixed export: first line with comma, the rest with semi colon now is all semi colon
* fixed filter by list on subscribers when going on next pages with pagination
* fixed get_avatar during install completely irrelevant
* fixed wordpress post in editor when an article had an image with height 0px
* fixed when domain does not exist, trying to send email, we need to flag it as undelivered after 3 tries and remove it from the queue
* fixed user tags [user:firstname | defaul:subscriber] left over when sent through queue and on some users
* fixed get_version when wp-admin folder doesn't exist...
* fixed Bulk Unsubscribe from all list "why can't I add him"

= 1.1 - 2012/03/03 =

* support for first and last names
* 14 new themes. First Premium themes
* added social bookmarks widget
* added new divider widget
* added first name and last name feature in subscription form, newsletter content and email subject
* header is now image only and not text/image
* small changes in Styles tab of visual editor
* new full width footer image area (600px)
* added transparency feature to header, footer, newsletter
* newsletter width for content narrowed to 564px
* improved line-height for titles in text editor
* fixed Outlook and Hotmail padding issue with images
* improved speed of editor
* possibility to import automatically and keep in sync lists from all major plugins: MailPress, Satollo, WP-Autoresponder, Tribulant, Subscribe2, etc.
* possibility to change "Unsubscribe" link text in footer
* choose which role can edit subscribers
* preview of newsletter in new window and not in popup
* added possibility to choose between excerpt or full article on inserting WP post
* theme management with API. Themes are now externalized from plugin.
* removed numbered lists from text editor because of inconsistent display, notably Outlook

= 1.0.1 - 2012/01/18 =

* added SMTP TLS support, useful for instance with live.com smtp
* added support for special Danish chars in email subscriptions
* fixed menu position conflict with other themes and plugins
* fixed subscription form works with jquery 1.3, compatible for themes that use it
* fixed issue of drag & drop of WP post not working with php magic quotes
* fixed permissions issue. Only admins could use the plugin despite changing the permissions in Settings > Advanced.
* fixed display of successful subscription in widget displays better in most theme
* fixed synching of WordPress user registering through frontend /wp-login.php?action=register
* fixed redirection unsubscribe link from preview emails
* fixed cross site scripting security threat
* fixed pagination on newsletter statistics's page
* fixed javascript conflict with Tribulant's javascript's includes
* improved detection of errors during installation

= 1.0 - 2011/12/23 =
* Premium upgrade available
* fix image selector width in editor
* fix front stats of email when email preview and show errors all
* fix front stats of email when show errors all
* fix import ONLY subscribed from external plugins such as Tribulant or Satollo
* fix retrieve wp.posts when time is different on mysql server and apache server
* fix changing encoding from utf8 to another was not sending
* newsletter background colour now displays in new Gmail
* less confusing queue sending status
* updated language file (pot) with 20 or so modifications

= 0.9.6 - 2011/12/18 =
* fixed subscribe from a wysija confirmation page bug
* fixed campaigns "Column does not exists in model .."
* fixed address and unsubscribe links appearing at bottom of newsletter a second time
* fixed menu submenu no wysija but newsletters no js
* fixed bug statistics opened_at not inserted
* fixed bug limit subscribers updated on subscribers delete
* fixed daily cron scandir empty dir
* fixed subscribe from frontend without javascript error
* fixed subscribe IP server validation when trying in local
* fixed CSS issues with Wordpress 3.3
* improving interface of email sending in the newsletter's listing
* added delete newsletter option
* added language pot file
* added french translation

= 0.9.2 - 2011/12/12 =
* fixed issue with synched users on multisite(each site synch its users only)
* fixed compatibility issue with wordpress 3.3(thickbox z-index)
* fixed issue with redundant messages after plugin import
* fixed version number display

= 0.9.1 - 2011/12/7 =
* fixed major issue with browser check preventing Safari users from using the plugin
* fixed issue with wp_attachment function affecting Wordpress post insertion
* fixed issue when importing subscribers (copy/paste from Gmail)
* fixed issue related to Wordpress MU
* minor bugfixes

= 0.9 - 2011/12/3 =
* Hello World.
