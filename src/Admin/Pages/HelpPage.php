<?php

/**
 * Help Page.
 *
 * Handles the Help and Japan admin pages.
 *
 * @package SermonBrowser\Admin\Pages
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Pages;

/**
 * Class HelpPage
 *
 * Displays help information, credits, and Japan ministry support page.
 */
class HelpPage
{
    /**
     * Render the help page.
     *
     * @return void
     */
    public function render(): void
    {
        sb_do_alerts();
        $this->renderHelpContent();
    }

    /**
     * Render the Japan support page.
     *
     * @return void
     */
    public function renderJapan(): void
    {
        sb_do_alerts();
        $this->renderJapanContent();
    }

    /**
     * Render the main help content.
     *
     * @return void
     */
    private function renderHelpContent(): void
    {
        ?>
    <div class="wrap">
        <a href="http://www.sermonbrowser.com/"><img src="<?php echo SB_PLUGIN_URL; ?>/assets/images/logo-small.png" width="191" height ="35" style="margin: 1em 2em; float: right;" /></a>
        <div style="width:45%;float:right;clear:right">
        <?php $this->renderThankYouSection(); ?>
        </div>
        <div style="width:45%;float:left">
        <?php $this->renderHelpSection(); ?>
        </div>
    </form>
        <?php
    }

    /**
     * Render the thank you and credits section.
     *
     * @return void
     */
    private function renderThankYouSection(): void
    {
        ?>
        <h2>Thank you</h2>
        <p>A number of individuals and churches have kindly <a href="http://www.sermonbrowser.com/donate/">donated</a> to the development of Sermon Browser. Their support is very much appreciated. Since April 2011, all donations have been sent to <a href="<?php echo admin_url('admin.php?page=sermon-browser/japan.php'); ?>">support the ministry of Nathanael and Anna Ayling</a> in Japan.</p>
        <ul style="list-style-type:circle; margin-left: 2em">
            <li><a href="http://www.cambray.org/" target="_blank">Cambray Baptist Church</a>, UK</li>
            <li><a href="https://www.bethel-clydach.co.uk/" target="_blank">Bethel Evangelical Church</a>, Clydach, UK</li>
            <li><a href="http://www.bethel-laleston.co.uk/" target="_blank">Bethel Baptist Church</a>, Laleston, UK</li>
            <li><a href="http://www.hessonchurch.com/" target="_blank">Hesson Christian Fellowship</a>, Ontario, Canada</li>
            <li><a href="http://www.icvineyard.org/" target="_blank">Vineyard Community Church</a>, Iowa</li>
            <li><a href="http://www.cbcsd.us/" target="_blank">Chinese Bible Church of San Diego</a>, California</li>
            <li><a href="http://thecreekside.org/" target="_blank">Creekside Community Church</a>, Texas</li>
            <li><a href="http://stluke.info/" target="_blank">St. Luke Lutheran Church, Gales Ferry</a>, Connecticut</li>
            <li><a href="http://www.bunnbaptistchurch.org/" target="_blank">Bunn Baptist Church</a>, North Carolina</li>
            <li><a href="http://www.ccpconline.org" target="_blank">Christ Community Presbyterian Church</a>, Florida</li>
            <li><a href="http://www.harborhawaii.org" target="_blank">Harbor Church</a>, Hawaii</li>
            <li>Vicky H, UK</li>
            <li>Ben S, UK</li>
            <li>Tom W, UK</li>
            <li>Gavin D, UK</li>
            <li>Douglas C, UK</li>
            <li>David A, UK</li>
            <li>Thomas C, Canada</li>
            <li>Daniel J, Germany</li>
            <li>Hiromi O, Japan</li>
            <li>David C, Australia</li>
            <li>Lou B, Australia</li>
            <li>Edward P, Delaware</li>
            <li>Steve J, Pensylvania</li>
            <li>William H, Indiana</li>
            <li>Brandon E, New Jersey</li>
            <li>Jamon A, Missouri</li>
            <li>Chuck H, Tennessee</li>
            <li>David F, Maryland</li>
            <li>Antony L, California</li>
            <li>David W, Florida</li>
            <li>Fabio P, Connecticut</li>
            <li>Bill C, Georgia</li>
            <li>Scott J, Florida</li>
            <li><a href="http://www.emw.org.uk/" target="_blank">Evangelical Movement of Wales</a>, UK</li>
            <li><a href="http://BetterCommunication.org" target="_blank">BetterCommunication.org</a></li>
            <li>Home and Outdoor Living, Indiana</li>
            <li><a href="http://design.ddandhservices.com/" target="_blank">DD&H Services</a>, British Columbia</li>
            <li><a href="http://www.dirtroadphotography.com" target="_blank">Dirt Road Photography</a>, Nebraska</li>
            <li><a href="http://www.hardeysolutions.com/" target="_blank">Hardey Solutions</a>, Houston</li>
            <li><a href="http://www.olivetreehost.com/" target="_blank">Olivetreehost.com</a></li>
            <li><a href="http://www.onQsites.com/" target="_blank">onQsites</a>, South Carolina</li>
            <li>Glorified Web Solutions</li>
        </ul>
        <p>Additional help was also received from:</p>
        <ul style="list-style-type:circle; margin-left: 2em">
            <li><a href="http://codeandmore.com/">Tien Do Xuan</a> (help with initial coding).
            <li>James Hudson, Matthew Hiatt, Mark Bouchard (code contributions)</li>
            <li>Juan Carlos and Marvin Ortega (Spanish translation)</li>
            <li><a href="http://www.fatcow.com/">FatCow</a> (Russian translation)</li>
            <li><a href="http://intercer.net/">Lucian Mihailescu</a> (Romanian translation)</li>
            <li>Monika Gause (German translation)</li>
            <li><a href="http://www.djio.com.br/sermonbrowser-em-portugues-brasileiro-pt_br/">DJIO</a> (Brazilian Portugese translation)</li>
            <li>Numerous <a href="http://www.sermonbrowser.com/forum/">forum contributors</a> for feature suggestions and bug reports</li>
        </ul>
        <?php
    }

