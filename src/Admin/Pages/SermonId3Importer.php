<?php

/**
 * ID3 Tag Importer for Sermon Editor.
 *
 * Handles importing metadata from audio files via ID3 tags.
 *
 * @package SermonBrowser\Admin\Pages
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Pages;

use SermonBrowser\Facades\File;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Preacher;

/**
 * Class SermonId3Importer
 *
 * Extracts and imports ID3 metadata from audio files.
 */
class SermonId3Importer
{
    /**
     * Handle ID3 tag import from audio files.
     *
     * @return array Imported ID3 tags.
     */
    public function import(): array
    {
        $id3_tags = [];

        if (!isset($_GET['getid3'])) {
            return $id3_tags;
        }

        $file_data = File::find((int) $_GET['getid3']);

        if ($file_data === null) {
            return $id3_tags;
        }

        if (!class_exists('getID3')) {
            require_once ABSPATH . WPINC . '/ID3/getid3.php'; // NOSONAR S4833 - WordPress bundled library not namespaced
        }

        $getID3 = new \getID3();

        if ($file_data->type === 'url') {
            $id3_raw_tags = $this->analyzeRemoteFile($getID3, $file_data->name);
            $filename = substr($file_data->name, strrpos($file_data->name, '/') + 1);
        } else {
            $filename = $file_data->name;
            $id3_raw_tags = $getID3->analyze(realpath(SB_ABSPATH . sb_get_option('upload_dir') . $filename));
        }

        if (!isset($id3_raw_tags['tags'])) {
            echo '<div id="message" class="updated fade"><p><b>' . __('No ID3 tags found.', 'sermon-browser');
            if ($file_data->type === 'url') {
                echo ' Remote files must have id3v2 tags.';
            }
            echo '</b></div>';
        }

        \getid3_lib::CopyTagsToComments($id3_raw_tags);

        return $this->extractEnabledFields($id3_raw_tags, $filename);
    }

    /**
     * Extract enabled fields from ID3 tags based on plugin settings.
     *
     * @param array $id3_raw_tags Raw ID3 tag data.
     * @param string $filename The filename for date parsing.
     * @return array Extracted ID3 tags.
     */
    private function extractEnabledFields(array $id3_raw_tags, string $filename): array
    {
        $id3_tags = [];

        if (sb_get_option('import_title')) {
            $id3_tags['title'] = $id3_raw_tags['comments_html']['title'][0] ?? '';
        }
        if (sb_get_option('import_comments')) {
            $id3_tags['description'] = $id3_raw_tags['comments_html']['comments'][0] ?? '';
        }
        if (sb_get_option('import_album')) {
            $id3_tags['series'] = $this->importSeries($id3_raw_tags['comments_html']['album'][0] ?? '');
        }
        if (sb_get_option('import_artist')) {
            $id3_tags['preacher'] = $this->importPreacher($id3_raw_tags['comments_html']['artist'][0] ?? '');
        }

        // Import date from filename.
        $date_format = sb_get_option('import_filename');
        if ($date_format !== '') {
            $id3_tags['date'] = $this->parseDateFromFilename($filename, $date_format);
        }

        return $id3_tags;
    }

    /**
     * Analyze a remote file for ID3 tags.
     *
     * @param \getID3 $getID3 GetID3 instance.
     * @param string $url Remote file URL.
     * @return array ID3 tags.
     */
    private function analyzeRemoteFile(\getID3 $getID3, string $url): array
    {
        $sermonUploadDir = SB_ABSPATH . sb_get_option('upload_dir');
        $tempfilename = $sermonUploadDir . sb_generate_temp_suffix(2) . '.mp3';

        $tempfile = @fopen($tempfilename, 'wb');
        if (!$tempfile) {
            return [];
        }

        $remote_file = @fopen($url, 'r');
        if (!$remote_file) {
            fclose($tempfile);
            return [];
        }

        $remote_contents = '';
        while (!feof($remote_file)) {
            $remote_contents .= fread($remote_file, 8192);
            if (strlen($remote_contents) > 65536) {
                break;
            }
        }

        fwrite($tempfile, $remote_contents);
        fclose($remote_file);
        fclose($tempfile);

        $id3_raw_tags = $getID3->analyze(realpath($tempfilename));
        unlink($tempfilename);

        return $id3_raw_tags;
    }

    /**
     * Import or create a series from ID3 album tag.
     *
     * @param string $album Album name.
     * @return int|string Series ID or empty string.
     */
    private function importSeries(string $album)
    {
        if ($album === '') {
            return '';
        }

        return Series::findOrCreate($album);
    }

    /**
     * Import or create a preacher from ID3 artist tag.
     *
     * @param string $artist Artist name.
     * @return int|string Preacher ID or empty string.
     */
    private function importPreacher(string $artist)
    {
        if ($artist === '') {
            return '';
        }

        return Preacher::findOrCreate($artist);
    }

    /**
     * Parse a date from a filename.
     *
     * @param string $filename The filename.
     * @param string $format Date format (uk, us, int).
     * @return string Formatted date or empty string.
     */
    private function parseDateFromFilename(string $filename, string $format): string
    {
        $filename = substr($filename, 0, strrpos($filename, '.'));
        $filename = str_replace('--', '-', str_replace('/', '-', $filename));
        $filename = trim(preg_replace('/[^0-9-]/', '', $filename), '-');
        $date = explode('-', $filename);

        if (count($date) < 3) {
            return '';
        }

        $formatMap = [
            'uk' => fn($d) => date('Y-m-d', mktime(0, 0, 0, (int) $d[1], (int) $d[0], (int) $d[2])),
            'us' => fn($d) => date('Y-m-d', mktime(0, 0, 0, (int) $d[0], (int) $d[1], (int) $d[2])),
            'int' => fn($d) => date('Y-m-d', mktime(0, 0, 0, (int) $d[1], (int) $d[2], (int) $d[0])),
        ];

        return isset($formatMap[$format]) ? $formatMap[$format]($date) : '';
    }
}
