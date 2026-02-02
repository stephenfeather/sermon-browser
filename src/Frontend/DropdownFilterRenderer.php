<?php

/**
 * Dropdown Filter Renderer for sermon search page.
 *
 * Handles rendering of the dropdown-style filter form.
 *
 * @package SermonBrowser\Frontend
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Frontend;

use SermonBrowser\Config\Defaults;
use SermonBrowser\Constants;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Service;
use SermonBrowser\Facades\Book;

/**
 * Class DropdownFilterRenderer
 *
 * Renders the dropdown-style filter form for the sermon search page.
 */
class DropdownFilterRenderer
{
    /**
     * Render the dropdown filter type.
     *
     * @param bool $hideFilter Whether to hide filter by default.
     * @param string $jsHide JavaScript for hide functionality.
     * @return void
     */
    public static function render(bool $hideFilter, string $jsHide): void
    {
        $translatedBooks = array_combine(
            Defaults::get('eng_bible_books'),
            Defaults::get('bible_books')
        );

        $preachers = Preacher::findAllForFilter();
        $series = Series::findAllForFilter();
        $services = Service::findAllForFilter();
        $bookCount = Book::findAllWithSermonCount();

        $sortByOptions = [
            'Title' => 'm.title',
            'Preacher' => 'preacher',
            'Date' => 'm.datetime',
            'Passage' => 'b.id',
        ];

        $directionOptions = [
            __('Ascending', 'sermon-browser') => 'asc',
            __('Descending', 'sermon-browser') => 'desc',
        ];

        $currentSortBy = isset($_REQUEST['sortby']) ? sanitize_text_field($_REQUEST['sortby']) : 'm.datetime';
        $currentDir = 'desc';
        if (isset($_REQUEST['dir'])) {
            $dir = strtolower($_REQUEST['dir']);
            if ($dir === 'asc' || $dir === 'desc') {
                $currentDir = $dir;
            }
        }

        self::renderHtml(
            ['preachers' => $preachers, 'series' => $series, 'services' => $services],
            ['count' => $bookCount, 'translations' => $translatedBooks],
            [
                'byOptions' => $sortByOptions,
                'dirOptions' => $directionOptions,
                'currentBy' => $currentSortBy,
                'currentDir' => $currentDir,
            ]
        );

        self::renderJs($hideFilter, $jsHide);
    }

