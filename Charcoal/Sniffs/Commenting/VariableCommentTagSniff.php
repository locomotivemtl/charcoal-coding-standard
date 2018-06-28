<?php

namespace Charcoal\CodeSniffer\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows the use of comment tags for variables.
 */
class VariableCommentTagSniff extends ForbiddenTagsSniff
{
    const MESSAGE_TAG_FORBIDDEN   = '%s tag is forbidden in variable comment';
    const MESSAGE_TAG_DISCOURAGED = '%s tag is discouraged in variable comment';

    /**
     * A list of forbidden comment tags with their alternatives.
     *
     * The value is NULL if no alternative exists.
     * IE, the tag should just not be used.
     *
     * @var array<string, string|null>
     */
    public $forbiddenTags = [];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    final public function registerTokens()
    {
        return [ T_VARIABLE ];
    } // end registerTokens()

    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token
     *                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        // Bail early if there's nothing to search
        if (empty($this->forbiddenTags)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $ignore = [
            T_PUBLIC,
            T_PRIVATE,
            T_PROTECTED,
            T_VAR,
            T_STATIC,
            T_WHITESPACE,
        ];

        $commentEnd = $phpcsFile->findPrevious($ignore, ($stackPtr - 1), null, true);

        if ($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG) {
            return;
        }

        $this->processTags($phpcsFile, $stackPtr, $tokens[$commentEnd]['comment_opener']);
    } // end process()
} // end class