    /**
     * Render the help documentation section.
     *
     * @return void
     */
    private function renderHelpSection(): void
    {
        ?>
        <h2><?php _e('Help page', 'sermon-browser'); ?></h2>
        <h3>Screencasts</h3>
        <p>If you need help with using SermonBrowser for the first time, these five minute screencast tutorials should be your first port of call (the tutorials were created with an older version of SermonBrowser, and an older version of Wordpress, but things haven't changed a great deal):</p>
        <ul>
            <li><a href="http://www.sermonbrowser.com/tutorials/#efe-swf-1" target="_blank">Installation and Overview</a></li>
            <li><a href="http://www.sermonbrowser.com/tutorials/#efe-swf-2" target="_blank">Basic Options</a></li>
            <li><a href="http://www.sermonbrowser.com/tutorials/#efe-swf-3" target="_blank">Preachers, Series and Services</a></li>
            <li><a href="http://www.sermonbrowser.com/tutorials/#efe-swf-4" target="_blank">Entering a new sermon</a></li>
            <li><a href="http://www.sermonbrowser.com/tutorials/#efe-swf-5" target="_blank">Editing a sermon and adding embedded video</a></li>
        </ul>
        <h3>Template tags</h3>
        <p>If you want to change the way SermonBrowser displays on your website, you'll need to edit the templates and/or CSS file. Check out this guide to <a href="http://www.sermonbrowser.com/customisation/" target="_blank">template tags</a>.</p>
        <h3>Shortcode</h3>
        <p>You can put individual sermons or lists of sermons on any page of your website. You do this by adding a <a href="http://www.sermonbrowser.com/customisation/" target="_blank">shortcode</a> into a WordPress post or page.</p>
        <h3>Frequently asked questions</h3>
        <p>A <a href="http://www.sermonbrowser.com/faq/" target="_blank">comprehensive FAQ</a> is available on sermonbrowser.com.</p>
        <h3>Further help</h3>
        <p>If you have a problem that the FAQ doesn't answer, or you have a feature suggestion, please use the <a href="http://www.sermonbrowser.com/forum/" target="_blank">SermonBrowser forum</a>.</p>
        </div>
        <?php
    }

    /**
     * Render the Japan ministry support content.
     *
     * @return void
     */
    private function renderJapanContent(): void
    {
        ?>
    <div class="wrap">
        <a href="http://www.sermonbrowser.com/"><img src="<?php echo SB_PLUGIN_URL; ?>/assets/images/logo-small.png" width="191" height ="35" style="margin: 1em 2em; float: right;" /></a>
        <h2 style=>Help support Christian ministry in Japan</h2>
        <div style="width:533px; float:left">
            <iframe src="http://player.vimeo.com/video/19995544?title=0&amp;byline=0&amp;portrait=0" width="533" height="300" frameborder="0"></iframe>
        </div>
        <div style="margin-left:553px;">
            <p>Since April 2011, all gifts donated to Sermon Browser have been given to support the work of <a href="https://www.bethel-clydach.co.uk/about/mission-partners/nathanael-and-anna-ayling/">Nathanael and Anna Ayling</a> in Japan.
             Nathanael and Anna are members of a small church in the UK where the the author of Sermon Browser is a minister. Together with little Ethan, they have been in Japan since April 2010, and are based in Sappororo in the north,
             undergoing intensive language training so that by God's grace they can work alongside Japanese Christians to make disciples of Jesus among Japanese students. They are being cared for by <a href="http://www.omf.org/omf/japan/about_us">OMF International</a> (formerly known as the China Inland Mission, and founded by
             Hudson Taylor in 1865).</p>
             <p>If you value Sermon Browser, please consider supporting Nathanael and Anna. You can do this by:</p>
             <ul>
                 <li><a href="http://ateamjapan.wordpress.com/">Looking at their blog</a>, and praying about their latest news.</li>
                 <li><a href="http://www.omf.org/omf/uk/omf_at_work/pray_for_omf_workers">Signing up</a> to receiving their regular prayer news.</li>
                 <li><form style="float:left" action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick" /><input type="hidden" name="hosted_button_id" value="YTB9ZW4P5F536" /><input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110429-1/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" /><img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110429-1/en_GB/i/scr/pixel.gif" width="1" height="1" /></form> towards their ongoing support.</li>
             </ul>
        </div>
    </div>
        <?php
    }
}