    /**
     * Render the dropdown filter HTML form.
     *
     * @param array{preachers: array<object>, series: array<object>, services: array<object>} $entities Entity data.
     * @param array{count: array<object>, translations: array<string, string>} $books Book data.
     * @param array{byOptions: array<string, string>, dirOptions: array<string, string>, currentBy: string, currentDir: string} $sorting Sorting options.
     * @return void
     */
    private static function renderHtml(
        array $entities,
        array $books,
        array $sorting
    ): void {
        ?>
        <span class="inline_controls"><button type="button" id="show_hide_filter" class="button-link" aria-label="<?php esc_attr_e('Toggle filter', 'sermon-browser'); ?>"></button></span>
        <div id="mainfilter">
            <form method="post" id="sermon-filter" action="<?php echo esc_url(PageResolver::getDisplayUrl()); ?>">
                <div style="clear:both">
                    <table class="sermonbrowser">
                        <tr>
                            <th scope="row" class="fieldname"><?php esc_html_e('Preacher', 'sermon-browser'); ?></th>
                            <td class="field">
                                <?php self::renderEntitySelect('preacher', $entities['preachers']); ?>
                            </td>
                            <th scope="row" class="fieldname rightcolumn"><?php esc_html_e('Services', 'sermon-browser'); ?></th>
                            <td class="field">
                                <?php self::renderEntitySelect('service', $entities['services']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" class="fieldname"><?php esc_html_e('Book', 'sermon-browser'); ?></th>
                            <td class="field">
                                <?php self::renderBookSelect($books['count'], $books['translations']); ?>
                            </td>
                            <th scope="row" class="fieldname rightcolumn"><?php esc_html_e('Series', 'sermon-browser'); ?></th>
                            <td class="field">
                                <?php self::renderEntitySelect('series', $entities['series']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" class="fieldname"><?php esc_html_e('Start date', 'sermon-browser'); ?></th>
                            <td class="field"><input type="text" name="date" id="date" value="<?php echo esc_attr($_REQUEST['date'] ?? ''); ?>" /></td>
                            <th scope="row" class="fieldname rightcolumn"><?php esc_html_e('End date', 'sermon-browser'); ?></th>
                            <td class="field"><input type="text" name="enddate" id="enddate" value="<?php echo esc_attr($_REQUEST['enddate'] ?? ''); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row" class="fieldname"><?php esc_html_e('Keywords', 'sermon-browser'); ?></th>
                            <td class="field" colspan="3"><input style="width: 98.5%" type="text" id="title" name="title" value="<?php echo esc_attr($_REQUEST['title'] ?? ''); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row" class="fieldname"><?php esc_html_e('Sort by', 'sermon-browser'); ?></th>
                            <td class="field">
                                <?php self::renderSortSelect($sorting['byOptions'], $sorting['currentBy']); ?>
                            </td>
                            <th scope="row" class="fieldname rightcolumn"><?php esc_html_e('Direction', 'sermon-browser'); ?></th>
                            <td class="field">
                                <?php self::renderDirectionSelect($sorting['dirOptions'], $sorting['currentDir']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">&nbsp;</td>
                            <td class="field"><input type="submit" class="filter" value="<?php esc_attr_e('Filter &raquo;', 'sermon-browser'); ?>"></td>
                        </tr>
                    </table>
                    <input type="hidden" name="page" value="1">
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render a select element for an entity (preacher, service, series).
     *
     * @param string $name The select name/id.
     * @param array<object> $items The items to render as options.
     * @return void
     */
    private static function renderEntitySelect(string $name, array $items): void
    {
        $currentValue = $_REQUEST[$name] ?? 0;
        $isAllSelected = !isset($_REQUEST[$name]) || $_REQUEST[$name] == 0;
        ?>
        <select name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>">
            <option value="0" <?php echo $isAllSelected ? Constants::SELECTED : ''; ?>><?php esc_html_e(Constants::ALL_FILTER, 'sermon-browser'); ?></option>
            <?php foreach ($items as $item) : ?>
            <option value="<?php echo (int) $item->id; ?>" <?php echo $currentValue == $item->id ? Constants::SELECTED : ''; ?>><?php echo esc_html(stripslashes($item->name) . ' (' . $item->count . ')'); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render the book select element.
     *
     * @param array<object> $bookCount Book count data.
     * @param array<string, string> $translatedBooks Book translations.
     * @return void
     */
    private static function renderBookSelect(array $bookCount, array $translatedBooks): void
    {
        ?>
        <select name="book">
            <option value=""><?php esc_html_e(Constants::ALL_FILTER, 'sermon-browser'); ?></option>
            <?php foreach ($bookCount as $book) : ?>
                <?php
                $bookName = stripslashes($book->name);
                $displayName = $translatedBooks[$bookName] ?? $bookName;
                $isSelected = isset($_REQUEST['book']) && $_REQUEST['book'] === $book->name;
                ?>
            <option value="<?php echo esc_attr($book->name); ?>" <?php echo $isSelected ? Constants::SELECTED : ''; ?>><?php echo esc_html($displayName . ' (' . $book->count . ')'); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render the sort by select element.
     *
     * @param array<string, string> $sortByOptions Sort by options.
     * @param string $currentSortBy Current sort by value.
     * @return void
     */
    private static function renderSortSelect(array $sortByOptions, string $currentSortBy): void
    {
        ?>
        <select name="sortby" id="sortby">
            <?php foreach ($sortByOptions as $label => $value) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php echo $currentSortBy === $value ? Constants::SELECTED : ''; ?>><?php esc_html_e($label, 'sermon-browser'); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render the direction select element.
     *
     * @param array<string, string> $directionOptions Direction options.
     * @param string $currentDir Current direction value.
     * @return void
     */
    private static function renderDirectionSelect(array $directionOptions, string $currentDir): void
    {
        ?>
        <select name="dir" id="dir">
            <?php foreach ($directionOptions as $label => $value) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php echo $currentDir === $value ? Constants::SELECTED : ''; ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render JavaScript for dropdown filter (datepicker).
     *
     * @param bool $hideFilter Whether filter is hidden by default.
     * @param string $jsHide Hide/show JavaScript.
     * @return void
     */
    private static function renderJs(bool $hideFilter, string $jsHide): void
    {
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                $('#date').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: new Date(1970, 0, 1),
                    changeMonth: true,
                    changeYear: true
                });
                $('#enddate').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: new Date(1970, 0, 1),
                    changeMonth: true,
                    changeYear: true
                });
                <?php if ($hideFilter === true) : ?>
                    <?php echo $jsHide; ?>
                <?php endif; ?>
            });
        </script>
        <?php
    }
}
