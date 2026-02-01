<?php

declare(strict_types=1);

namespace SermonBrowser\Install;

/**
 * Handles Sermon Browser plugin installation.
 *
 * Creates database tables and sets default options when the plugin is activated.
 */
class Installer
{
    /**
     * Run the installation process.
     *
     * Creates upload directories, database tables, and sets default options.
     *
     * @return void
     */
    public static function run(): void
    {
        global $wpdb;

        self::createUploadDirectories();
        self::createTables($wpdb);
        self::setDefaultOptions($wpdb);
    }

    /**
     * Create the upload directories for sermon files.
     *
     * @return void
     */
    private static function createUploadDirectories(): void
    {
        $sermonUploadDir = sb_get_default('sermon_path');

        if (!is_dir(SB_ABSPATH . $sermonUploadDir)) {
            sb_mkdir(SB_ABSPATH . $sermonUploadDir);
        }

        if (!is_dir(SB_ABSPATH . $sermonUploadDir . 'images')) {
            sb_mkdir(SB_ABSPATH . $sermonUploadDir . 'images');
        }
    }

    /**
     * Create all database tables.
     *
     * @param \wpdb $wpdb The WordPress database object.
     * @return void
     */
    private static function createTables(\wpdb $wpdb): void
    {
        self::createPreachersTable($wpdb);
        self::createSeriesTable($wpdb);
        self::createServicesTable($wpdb);
        self::createSermonsTable($wpdb);
        self::createBooksSermonsTable($wpdb);
        self::createBooksTable($wpdb);
        self::createStuffTable($wpdb);
        self::createTagsTable($wpdb);
        self::createSermonTagsTable($wpdb);
    }

