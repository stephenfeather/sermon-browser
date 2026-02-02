<?php

declare(strict_types=1);

namespace SermonBrowser\Install;

/**
 * Default templates for Sermon Browser installation.
 *
 * Provides default template content for search results, single sermon pages,
 * excerpt displays, and CSS styling.
 */
class DefaultTemplates
{
    /**
     * Get the default template for search results (multi-sermon view).
     *
     * @return string The default multi-sermon template HTML.
     */
    public static function multiTemplate(): string
    {
        return <<<'HERE'
<div class="sermon-browser">
    [filters_form]
       <div class="sb-clear">
        <h4>Subscribe to Podcast</h4>
        <table class="podcast">
            <tr>
                <td class="podcastall">
                    <table>
                        <tr>
                            <td class="podcast-icon"><a href="[podcast]">[podcasticon]</a></td>
                            <td><strong>All sermons</strong><br /><a href="[itunes_podcast]">iTunes</a> &bull; <a href="[podcast]">Other</a></td>
                        </tr>
                    </table>
                <td style="width: 2em"> </td>
                <td class="podcastcustom">
                    <table>
                        <tr>
                            <td class="podcast-icon"><a href="[podcast_for_search]">[podcasticon_for_search]</a></td>
                            <td><strong>Filtered sermons</strong><br /><a href="[itunes_podcast_for_search]">iTunes</a> &bull; <a href="[podcast_for_search]">Other</a></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <h2 class="sb-clear">Sermons ([sermons_count])</h2>
       <div class="floatright">[next_page]</div>
       <div class="floatleft">[previous_page]</div>
    <table class="sermons">
    [sermons_loop]
        <tr>
            <td class="sermon-title">[sermon_title]</td>
        </tr>
        <tr>
            <td class="sermon-passage">[first_passage] (Part of the [series_link] series).</td>
        </tr>
        <tr>
            <td class="files">[files_loop][file][/files_loop]</td>
        </tr>
        <tr>
            <td class="embed">[embed_loop][embed][/embed_loop]</td>
        </tr>
        <tr>
            <td class="preacher">Preached by [preacher_link] on [date] ([service_link]). [editlink]</td>
        </tr>
       [/sermons_loop]
    </table>
       <div class="floatright">[next_page]</div>
       <div class="floatleft">[previous_page]</div>
       [creditlink]
</div>
HERE;
    }

    /**
     * Get the default template for single sermon pages.
     *
     * @return string The default single sermon template HTML.
     */
    public static function singleTemplate(): string
    {
        return <<<'HERE'
<div class="sermon-browser-results">
    <h2>[sermon_title] <span class="scripture">([passages_loop][passage][/passages_loop])</span> [editlink]</h2>
    [preacher_image]<span class="preacher">[preacher_link], [date]</span><br />
    Part of the [series_link] series, preached at a [service_link] service<br />
    <div class="sermon-description">[sermon_description]</div>
    <p class="sermon-tags">Tags: [tags]</p>
    [files_loop]
        [file_with_download]
    [/files_loop]
    [embed_loop]
        <br />[embed]<br />
    [/embed_loop]
    [preacher_description]
    <table class="nearby-sermons">
        <tr>
            <th class="earlier">Earlier:</th>
            <th>Same day:</th>
            <th class="later">Later:</th>
        </tr>
        <tr>
            <td class="earlier">[prev_sermon]</td>
            <td>[sameday_sermon]</td>
            <td class="later">[next_sermon]</td>
        </tr>
    </table>
    [esvtext]
       [creditlink]
</div>
HERE;
    }

    /**
     * Get the default template for sermon excerpts.
     *
     * @return string The default excerpt template HTML.
     */
    public static function excerptTemplate(): string
    {
        return <<<'HERE'
<div class="sermon-browser">
    <table class="sermons">
    [sermons_loop]
        <tr>
            <td class="sermon-title">[sermon_title]</td>
        </tr>
        <tr>
            <td class="sermon-passage">[first_passage] (Part of the [series_link] series).</td>
        </tr>
        <tr>
            <td class="files">[files_loop][file][/files_loop]</td>
        </tr>
        <tr>
            <td class="embed">[embed_loop][embed][/embed_loop]</td>
        </tr>
        <tr>
            <td class="preacher">Preached by [preacher_link] on [date] ([service_link]).</td>
        </tr>
       [/sermons_loop]
    </table>
</div>
HERE;
    }

