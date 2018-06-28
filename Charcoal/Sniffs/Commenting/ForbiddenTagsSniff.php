<?php

namespace Charcoal\CodeSniffer\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Forbids the use of comment tags.
 */
class ForbiddenTagsSniff implements Sniff
{
    const CODE_TAG_FOUND       = 'Found';
    const CODE_TAG_DISCOURAGED = 'Discouraged';
    const CODE_TAG_ALTERNATIVE = 'WithAlternative';

    const MESSAGE_TAG_FORBIDDEN   = 'Use of comment tag %s is forbidden';
    const MESSAGE_TAG_DISCOURAGED = 'Use of comment tag %s is discouraged';

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = [
        'PHP',
        'JS',
    ];

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var boolean
     */
    public $error = true;

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
     * A cache of forbidden comment tags, for faster lookups.
     *
     * @var string[]
     */
    protected $forbiddenCommentTags = [];

    /**
     * If true, forbidden comment tags will be considered regular expressions.
     *
     * @var boolean
     */
    protected $patternMatch = false;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    final public function register()
    {
        $this->registerAnnotations();

        return $this->registerTokens();
    } // end register()

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function registerTokens()
    {
        return [ T_DOC_COMMENT_OPEN_TAG ];
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

        $this->processTags($phpcsFile, $stackPtr, $stackPtr);
    } // end process()

    /**
     * Processes each required or optional tag.
     *
     * @param  File $phpcsFile    The file being scanned.
     * @param  int  $stackPtr     The position of the current token
     *                            in the stack passed in $tokens.
     * @param  int  $commentStart Position in the stack where the comment started.
     *
     * @return void
     */
    protected function processTags($phpcsFile, $stackPtr, $commentStart)
    {
        $tokens = $phpcsFile->getTokens();

        foreach ($tokens[$commentStart]['comment_tags'] as $tag) {
            $tagName = strtolower($tokens[$tag]['content']);
            $pattern = null;

            if ($this->patternMatch === true) {
                $count   = 0;
                $pattern = preg_replace(
                    $this->forbiddenCommentTags,
                    $this->forbiddenCommentTags,
                    $tagName,
                    1,
                    $count
                );

                if ($count === 0) {
                    continue;
                }

                // Remove the pattern delimiters and modifier.
                $pattern = substr($pattern, 1, -2);
            } elseif (in_array($tagName, $this->forbiddenCommentTags) === false) {
                continue;
            } // end if

            $this->reportTag($phpcsFile, $stackPtr, $tag, $tokens[$tag]['content'], $pattern);
        } // end foreach
    } // end processTags()

    /**
     * Generates the error or warning for this comment tag.
     *
     * @param  File   $phpcsFile The file being scanned.
     * @param  int    $stackPtr  The stack position where the comment tag was found.
     * @param  int    $tagPtr    The stack position of the comment tag.
     * @param  string $tagName   The name of the forbidden comment tag.
     * @param  string $pattern   The pattern used for the match.
     *
     * @return void
     */
    protected function reportTag(File $phpcsFile, $stackPtr, $tagPtr, $tagName, $pattern = null)
    {
        $tokens = $phpcsFile->getTokens();

        $data = [ $tagName ];

        if ($this->error === true) {
            $code  = self::CODE_TAG_FOUND;
            $error = static::MESSAGE_TAG_FORBIDDEN;
        } else {
            $code  = self::CODE_TAG_DISCOURAGED;
            $error = static::MESSAGE_TAG_DISCOURAGED;
        }

        if ($pattern === null) {
            $pattern = strtolower($tagName);
        }

        if ($this->forbiddenTags[$pattern] !== null) {
            $code  .= self::CODE_TAG_ALTERNATIVE;
            $data[] = $this->forbiddenTags[$pattern];
            $error .= '; use %s instead';
        }

        if ($this->error === true) {
            $phpcsFile->addError($error, $tagPtr, $code, $data);
        } else {
            $phpcsFile->addWarning($error, $tagPtr, $code, $data);
        }
    } // end reportTag()

    /**
     * Prepares the annotations that this test should include.
     *
     * The tag names are normalized and sanitized for use in a regular expression pattern.
     *
     * @return void
     */
    protected function registerAnnotations()
    {
        $parsedTags = [];
        foreach ($this->forbiddenTags as $badTag => $altTag) {
            if ($altTag === 'null' || empty($altTag)) {
                $altTag = null;
            }

            if ($this->patternMatch === true) {
                $badTag = '/'.$badTag.'/i';
            } else {
                $badTag = strtolower($badTag);
            }

            $parsedTags[$badTag] = $altTag;
        }

        $this->forbiddenCommentTags = array_keys($parsedTags);
        $this->forbiddenTags        = $parsedTags;
    } // end registerAnnotations()
} // end class