    /**
     * Create the preachers table.
     *
     * @param \wpdb $wpdb The WordPress database object.
     * @return void
     */
    private static function createPreachersTable(\wpdb $wpdb): void
    {
        $tableName = "{$wpdb->prefix}sb_preachers";

        if ($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") !== $tableName) {
            $sql = "CREATE TABLE {$tableName} (
                id INT(10) NOT NULL AUTO_INCREMENT,
                name VARCHAR(30) NOT NULL,
                description TEXT NOT NULL,
                image VARCHAR(255) NOT NULL,
                PRIMARY KEY (id)
            )";
            $wpdb->query($sql);
            $wpdb->query("INSERT INTO {$tableName} (name, description, image) VALUES ('C H Spurgeon', '', '')");
            $wpdb->query("INSERT INTO {$tableName} (name, description, image) VALUES ('Martyn Lloyd-Jones', '', '')");
        }
    }

    /**
     * Create the series table.
     *
     * @param \wpdb $wpdb The WordPress database object.
     * @return void
     */
    private static function createSeriesTable(\wpdb $wpdb): void
    {
        $tableName = "{$wpdb->prefix}sb_series";

        if ($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") !== $tableName) {
            $sql = "CREATE TABLE {$tableName} (
                id INT(10) NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                page_id INT(10) NOT NULL,
                PRIMARY KEY (id)
            )";
            $wpdb->query($sql);
            $wpdb->query("INSERT INTO {$tableName} (name, page_id) VALUES ('Exposition of the Psalms', 0)");
            $wpdb->query("INSERT INTO {$tableName} (name, page_id) VALUES ('Exposition of Romans', 0)");
        }
    }

    /**
     * Create the services table.
     *
     * @param \wpdb $wpdb The WordPress database object.
     * @return void
     */
    private static function createServicesTable(\wpdb $wpdb): void
    {
        $tableName = "{$wpdb->prefix}sb_services";

        if ($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") !== $tableName) {
            $sql = "CREATE TABLE {$tableName} (
                id INT(10) NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                time VARCHAR(5) NOT NULL,
                PRIMARY KEY (id)
            )";
            $wpdb->query($sql);
            $wpdb->query("INSERT INTO {$tableName} (name, time) VALUES ('Sunday Morning', '10:30')");
            $wpdb->query("INSERT INTO {$tableName} (name, time) VALUES ('Sunday Evening', '18:00')");
            $wpdb->query("INSERT INTO {$tableName} (name, time) VALUES ('Midweek Meeting', '19:00')");
            $wpdb->query("INSERT INTO {$tableName} (name, time) VALUES ('Special event', '20:00')");
        }
    }

    /**
     * Create the sermons table.
     *
     * @param \wpdb $wpdb The WordPress database object.
     * @return void
     */
    private static function createSermonsTable(\wpdb $wpdb): void
    {
        $tableName = "{$wpdb->prefix}sb_sermons";

        if ($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") !== $tableName) {
            $sql = "CREATE TABLE {$tableName} (
                id INT(10) NOT NULL AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                preacher_id INT(10) NOT NULL,
                datetime DATETIME NOT NULL,
                service_id INT(10) NOT NULL,
                series_id INT(10) NOT NULL,
                start TEXT NOT NULL,
                end TEXT NOT NULL,
                description TEXT,
                time VARCHAR (5),
                override TINYINT (1),
                page_id INT(10) NOT NULL,
                PRIMARY KEY (id)
            )";
            $wpdb->query($sql);
        }
    }

    /**
     * Create the books_sermons junction table.
     *
     * @param \wpdb $wpdb The WordPress database object.
     * @return void
     */
    private static function createBooksSermonsTable(\wpdb $wpdb): void
    {
        $tableName = "{$wpdb->prefix}sb_books_sermons";

        if ($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") !== $tableName) {
            $sql = "CREATE TABLE {$tableName} (
                id INT(10) NOT NULL AUTO_INCREMENT,
                book_name VARCHAR(30) NOT NULL,
                chapter INT(10) NOT NULL,
                verse INT(10) NOT NULL,
                `order` INT(10) NOT NULL,
                type VARCHAR (30) DEFAULT NULL,
                sermon_id INT(10) NOT NULL,
                PRIMARY KEY (id),
                KEY sermon_id (sermon_id)
            )";
            $wpdb->query($sql);
        }
    }

    /**
     * Create the books table.
     *
     * @param \wpdb $wpdb The WordPress database object.
     * @return void
     */
    private static function createBooksTable(\wpdb $wpdb): void
    {
        $tableName = "{$wpdb->prefix}sb_books";

        if ($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") !== $tableName) {
            $sql = "CREATE TABLE {$tableName} (
                id INT(10) NOT NULL AUTO_INCREMENT,
                name VARCHAR(30) NOT NULL,
                PRIMARY KEY (id)
            )";
            $wpdb->query($sql);
        }
    }

    /**
     * Create the stuff (attachments) table.
     *
     * @param \wpdb $wpdb The WordPress database object.
     * @return void
     */
    private static function createStuffTable(\wpdb $wpdb): void
    {
        $tableName = "{$wpdb->prefix}sb_stuff";

        if ($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") !== $tableName) {
            $sql = "CREATE TABLE {$tableName} (
                id INT(10) NOT NULL AUTO_INCREMENT ,
                type VARCHAR(30) NOT NULL,
                name TEXT NOT NULL,
                sermon_id INT(10) NOT NULL,
                count INT(10) NOT NULL,
                duration VARCHAR (6) NOT NULL,
                PRIMARY KEY (id)
            )";
            $wpdb->query($sql);
        }
    }

    /**
     * Create the tags table.
     *
     * @param \wpdb $wpdb The WordPress database object.
     * @return void
     */
    private static function createTagsTable(\wpdb $wpdb): void
    {
        $tableName = "{$wpdb->prefix}sb_tags";

        if ($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") !== $tableName) {
            $sql = "CREATE TABLE {$tableName} (
                id int(10) NOT NULL auto_increment,
                name varchar(255) default NULL,
                PRIMARY KEY (id),
                UNIQUE KEY name (name)
            )";
            $wpdb->query($sql);
        }
    }

    /**
     * Create the sermons_tags junction table.
     *
     * @param \wpdb $wpdb The WordPress database object.
     * @return void
     */
    private static function createSermonTagsTable(\wpdb $wpdb): void
    {
        $tableName = "{$wpdb->prefix}sb_sermons_tags";

        if ($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") !== $tableName) {
            $sql = "CREATE TABLE {$tableName} (
                id INT(10) NOT NULL AUTO_INCREMENT,
                sermon_id INT(10) NOT NULL,
                tag_id INT(10) NOT NULL,
                INDEX (sermon_id),
                PRIMARY KEY (id)
            )";
            $wpdb->query($sql);
        }
    }

    /**
     * Set default plugin options.
     *
     * @param \wpdb $wpdb The WordPress database object.
     * @return void
     */
    private static function setDefaultOptions(\wpdb $wpdb): void
    {
        $sermonUploadDir = sb_get_default('sermon_path');

        sb_update_option('upload_dir', $sermonUploadDir);
        sb_update_option('upload_url', sb_get_default('attachment_url'));
        sb_update_option('podcast_url', site_url() . '?podcast');
        sb_update_option('display_method', 'dynamic');
        sb_update_option('sermons_per_page', '10');
        sb_update_option('search_template', DefaultTemplates::multiTemplate());
        sb_update_option('single_template', DefaultTemplates::singleTemplate());
        sb_update_option('css_style', DefaultTemplates::defaultCss());
        sb_update_option('style_date_modified', strtotime('now'));

        $books = sb_get_default('eng_bible_books');
        foreach ($books as $book) {
            $wpdb->query("INSERT INTO {$wpdb->prefix}sb_books VALUES (null, '{$book}')");
        }

        sb_update_option('db_version', SB_DATABASE_VERSION);
        sb_update_option('filter_type', 'oneclick');
        sb_update_option('filter_hide', 'hide');
        sb_update_option('import_prompt', true);
        sb_update_option('hide_no_attachments', false);
        sb_update_option('import_title', false);
        sb_update_option('import_artist', false);
        sb_update_option('import_album', false);
        sb_update_option('import_comments', false);
        sb_update_option('import_filename', 'none');
        sb_update_option('mp3_shortcode', '[audio mp3="%SERMONURL%"]');
    }
}
