<?php

namespace Charcoal\CodeSniffer\Sniffs\Commenting;

/**
 * Discourages the use of comment tags.
 */
class DiscouragedTagsSniff extends ForbiddenTagsSniff
{
    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var boolean
     */
    public $error = false;

    /**
     * A list of forbidden comment tags with their alternatives.
     *
     * The value is NULL if no alternative exists.
     * IE, the tag should just not be used.
     *
     * @var array<string, string|null>
     */
    public $forbiddenTags = [];
} // end class
