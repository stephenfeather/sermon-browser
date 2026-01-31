# Sermon Browser

> [!CAUTION]
> ## ⚠️ BREAKING CHANGES IN v1.0.0 ⚠️
>
> **The template system is being completely replaced in v1.0.0.**
>
> The legacy template system used `eval()` to process templates, which was **dangerous and vulnerable to code injection attacks**. An attacker with admin access could execute arbitrary PHP code on your server.
>
> **What's changing:**
> - The old `eval()`-based template rendering is being removed
> - A new secure template system will replace it
> - **Your existing custom templates will need to be migrated**
>
> **Action required:** If you have customized your Sermon Browser templates, please back them up before upgrading to v1.0.0. Migration documentation will be provided with the release.

Upload sermons to your website, where they can be searched, listened to, and downloaded. Easy to use with comprehensive help and tutorials.

## Modernization

This plugin is being updated for modern WordPress compatibility.

### Targets
- WordPress 6.9+
- PHP 8.0+

### Documentation
- [Compatibility Analysis](thoughts/shared/handoffs/sermon-browser-session/compatibility-research.md)
- [Modernization Plan](thoughts/shared/plans/sermon-browser-modernization.md)
- [Current Handoff](thoughts/shared/handoffs/sermon-browser-session/current.md)

---

## Original README (for reference)

**Contributors:** mark8barnes
**Donate link:** http://www.sermonbrowser.com/donate/
**Tags:** sermons, podcast, mp3, church, bible, audio, widget, embed, video, esv, wpmu, preach, iTunes, preacher, listen
**Requires at least:** 6.0
**Tested up to:** 6.4
**Requires PHP:** 8.0
**Stable tag:** 0.5.0
**License:** GPLv3 or later
**License URI:** http://www.gnu.org/licenses/gpl.html

### Description

