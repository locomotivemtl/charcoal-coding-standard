<?php

namespace Charcoal\Sniffs\Commenting;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Warns about TODO / @todo comments.
 *
 * Based on {@link https://www.python.org/dev/peps/pep-0350/ PEP-350}.
 */
abstract class AbstractTaskSniff implements Sniff
{
    const CODE_COMMENT_FOUND = 'CommentFound';
    const CODE_TAG_FOUND     = 'TagFound';
    const CODE_TASK_FOUND    = 'TaskFound';

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
     * A list of todo types that should be noticed.
     *
     * @var array<int,string>
     */
    public $annotationNames = [];

    /**
     * The normalized and filtered list of todo types.
     *
     * @var array<int,string>
     * @see self::registerAnnotations()
     */
    protected $includedAnnotationNames = [];

    /**
     * A cache of processed annotations, to avoid duplicates.
     *
     * @var array<string,mixed>
     */
    protected $parsedAnnotations = [];

    /**
     * The regex pattern to find @todo doctag.
     *
     * @var string
     * @see self::registerRegexPatterns()
     */
    protected $regexDocTagPattern;

    /**
     * The regex pattern to find TODO codetag.
     *
     * @var string
     * @see self::registerRegexPatterns()
     */
    protected $regexCodeTagPattern;

    /**
     * The regex pattern to find TODO comments.
     *
     * @var string
     * @see self::registerRegexPatterns()
     */
    protected $regexCommentStringPattern;

    /**
     * The regex pattern to strip TODO comment metadata.
     *
     * @var string
     * @see self::registerRegexPatterns()
     */
    protected $regexCommentMetadataPattern;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    final public function register()
    {
        $this->registerAnnotations();
        $this->registerRegexPatterns();

        return array_diff(Tokens::$commentTokens, Tokens::$phpcsCommentTokens);
    } // end register()

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
        if (empty($this->includedAnnotationNames)) {
            return;
        }

        $tokens  = $phpcsFile->getTokens();
        $content = $tokens[$stackPtr]['content'];

        $task = $this->parseTask($content);

        if ($task === false) {
            return;
        }

        $this->reportTask($phpcsFile, $stackPtr, $task);
    } // end process()

    /**
     * Generates the error or warning for this sniff.
     *
     * @param  File  $phpcsFile The file being scanned.
     * @param  int   $stackPtr  The stack position where the todo was found.
     * @param  array $task      The recorded task.
     *
     * @return void
     */
    protected function reportTask(File $phpcsFile, $stackPtr, array $task)
    {
        $data = [
            $task['name'],
            $task['desc'],
        ];

        if ($task['desc'] === null) {
            if ($task['type'] === T_DOC_COMMENT_TAG) {
                $code  = self::CODE_TAG_FOUND;
                $error = 'Tag refers to a %s task';
            } else {
                $code  = self::CODE_COMMENT_FOUND;
                $error = 'Comment refers to a %s task';
            }
        } else {
            $code  = self::CODE_TASK_FOUND;
            $error = '%s: %s';
        }

        if ($this->error === true) {
            $phpcsFile->addError($error, $stackPtr, $code, $data);
        } else {
            $phpcsFile->addWarning($error, $stackPtr, $code, $data);
        }
    } // end reportTask()

    /**
     * Parse this comment for any task and return the task as a dataset.
     *
     * @param  string $comment The comment to parse.
     *
     * @return array|bool An associative array, or FALSE if the comment is invalid or malformed.
     */
    protected function parseTask($comment)
    {
        $pattern = $this->regexCommentStringPattern;
        $matches = [];

        /** @todo PHP 7.2 - Use PREG_UNMATCHED_AS_NULL */
        preg_match($pattern, $comment, $matches);
        if (empty($matches)) {
            return false;
        }

        $task = [
            'type' => empty($matches['tag']) ? T_DOC_COMMENT_STRING : T_DOC_COMMENT_TAG,
            'name' => $this->parseTaskName($matches['name']),
            'desc' => $this->parseTaskData($matches['data']),
        ];

        return $task;
    } // end parseTask()

    /**
     * Parse the name of the task annotation.
     *
     * @param  string $name The task name (codetag).
     *
     * @return string The sanitized task name.
     */
    protected function parseTaskName($name)
    {
        $name = strtoupper($name);
        return $name;
    } // end parseTaskName()

    /**
     * Parse the descriptor of the task annotation.
     *
     * @param  string $data The task description (everything after the task name).
     *
     * @return string|null The sanitized task description.
     */
    protected function parseTaskData($data)
    {
        // Clear whitespace and some common characters not required at
        // the end of a task message to make the notice more informative.
        $data = trim($data);
        $data = $this->stripTaskMetadata($data);
        $data = trim($data, '-:. ');

        if ($data === '') {
            return null;
        }

        return $data;
    } // end parseTaskData()

    /**
     * Strip the extraneous characters and metadata from the task annotation.
     *
     * @param  string $data The task description.
     *
     * @return string The sanitized task description.
     */
    protected function stripTaskMetadata($data)
    {
        // Strip metadata for the Google's C++ Style Guide and Python's PEP-350.
        $pattern = $this->regexCommentMetadataPattern;

        return preg_replace($pattern, '$1', $data);
    } // end stripTaskMetadata()

    /**
     * Prepares the annotations that this test should include.
     *
     * The tag names are normalized and sanitized for use in a regular expression pattern.
     *
     * @return void
     */
    protected function registerAnnotations()
    {
        $tagNames = array_filter(array_map('trim', $this->annotationNames));

        foreach ($tagNames as $tagName) {
            $tagName = preg_quote(strtoupper($tagName));
            $this->includedAnnotationNames[] = $tagName;
        }

        if (empty($this->includedAnnotationNames) === false) {
            $this->registerRegexPatterns();
        }
    } // end registerAnnotations()

    /**
     * Prepares the regular expression pattern that this test should use.
     *
     * @return void
     */
    protected function registerRegexPatterns()
    {
        $search  = implode('|', $this->includedAnnotationNames);

        // Example: <em>@todo</em> We need to clean this code up.
        $pattern = '/^@(?<name>'.$search.')$/i';
        $this->regexDocTagPattern = $pattern;

        // Example: <em>TODO</em> - We need to clean this code up.
        // Example: <em>@todo</em> - We need to clean this code up.
        $pattern = '/(?:\A|[^\p{L}]+)(?<tag>@)?(?<name>'.$search.')(?:[^\p{L}]+|\Z)/ui';
        $this->regexCodeTagPattern = $pattern;

        // Example: <em>TODO - We need to clean this code up.</em>
        // Example: <em>@todo We need to clean this code up.</em>
        $pattern = '/(?:\A|[^\p{L}]+)(?<tag>@)?(?<name>'.$search.')(?<data>[^\p{L}]+(?:.*)|\Z)/ui';
        $this->regexCommentStringPattern = $pattern;

        $search  = '[\[\{\(\<](.*)[\]\}\)\>]';

        // Example: <em>(Zeke)</em> We need to clean this code up. <em><#1234 2018-01-01 00:00:00></em>
        $pattern = '/^'.$search.'$|^'.$search.'[^\p{L}]*|[^\p{L}]*'.$search.'$/';
        $this->regexCommentMetadataPattern = $pattern;
    } // end registerRegexPatterns()
} // end class
