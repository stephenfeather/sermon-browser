<?php

/**
 * Migration Result value class.
 *
 * Holds the results of a template migration operation including any
 * unknown tags found during validation.
 *
 * @package SermonBrowser\Templates
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Templates;

/**
 * Class MigrationResult
 *
 * Value object representing the result of a template migration.
 * Contains information about unknown tags and provides user-friendly messages.
 */
class MigrationResult
{
    /**
     * Array of unknown tag names found during migration.
     *
     * @var array<string>
     */
    private array $unknownTags;

    /**
     * Constructor.
     *
     * @param array<string> $unknownTags List of unknown tag names found in templates.
     */
    public function __construct(array $unknownTags = [])
    {
        // Store only unique tags
        $this->unknownTags = array_values(array_unique($unknownTags));
    }

    /**
     * Check if the migration was successful (no unknown tags).
     *
     * @return bool True if no unknown tags were found.
     */
    public function isSuccess(): bool
    {
        return empty($this->unknownTags);
    }

    /**
     * Check if there are warnings (unknown tags found).
     *
     * @return bool True if unknown tags were found.
     */
    public function hasWarnings(): bool
    {
        return !empty($this->unknownTags);
    }

    /**
     * Get the list of unknown tag names.
     *
     * @return array<string> The unknown tag names.
     */
    public function getUnknownTags(): array
    {
        return $this->unknownTags;
    }

    /**
     * Get a user-friendly message about the migration result.
     *
     * @return string The message describing the migration result.
     */
    public function getMessage(): string
    {
        if ($this->isSuccess()) {
            return 'Template migration completed successfully. Templates have been backed up and output cache has been cleared.';
        }

        $tagCount = count($this->unknownTags);
        $tagList = implode(', ', $this->unknownTags);

        return sprintf(
            'Template migration completed with warnings. Found %d unknown tag(s) that may not render correctly: %s. ' .
            'Please review your templates and update any custom or deprecated tags.',
            $tagCount,
            $tagList
        );
    }
}
