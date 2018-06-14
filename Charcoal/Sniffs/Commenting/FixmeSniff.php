<?php

namespace Charcoal\Sniffs\Commenting;

use Charcoal\Sniffs\Commenting\AbstractTaskSniff;

/**
 * Warns about FIXME / @todo comments.
 */
class FixmeSniff extends AbstractTaskSniff
{
    /**
     * A list of task types that should show errors.
     *
     * @var array<int,string>
     */
    public $annotationNames = [
        'FIXME',
    ];

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var boolean
     */
    public $error = true;
} // end class
