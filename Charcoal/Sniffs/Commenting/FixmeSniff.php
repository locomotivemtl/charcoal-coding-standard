<?php

namespace Charcoal\CodeSniffer\Sniffs\Commenting;

/**
 * Warns about FIXME / @todo comments.
 */
class FixmeSniff extends AbstractTaskSniff
{
    /**
     * A list of task types that should show errors with their severity level.
     *
     * A value of 0 will be converted into the default severity level.
     *
     * @var array<string, int>
     */
    public $taskNames = [
        'FIXME' => 0,
    ];

    /**
     * A list of tag names to look up with their default task name.
     *
     * The value is NULL if no default task should be assigned. IE,
     * the tag will be analysed but ignored if no task is assigned.
     *
     * @var array<string, string|null>
     */
    public $tagNames = [
        '@todo' => null,
    ];

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var boolean
     */
    public $error = true;
} // end class
