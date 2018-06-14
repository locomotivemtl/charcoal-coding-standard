<?php

namespace Charcoal\Sniffs\Commenting;

use Charcoal\Sniffs\Commenting\AbstractTaskSniff;

/**
 * Warns about TODO / @todo comments.
 */
class TodoSniff extends AbstractTaskSniff
{
    /**
     * A list of task types that should show warnings.
     *
     * @var array<int,string>
     */
    public $annotationNames = [
        'TODO',
    ];

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var boolean
     */
    public $error = false;
} // end class