    /**
     * Get the default CSS styles.
     *
     * @return string The default CSS with plugin URL placeholder replaced.
     */
    public static function defaultCss(): string
    {
        $css = <<<'HERE'
.sermon-browser h2 {
    clear: both;
}

div.sermon-browser table, div.sermon-browser td {
    border-top: none;
    border-bottom: none;
    border-left: none;
    border-right: none;
}

div.sermon-browser tr td {
    padding: 4px 0;
}

div.sermon-browser table.podcast table {
    margin: 0 1em;
}

div.sermon-browser td.sermon-title, div.sermon-browser td.sermon-passage {
    font-family: "Helvetica Neue",Arial,Helvetica,"Nimbus Sans L",sans-serif;
}

div.sermon-browser table.sermons {
    width: 100%;
    clear:both;
}

div.sermon-browser table.sermons td.sermon-title {
    font-weight:bold;
    font-size: 140%;
    padding-top: 2em;
}

div.sermon-browser table.sermons td.sermon-passage {
    font-weight:bold;
    font-size: 110%;
}

div.sermon-browser table.sermons td.preacher {
    border-bottom: 1px solid #444444;
    padding-bottom: 1em;
}

div.sermon-browser table.sermons td.files img {
    border: none;
    margin-right: 24px;
}

table.sermonbrowser td.fieldname {
    font-weight:bold;
    padding-right: 10px;
    vertical-align:bottom;
}

table.sermonbrowser td.field input, table.sermonbrowser td.field select{
    width: 170px;
}

table.sermonbrowser td.field  #date, table.sermonbrowser td.field #enddate {
    width: 150px;
}

table.sermonbrowser td {
    white-space: nowrap;
    padding-top: 5px;
    padding-bottom: 5px;
}

table.sermonbrowser td.rightcolumn {
    padding-left: 10px;
}

div.sermon-browser div.floatright {
    float: right
}

div.sermon-browser div.floatleft {
    float: left
}

img.sermon-icon , img.site-icon {
    border: none;
}

table.podcast {
    margin: 0 0 1em 0;
}

.podcastall {
    float:left;
    background: #fff0c8 url(**SB_PATH**/podcast_background.png) repeat-x;
    padding: 0.5em;
    font-size: 1em;
    -moz-border-radius: 7px;
    -webkit-border-radius: 7px;
}

.podcastcustom {
    float:right;
    background: #fce4ff url(**SB_PATH**/assets/images/icons/podcast_custom_background.png) repeat-x;
    padding: 0.5em;
    font-size: 1em;
    -moz-border-radius: 7px;
    -webkit-border-radius: 7px;
}

td.podcast-icon {
    padding-right:1em;
}

div.filtered, div.mainfilter {
    text-align: left;
}

div.filter {
    margin-bottom: 1em;
}

.filter-heading {
    font-weight: bold;
}

div.sermon-browser-results span.preacher {
    font-size: 120%;
}

div.sermon-browser-results span.scripture {
    font-size: 80%;
}

div.sermon-browser-results img.preacher {
    float:right;
    margin-left: 1em;
}

div.sermon-browser-results div.preacher-description {
    margin-top: 0.5em;
}

div.sermon-browser-results div.preacher-description span.about {
    font-weight: bold;
    font-size: 120%;
}

span.chapter-num {
    font-weight: bold;
    font-size: 150%;
}

span.verse-num {
    vertical-align:super;
    line-height: 1em;
    font-size: 65%;
}

div.esv span.small-caps {
    font-variant: small-caps;
}

div.net p.poetry {
    font-style: italic;
    margin: 0
}

div.sermon-browser #poweredbysermonbrowser {
    text-align:center;
}
div.sermon-browser-results #poweredbysermonbrowser {
    text-align:right;
}

table.nearby-sermons {
    width: 100%;
    clear:both;
}

table.nearby-sermons td, table.nearby-sermons th {
    text-align: center;
}

table.nearby-sermons .earlier {
    padding-right: 1em;
    text-align: left;
}

table.nearby-sermons .later {
    padding-left: 1em;
    text-align:right;
}

table.nearby-sermons td {
    width: 33%;
    vertical-align: top;
}

ul.sermon-widget {
    list-style-type:none;
    margin:0;
    padding: 0;
}

ul.sermon-widget li {
    list-style-type:none;
    margin:0;
    padding: 0.25em 0;
}

ul.sermon-widget li span.sermon-title {
    font-weight:bold;
}

div.sb_edit_link {
    display:inline;
}
h2 div.sb_edit_link {
    font-size: 80%;
}

.sb-clear {
    clear:both;
}
HERE;
        return str_replace('**SB_PATH**', SB_PLUGIN_URL, $css);
    }
}