The Sermon Browser WordPress Plugin allows churches to simply upload sermons to their website, where they can be searched, listened to, and downloaded. It is easy to use with comprehensive help and tutorials, and is used on hundreds of church websites. You can view working demos at [Bethel Evangelical Church](http://www.bethel-clydach.co.uk/sermons/), or the [Evangelical Movement of Wales](http://www.emw.org.uk/sermons/). Features include:

1. Store thousands of sermons, and **search** them by topic, preacher, bible passage or date.
2. Full **podcasting** capabilities, including custom podcasts for individual users.
3. Sermons uploaded in mp3 format can be **played directly** on your website using the WordPress 3.6 built-in player or your choice of WordPress MP3 plugins.
4. Three optional **sidebar widgets** can display sermons on all of your posts or pages.
5. **Embed videos** and other flash files from sites such as [YouTube](http://www.youtube.com/) or [Vimeo](http://www.vimeo.com/), using either HTML code provided by those sites, or shortcode providing by a WordPress plugin.
6. **Other file types** can also be uploaded, including PDF, Powerpoint, Word, text and RTF. Multiple files can be attached to single sermons.
7. The **full Bible text** of the passage being preached on can be included on each sermon page (eight English-language versions including ESV and NET, plus Spanish, Russian and Romanian).
8. Files can be uploaded to your own site **through the browser or via FTP**. Alternatively you can use free audio hosting sites.
9. Details about each sermon can be **added automatically from the MP3's ID3 tags**.
10. Powerful **templating function** allows complete customisation to complement the look of your site.
11. Simple statistics show how often each sermon has been listened to.
12. Support for multisite.
13. Extensive **help** and [tutorial screencasts](http://www.sermonbrowser.com/tutorials/).
14. Active [community support forum](http://www.sermonbrowser.com/forum/).
15. Translated into Brazilian Portuguese, German, Hindi, Italian, Romanian, Russian, Spanish, Ukrainian and Welsh.

#### Translations provided by

* Brazilian Portuguese [DIJO](http://www.djio.com.br/sermonbrowser-em-portugues-brasileiro-pt_br/)
* German - Monika Gause
* Hindi - [Chanel](http://outshinesolutions.com/)
* Italian - Manoah Cammarano
* Romanian - [Lucian Mihailescu](http://lucianwebservice.com/)
* Russian - [FatCow](http://www.fatcow.com/), [Vadym Gulyi](http://www.vady.kiev.ua/) and [Alisa Bagrii](http://www.everycloudtech.com/)
* Spanish - Juan, and Marvin Ortega
* Ukrainian - Alisa Bagrii from [Everycloudtech](http://www.everycloudtech.com/)
* Welsh - Emyr James

### Installation

#### Install the plugin in one of two ways:

* In your WordPress admin panel, go to Plugins, Add New. Search for "sermon browser". Find "Sermon Browser" by Mark Barnes, and click "Install Now".

or

* [Download the plugin](https://wordpress.org/plugins/sermon-browser/), unzip it, and upload it to your website, placing the sermon-browser folder in your wp-content/plugins folder.

#### After you have installed the plugin:

1. Activate the plugin from the plugins tab of your WordPress admin.
2. You may have to change the permissions the upload folder (by default `wp-content/uploads/sermons`). See the FAQ for more details.
3. Create a WordPress page with the text `[sermons]`. The plugin will display your sermons on this page.
4. You can also display sermons (filtered according to your criteria) on additional pages or posts by using **shortcodes**. See the Customisation page for more details.

#### Installation in WordPress MU

1. Download the plugin, and unzip it.
2. Place the contents of the sermon-browser folder in your wp-content/mu-plugins folder and upload it to your website.
3. The plugin will be automatically activated and available for each user.

### Frequently Asked Questions

#### I've activated the plugin, and entered in a few sermons, but they are not showing up to my website users. Where are they?

SermonBrowser only displays your sermons where you choose. You need to create the page/post where you want the sermons to appear (or edit an existing one), and add `[sermons]` to the page/post. You can also add some explanatory text if you wish. If you do so, the text will appear on all your sermons pages. If you want your text to only appear on the list of sermons, not on individual sermon pages, you need to edit the SermonBrowser templates (see customisation).

#### What does the error message "Error: The upload folder is not writeable. You need to CHMOD the folder to 666 or 777." mean?

SermonBrowser tries to set the correct permissions on your folders for you, but sometimes restrictions mean that you have to do it yourself. You need to make sure that SermonBrowser is able to write to your sermons upload folder (usually `/wp-content/uploads/sermons/`). [This tutorial](http://samdevol.com/wordpress-troubleshooting-permissions-chmod-and-paths-oh-my/) explains how to use the free FileZilla FTP software to do this.

#### SermonBrowser spends a long time attempting to upload files, but the file is never uploaded. What's happening?

The most likely cause is that you're reaching either the maximum filesize that can be uploaded, or the maximum time a PHP script can run for. [Editing your php.ini](http://www.techrepublic.com/article/a-tour-of-the-phpini-configuration-file-part-2/5272345) may help overcome these problems - but if you're on shared hosting, it's possible your host has set maximum limits you cannot change. If that's the case, you should upload your files via FTP. This is generally a better option than using your browser, particularly if you have several files to upload. If you do edit your php.ini file, these settings should be adequate:

```ini
file_uploads = On
upload_max_filesize = 15M
post_max_size = 15M
max_execution_time = 600
max_input_time = 600
memory_limit = 48M
```

#### Why are my MP3 files appearing as an icon, rather than as a player, as I've seen on other SermonBrowser sites?

If you are using a version of WordPress older than 3.6, you need to install and activate your favourite WordPress MP3 plugin. WordPress 3.6 has the MediaElement.js player built-in; if you are running an older version of WordPress, you can install the [Mediaelement.js plugin](https://wordpress.org/plugins/media-element-html5-video-and-audio-player/). SermonBrowser also supports any WordPress MP3 player that allows you add the player by entering shortcodes in a post or page. To use a different media player plugin, change the MP3 shortcode setting on the Sermons, Options admin page.

#### How do I change the Bible version from the ESV?

Several Bible versions are supported by Sermon Browser. To switch to a different version, go to Options, and edit the single template. Replace `[esvtext]` with the appropriate template tag for the alternative version. (Template tags are listed on the Customisation page of this site). For example, to switch to the KJV, use the tag `[kjvtext]`. Thanks go to Crossway for providing access to the ESV, bible.org for the NET Bible. Other versions are supplied by SermonBrowser itself.

There are lots of other versions available in non-English languages. [This forum post](http://www.sermonbrowser.com/forum/sermon-browser-support/german-bible-support/#p19464) describes what is available and how to add a new version to your Sermon Browser installation.

#### How do I get recent sermons to display in my sidebar or elsewhere in my theme?

SermonBrowser comes with several widgets you can add to your sidebars - just go to Appearance and choose Widgets.

If you want to add sermons elsewhere on your site, and you are comfortable in editing template files, add the following code:

```php
<?php if (function_exists('sb_display_sermons')) sb_display_sermons(array('display_preacher' => 1, 'display_passage' => 1, 'display_date' => 1, 'display_player' => 0, 'preacher' => 0, 'service' => 0, 'series' => 0, 'limit' => 5, 'url_only' => 0)) ?>
```

Each of the values in that line can be changed or omitted (if they are omitted, the default values are used). For example, you could just use:

```php
<?php if (function_exists('sb_display_sermons')) sb_display_sermons(array('display_player' => 1, 'preacher' => 12) ?>
```

The various array keys are used to specify the following:

* `display_preacher`, `display_passage`, `display_date` and `display_player` affect what is displayed (0 is off, 1 is on).
* `preacher`, `service` and `series` allow you to limit the output to a particular preacher, service or series. Simply change the number of the ID of the preacher/services/series you want to display. You can get the ID from the Preachers page, or the Series & Services page. 0 shows all preachers/services/series.
* `limit` is the maximum number of sermons you want displayed.
* `url_only` means that only the URL of a sermon is returned. It's useful if you want to create your own link (e.g. click here for Bob's latest sermon). url_only means the display_ values are ignored, and limit is set to 1.

#### Can I turn off the "Powered by Sermonbrowser" link?

The link is there so that people from other churches who listen to your sermons can find out about SermonBrowser themselves. But if you'd like to remove the link, just remove `[creditlink]` from the templates in SermonBrowser Options.

#### What is the difference between the public and private podcast feeds?

In SermonBrowser options, you are able to change the address of the public podcast feed. This is the feed that is shown on your sermons page, and is usually the same as your private feed (i.e. you won't need to change it). However, if you use a service such as FeedBurner, you can use your private feed to send data to feedburner, and change your public feed to your Feedburner address. If you do not use a service like Feedburner, just make sure your public and private feeds are the same.

#### Can I change the default sort order of the sermons?

Yes. Use the **shortcode** `[sermons dir=asc]` instead of just `[sermons]`.

#### Can I change the way sermons are displayed?

Yes, definitely, although you need to know a little HTML and/or CSS. SermonBrowser has a powerful templating function, so you can exclude certain parts of the output (e.g. if you don't want the links to other sermons preached on the same day to be displayed). The **Customisation** section has much more information.

### Customisation

Sermon Browser works out of the box, but if you wish, you can customise it to fit in with your own theme, and to display or hide whatever information you choose. If you want to create an extra page on your site that just shows a few sermons (for example, just the sermons preached at a recent conference), use **shortcodes**. If you want to customise how Sermon Browser appears throughout your site, use **template tags** (scroll down for more info), or the built-in CSS editor.

#### Shortcodes

Shortcodes allow you to put individual sermons or lists of sermons on any page or post of your website. A simple shortcode looks like this: `[sermons id=52]`, though you can combine parameters like this: `[sermons filter=none preacher=3 series=7]`. The list below gives examples of shortcode uses. A pipe character `|` means 'or'. So `[sermons id=52|latest]` means you would either write `[sermons id=52]`, or `[sermons id=latest]`.

##### `[sermons id=52|latest]`
Displays a single sermon page corresponding to the ID of the sermon (you can see a list of sermon IDs by looking on the Sermons page in admin). You can also use the special value of `latest` which displays the most recent sermon.

##### `[sermons filter=dropdown|oneclick|none]`
Specifies which filter to display with a sermon list.

##### `[sermons filterhide=show|hide]`
Specifies whether the filter should be shown or hidden by default.

##### `[sermons preacher=6]`
Displays a list of sermons preached by one preacher (you can see a list of preacher IDs by looking on the Preachers page in admin).

##### `[sermons series=11]`
Displays a list of sermons in particular series (you can see a list of series IDs by looking on the Series & Services page in admin).

##### `[sermons service=2]`
Displays a list of sermons preached at a particular service (you can see a list of service IDs by looking on the Series & Services page in admin).

##### `[sermons book="1 John"]`
Displays a list of sermons on a particular Bible book. The book name should be written out in full, and if it includes spaces, should be surrounded by quotes.

##### `[sermons tag=hope]`
Displays a list of sermons matching a particular tag.

##### `[sermons limit=5]`
Sets the maximum number of sermons to be displayed.

##### `[sermons dir=asc|desc]`
Sets the sort order to ascending or descending.

#### Template Tags

If you want to change the output of Sermon Browser, you'll need to edit the templates. You'll need to understand the basics of HTML and CSS, and to know the special SermonBrowser template tags. There are two templates, one (called the results page) is used to produce the search results on the main sermons page. The other template (called the sermon page) is used to produce the page for single sermon. Most tags can be used in both templates, but some are specific.

##### Results Page Only
* `[filters_form]` - The search form which allows filtering by preacher, series, date, etc.
* `[sermons_count]` - The number of sermons which match the current search criteria.
* `[sermons_loop][/sermons_loop]` - These two tags should be placed around the output for one sermon. (That is all of the tags that return data about sermons should come between these two tags.)
* `[first_passage]` - The main bible passage for this sermon
* `[previous_page]` - Displays the link to the previous page of search results (if needed)
* `[next_page]` - Displays the link to the next page of search results (if needed)
* `[podcast]` - Link to the podcast of all sermons
* `[podcast_for_search]` - Link to the podcast of sermons that match the current search
* `[itunes_podcast]` - iTunes (itpc://) link to the podcast of all sermons
* `[itunes_podcast_for_search]` - iTunes (itpc://) link to the podcast of sermons that match the current search
* `[podcasticon]` - Displays the icon used for the main podcast
* `[podcasticon_for_search]` - Displays the icon used for the custom podcast
* `[tag_cloud]` - Displays a tag cloud

##### Both Results Page and Sermon Page
* `[sermon_title]` - The title of the sermon
* `[preacher_link]` - The name of the preacher (hyperlinked to his search results)
* `[series_link]` - The name of the series (hyperlinked to search results)
* `[service_link]` - The name of the service (hyperlinked to search results)
* `[date]` - The date of the sermon
* `[files_loop][/files_loop]` - These two tags should be placed around the [file] tag if you want to display all the files linked to this sermon. They are not needed if you only want to display the first file.
* `[file]` - Displays the files and external URLs
* `[file_with_download]` - The same as [file], but includes a download link after the media player for MP3 files
* `[embed_loop][/embed_loop]` - These two tags should be placed around the tag if you want to display all the embedded objects linked to this sermon. They are not needed if you only want to display the first embedded object.
* `[embed]` - Displays an embedded object (e.g. video)
* `[creditlink]` - displays a "Powered by Sermon Browser" link.
* `[editlink]` - Displays a link to edit the current sermon if you are logged in as an admin

##### Sermon Page Only
* `[preacher_description]` - The description of the preacher.
* `[preacher_image]` - The photo of the preacher.
* `[passages_loop][/passages_loop]` - These two tags should be placed around the [passage] tag if you want to display all the passages linked to this sermon.
* `[passage]` - Displays the reference of the bible passage with the book name hyperlinked to search results.
* `[next_sermon]` - Displays a link to the next sermon preached (excluding ones preached on the same day)
* `[prev_sermon]` - Displays a link to the previous sermon preached
* `[sameday_sermon]` - Displays a link to other sermons preached on that day
* `[tags]` - Displays the tags for that sermon
* `[esvtext]` - Displays the full text of the ESV Bible for all passages linked to that sermon.
* `[asvtext]` - Displays the full text of the ASV Bible for all passages linked to that sermon.
* `[kjvtext]` - Displays the full text of the KJV Bible for all passages linked to that sermon.
* `[ylttext]` - Displays the full text of the YLT Bible for all passages linked to that sermon.
* `[webtext]` - Displays the full text of the WEB Bible for all passages linked to that sermon.
* `[akjvtext]` - Displays the full text of the AKJV Bible for all passages linked to that sermon.
* `[hnvtext]` - Displays the full text of the HNV Bible for all passages linked to that sermon.
* `[lbrvtext]` - Displays the full text of the Reina Valera Bible (Spanish) for all passages linked to that sermon.
* `[cornilescutext]` - Displays the full text of the Cornilescu Bible (Romanian) for all passages linked to that sermon.
* `[synodaltext]` - Displays the full text of the Synodal 1876 Translation (Russian) for all passages linked to that sermon.
* `[biblepassage]` - Displays the reference of the bible passages for that sermon. Useful for utilising other bible plugins (see FAQ).

### Changelog

#### 0.5.0 (January 2026)
**Major compatibility update for modern WordPress and PHP.**

* **Requires:** WordPress 6.0+, PHP 8.0+
* **Tested up to:** WordPress 6.4 with PHP 8.2

**PHP 8.x Compatibility:**
* Fixed fatal error: Replaced `preg_replace` /e modifier with `preg_replace_callback`
* Fixed `implode()` argument order deprecation (3 locations)
* Fixed `(boolean)` cast deprecation - now uses `(bool)`
* Fixed PHP 8 null property assignment in widget functions
* Fixed PHP 8 string/int comparison change in query builder (one-click filter)
* Fixed empty array access in date filter function

**WordPress 6.x Compatibility:**
* Replaced deprecated `is_site_admin()` with `is_super_admin()` (3 locations)
* Replaced deprecated `WPLANG` constant with `get_locale()`
* Replaced deprecated `strftime()` with `wp_date()` (4 locations)
* Replaced deprecated `rightnow_end` hook with `dashboard_glance_items`
* Converted legacy `contextual_help` filter to Help Tabs API

**Widget Modernization:**
* Converted all 3 widgets to extend `WP_Widget` class
* Added widget settings migration routine for upgrades
* Widgets now properly appear in Appearance > Widgets

**jQuery Compatibility:**
* Replaced `.attr()` with `.prop()` for boolean properties (14 occurrences)
* Admin JavaScript now compatible with jQuery 3.x

**Code Quality:**
* Replaced `extract()` calls with explicit variable assignments (6 locations)
* Added try/catch wrapper around eval() template rendering
* Updated plugin headers with minimum requirements

#### 0.45.22 (29 August 2018)
* **Bug fix:** Sermons couldn't be deleted. Now they can.
* **Bug fix:** Sermons weren't downloading on Apple's Podcast app. Now they are.

#### 0.45.21 (11 August 2018)
* **Bug fix:** Several security improvements.
* **Bug fix:** Support sites that have renamed the wp-content folder
* **Enhancement:** Updated to v3 of the ESV Bible API (v2 was deprecated).
* **Enhancement:** Removed support for flash audio players.
* **Enhancement:** External https media files can now be downloaded

#### 0.45.20 (27 June 2017)
* **Bug fix:** Added nonces to enhance security protection.

#### 0.45.19 (31 May 2016)
* **Bug fix:** Fixed bug introduced in 0.45.16 that prevented iframe embeds from being saved.

#### 0.45.18 (30 May 2016)
* **Bug fix:** Fixed bug that prevented shortcodes from working.
* **Enhancement:** No need to surround embedded videos with the [embed] shortcode.
* **Enhancement:** Added Italian translation (thanks to Manoah Cammarano)

#### 0.45.17 (23 May 2016)
* **Enhancement:** Minor, under-the-hood changes to translations.

#### 0.45.16 (21 April 2016)
* **Bug fix:** Fixed potential XSS vulnerabilities.

#### 0.45.15 (10 November 2015)
* **Bug fix:** Sermon duration is now correctly calculated.
* **Bug fix:** Fixed bug that prevented sermons being added from the 'Files' page.
* **Bug fix:** Remove empty rows from the list of files.

#### 0.45.14 (6 November 2015)
* **Bug fix:** Fixed bug introduced in 0.45.13 which prevented series being edited/saved.

#### 0.45.13 (6 November 2015)
* **Bug fix:** Compatibility with PHP 5.2 to 5.6 and WordPress 3.6 to 4.3.

*See [readme.txt](readme.txt) for complete changelog history.*
