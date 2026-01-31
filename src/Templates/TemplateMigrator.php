<?php
/**
 * Template Migrator for plugin upgrades.
 *
 * Handles migration of templates during plugin upgrade:
 * - Backs up existing templates
 * - Validates template tags
 * - Clears generated output cache
 *
 * @package SermonBrowser\Templates
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Templates;

/**
 * Class TemplateMigrator
 *
 * Migrates templates from the old eval()-based system to the new
 * regex-based TagParser/TagRenderer system.
 */
class TemplateMigrator
{
    /**
     * The tag renderer instance for getting available tags.
     *
     * @var TagRenderer
     */
    private TagRenderer $renderer;

    /**
     * Option names for templates.
     */
    private const SEARCH_TEMPLATE_OPTION = 'sb_search_template';
    private const SINGLE_TEMPLATE_OPTION = 'sb_single_template';
    private const SEARCH_BACKUP_OPTION = 'sb_search_template_backup';
    private const SINGLE_BACKUP_OPTION = 'sb_single_template_backup';
    private const SEARCH_OUTPUT_OPTION = 'sb_search_output';
    private const SINGLE_OUTPUT_OPTION = 'sb_single_output';

    /**
     * Constructor.
     *
     * @param TagRenderer|null $renderer The tag renderer instance.
     */
    public function __construct(?TagRenderer $renderer = null)
    {
        $this->renderer = $renderer ?? new TagRenderer();
    }

    /**
     * Perform the template migration.
     *
     * This method:
     * 1. Backs up existing templates
     * 2. Validates all tags in templates are supported
     * 3. Deletes the generated output options
     * 4. Returns a result with any issues found
     *
     * @return MigrationResult The result of the migration.
     */
    public function migrate(): MigrationResult
    {
        // Load templates once
        $searchTemplate = $this->getTemplateOption(self::SEARCH_TEMPLATE_OPTION);
        $singleTemplate = $this->getTemplateOption(self::SINGLE_TEMPLATE_OPTION);

        // Step 1: Backup existing templates
        $this->backupTemplates($searchTemplate, $singleTemplate);

        // Step 2: Validate all tags in templates
        $unknownTags = $this->validateTemplates($searchTemplate, $singleTemplate);

        // Step 3: Delete output options (generated PHP code)
        $this->deleteOutputOptions();

        // Step 4: Return result
        return new MigrationResult($unknownTags);
    }

    /**
     * Backup existing templates to backup options.
     *
     * @param string $searchTemplate The search template content.
     * @param string $singleTemplate The single template content.
     * @return void
     */
    private function backupTemplates(string $searchTemplate, string $singleTemplate): void
    {
        update_option(self::SEARCH_BACKUP_OPTION, $searchTemplate);
        update_option(self::SINGLE_BACKUP_OPTION, $singleTemplate);
    }

    /**
     * Validate all tags in templates and return any unknown tags.
     *
     * @param string $searchTemplate The search template content.
     * @param string $singleTemplate The single template content.
     * @return array<string> List of unknown tag names.
     */
    private function validateTemplates(string $searchTemplate, string $singleTemplate): array
    {
        $availableTags = $this->renderer->getAvailableTags();

        $unknownTags = [];

        // Extract and validate tags from search template
        $unknownTags = array_merge(
            $unknownTags,
            $this->findUnknownTags($searchTemplate, $availableTags)
        );

        // Extract and validate tags from single template
        $unknownTags = array_merge(
            $unknownTags,
            $this->findUnknownTags($singleTemplate, $availableTags)
        );

        // Return unique unknown tags
        return array_values(array_unique($unknownTags));
    }

    /**
     * Find unknown tags in a template.
     *
     * @param string $template The template content.
     * @param array<string> $availableTags List of available tag names.
     * @return array<string> List of unknown tag names found.
     */
    private function findUnknownTags(string $template, array $availableTags): array
    {
        if (empty($template)) {
            return [];
        }

        $unknownTags = [];

        // Match all [tag_name] patterns
        // Pattern: [lowercase letters, underscores, and optional leading slash]
        $pattern = '/\[([a-z_\/]+)\]/';

        if (preg_match_all($pattern, $template, $matches)) {
            foreach ($matches[1] as $tagName) {
                if (!in_array($tagName, $availableTags, true)) {
                    $unknownTags[] = $tagName;
                }
            }
        }

        return $unknownTags;
    }

    /**
     * Delete the output options (generated PHP code from old system).
     *
     * @return void
     */
    private function deleteOutputOptions(): void
    {
        delete_option(self::SEARCH_OUTPUT_OPTION);
        delete_option(self::SINGLE_OUTPUT_OPTION);
    }

    /**
     * Get a template option value, handling false/null returns.
     *
     * @param string $optionName The option name.
     * @return string The template content, or empty string if not found.
     */
    private function getTemplateOption(string $optionName): string
    {
        $value = get_option($optionName);

        if ($value === false || $value === null) {
            return '';
        }

        return (string) $value;
    }
}
