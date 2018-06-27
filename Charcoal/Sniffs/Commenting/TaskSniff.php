<?php

namespace Charcoal\CodeSniffer\Sniffs\Commenting;

use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Warns about TODO / @todo comments.
 */
interface TaskSniff extends Sniff
{
    const CODE_COMMENT_FOUND = 'CommentFound';
    const CODE_TAG_FOUND     = 'TagFound';
    const CODE_TASK_FOUND    = 'TaskFound';
} // end class
