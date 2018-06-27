<?php

namespace Charcoal\CodeSniffer\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Warns about TODO / @todo comments.
 *
 * @todo RFE - When minimum requirement is PHP 7.2, we can take advantage of Use PREG_UNMATCHED_AS_NULL.
 * @todo RFE - Add support for task severity levels.
 * @todo RFE - Add support for task labels.
 * @todo RFE - Add support for task aliases.
 */
abstract class AbstractTaskSniff implements TaskSniff
{
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
     * A list of task types that should be reported with their severity level.
     *
     * A value of 0 will be converted into the default severity level.
     *
     * @var array<string, int>
     */
    public $taskNames = [];

    /**
     * A list of task types with their pretty label.
     *
     * A value of NULL will fallback to the task's name as its label.
     * IE, customize the reported task name.
     *
     * @var array<string, string|null>
     *
     * @todo Add support for task labels.
     */
    public $taskLabels = [];

    /**
     * A list of task types with their aliases.
     *
     * The value is a list of synonyms to look for
     * and report using the key as the task name.
     *
     * @var array<string, array<string>>
     *
     * @todo Add support for task aliases.
     */
    public $taskAliases = [];

    /**
     * A list of tag names to look up with their default task name.
     *
     * The value is NULL if no fallback task name is applicable.
     * IE, the tag will be analysed but ignored if no task is assigned.
     *
     * @var array<string, string|null>
     */
    public $tagNames = [];

    /**
     * The normalized and filtered list of tag names with their default task name.
     *
     * @var array<string, string|null>
     * @see self::registerAnnotations()
     */
    protected $includedTagNames = [];

    /**
     * The normalized and filtered list of tasl names with their severity level.
     *
     * @var array<string, int>
     * @see self::registerAnnotations()
     */
    protected $includedTaskNames = [];

    /**
     * The regex pattern to find @todo doctag.
     *
     * @var string
     * @see self::registerAnnotations()
     */
    protected $regexDocTagPattern;

    /**
     * The regex pattern to find TODO codetag.
     *
     * @var string
     * @see self::registerAnnotations()
     */
    protected $regexCodeTagPattern;

    /**
     * The regex pattern to find TODO comments.
     *
     * @var string
     * @see self::registerAnnotations()
     */
    protected $regexCommentStringPattern;

    /**
     * The regex pattern to loosely find TODO comments.
     *
     * @var string
     * @see self::registerAnnotations()
     */
    protected $regexAnyCommentStringPattern;

    /**
     * The regex pattern to strip TODO comment metadata.
     *
     * @var string
     * @see self::registerAnnotations()
     */
    protected $regexCommentMetadataPattern;

    /**
     * A cache of all registered tasks across all sub-classes, to avoid conflicts.
     *
     * The value is the class that registered the annotation.
     *
     * @var array<string, string>
     * @see self::registerAnnotations()
     */
    protected static $registeredTaskNames = [];

    /**
     * A cache of processed tasks and comments, to avoid duplicate reports.
     *
     * The value is the main token (a comment tag) and the key is the related token.
     *
     * @var array<int, int>
     */
    protected static $processedTokens = [];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        $this->registerAnnotations();

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
        if (empty($this->includedTaskNames)) {
            return;
        }

        $fileName = $phpcsFile->getFilename();

        // Bail early if this token has already been processed
        if (isset(static::$processedTokens[$fileName][$stackPtr]) === true) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        switch ($tokens[$stackPtr]['code']) {
            case T_DOC_COMMENT_TAG:
                $this->processDocComment($phpcsFile, $stackPtr);
                break;

            case T_DOC_COMMENT_STRING:
            case T_COMMENT:
                $this->processComment($phpcsFile, $stackPtr);
                break;
        }
    } // end process()

    /**
     * Process the comment for any tasks.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token
     *                        in the stack passed in $tokens.
     *
     * @return void
     */
    protected function processComment(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $task = $this->parseTaskFromComment($tokens[$stackPtr]['content']);
        if ($task === false) {
            return;
        }

        $fileName = $phpcsFile->getFilename();

        // Mark the comment as processed
        static::$processedTokens[$fileName][$stackPtr] = true;

        $task['line'] = $tokens[$stackPtr]['line'];
        $task['type'] = $tokens[$stackPtr]['code'];

        $this->reportTask($phpcsFile, $stackPtr, $task);
    } // end processComment()

    /**
     * Process the doc comment for any tasks.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token
     *                        in the stack passed in $tokens.
     *
     * @return void
     */
    protected function processDocComment(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        list($tagName, $taskName) = $this->parseCommentTag($tokens[$stackPtr]['content']);

        // Check if its an accepted comment tag (e.g., `@todo`).
        if ($this->isCommentTag($tagName) === false) {
            return;
        }

        // Check if its an accepted comment subtag (e.g., `@todo:hack`).
        if ($taskName !== null && isset(static::$registeredTaskNames[$taskName]) === true) {
            $className = static::$registeredTaskNames[$taskName];
            if (is_a($this, $className) === false) {
                return;
            }
        }

        $task = $this->parseTaskFromDocComment($phpcsFile, $stackPtr);
        if ($task === false) {
            return;
        }

        $fileName = $phpcsFile->getFilename();

        // Mark the comment tag as processed
        static::$processedTokens[$fileName][$stackPtr] = true;

        // Mark the comment tokens as processed
        foreach ($task['comment_tokens'] as $i) {
            static::$processedTokens[$fileName][$i] = $stackPtr;
        }

        $task['line'] = $tokens[$stackPtr]['line'];
        $task['type'] = $tokens[$stackPtr]['code'];

        $this->reportTask($phpcsFile, $stackPtr, $task);
    } // end processDocComment()

    /**
     * Parse this block comment tag for any description.
     *
     * @param File   $phpcsFile The file being scanned.
     * @param int    $tag       The position of the comment tag (i.e., $stackPtr)
     *                          in the token stack passed in $tokens.
     *
     * @return array|bool An associative array, or FALSE if the comment is invalid or malformed.
     */
    protected function parseTaskFromDocComment(File $phpcsFile, $tag)
    {
        $tokens = $phpcsFile->getTokens();

        $commentStart = $phpcsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, ($tag - 1));
        if ($commentStart === false) {
            return false;
        }

        $commentEnd  = $tokens[$commentStart]['comment_closer'];

        $taskName      = null;
        $comment       = '';
        $commentLines  = [];
        $commentTokens = [];
        $commentTags   = $tokens[$commentStart]['comment_tags'];

        // Assemble the task description from subsequent comment tokens.
        $nextPtr = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $tag, $commentEnd);
        if ($nextPtr !== false && $tokens[$nextPtr]['line'] === $tokens[$tag]['line']) {
            $content = $tokens[$nextPtr]['content'];

            if ($content !== '') {
                $comment         = $content;
                $commentTokens[] = $nextPtr;
                $commentLines[]  = [
                    'comment' => $comment,
                    'token'   => $nextPtr,
                    'line'    => $tokens[$nextPtr]['line'],
                    'indent'  => 0,
                ];

                // Fetch the position of this tag amongst siblings of this block.
                $tagPos = array_search($tag, $commentTags, true);

                // Any strings until the next tag belong to this comment.
                if (isset($commentTags[($tagPos + 1)]) === true) {
                    $end = $commentTags[($tagPos + 1)];
                } else {
                    $end = $commentEnd;
                }

                for ($i = ($tag + 3); $i < $end; $i++) {
                    if ($tokens[$i]['code'] !== T_DOC_COMMENT_STRING) {
                        continue;
                    }

                    $indent = 0;
                    if ($tokens[($i - 1)]['code'] === T_DOC_COMMENT_WHITESPACE) {
                        $indent = strlen($tokens[($i - 1)]['content']);
                    }

                    $comment        .= ' '.$tokens[$i]['content'];
                    $commentTokens[] = $i;
                    $commentLines[]  = [
                        'comment' => $tokens[$i]['content'],
                        'token'   => $i,
                        'line'    => $tokens[$i]['line'],
                        'indent'  => $indent,
                    ];
                } // end for
            } // end if
        } // end if

        // If a valid subtag is found, use that as the task name.
        list($tagName, $taskName) = $this->parseCommentTag($tokens[$tag]['content']);

        if ($this->isCodeTag($taskName) === true) {
            $task = [
                'name' => $taskName,
                'body' => $this->parseTaskBody($comment),
            ];
        } else {
            $taskName = null;

            // If a valid task name is found, use that.
            $task = $this->parseTaskFromComment($comment, false);

            // If the comment is just a description, use the comment tag to resolve the task name.
            if ($task === false) {
                // If the comment tag does not have a fallback task name, ignore the tag.
                if (isset($this->includedTagNames[$tagName]) === false) {
                    return false;
                }

                $taskName = $this->includedTagNames[$tagName];

                $task = [
                    'name' => $taskName,
                    'body' => $this->parseTaskBody($comment),
                ];
            } else {
                $taskName = $task['name'];

                // If the extracted task name isn't accepted, ignore the tag.
                if ($this->isCodeTag($taskName) === false) {
                    return false;
                }
            } // end if
        }

        $task['comment_tokens'] = $commentTokens;
        $task['comment_lines']  = $commentLines;

        return $task;
    } // end parseTaskFromDocComment()

    /**
     * Parse the name of the task annotation from the comment tag.
     *
     * @param  string $tagName The comment tag.
     *
     * @return string[] The comment tag parts: `[ <tag>, <task> ]`.
     */
    protected function parseCommentTag($tagName)
    {
        if (substr($tagName, 0, 1) !== '@') {
            return [ null, null ];
        }

        list($tagName, $taskName) = array_pad(explode(':', $tagName, 2), 2, null);

        if ($taskName !== null) {
            $taskName = strtoupper($taskName);
        }

        return [ $tagName, $taskName ];
    } // end parseCommentTag()

    /**
     * Parse this comment for any task and return the task as a dataset.
     *
     * @param string $comment The comment being scanned.
     * @param bool   $strict  If FALSE then function will match any task type. IE,
     *     ignore values defined by {@see self::$taskNames}.
     *
     * @return array|bool An associative array, or FALSE if the comment is invalid or malformed.
     */
    public function parseTaskFromComment($comment, $strict = true)
    {
        if ($strict === true) {
            $pattern = $this->regexCommentStringPattern;
        } else {
            $pattern = $this->regexAnyCommentStringPattern;
        }

        $matches = [];

        preg_match($pattern, $comment, $matches);
        if (empty($matches)) {
            return false;
        }

        $task = [
            'name' => $this->parseTaskName($matches['name']),
            'body' => $this->parseTaskBody($matches['body']),
        ];

        return $task;
    } // end parseTaskFromComment()

    /**
     * Parse the name of the task annotation.
     *
     * @param  string $name The task name (codetag).
     *
     * @return string The sanitized task name.
     */
    protected function parseTaskName($name)
    {
        $name = strtoupper(trim($name));
        return $name;
    } // end parseTaskName()

    /**
     * Parse the descriptor of the task annotation.
     *
     * @param  string $body The task description (everything after the task name).
     *
     * @return string|null The sanitized task description.
     */
    protected function parseTaskBody($body)
    {
        // Clear whitespace and some common characters not required at
        // the end of a task message to make the notice more informative.
        $body = trim($body);
        $body = $this->stripTaskMetadata($body);
        $body = trim($body, '-:[](). ');

        if ($body === '') {
            return null;
        }

        return $body;
    } // end parseTaskBody()

    /**
     * Strip the extraneous characters and metadata from the task annotation.
     *
     * @param  string $body The task description.
     *
     * @return string The sanitized task description.
     */
    protected function stripTaskMetadata($body)
    {
        // Strip metadata for the Google's C++ Style Guide and Python's PEP-350.
        $pattern = $this->regexCommentMetadataPattern;

        return preg_replace($pattern, '$1', $body);
    } // end stripTaskMetadata()

    /**
     * Determine if the current token is a @todo comment tag.
     *
     * @param string $content The input string.
     *
     * @return boolean
     */
    public function isCommentTag($content)
    {
        return array_key_exists($content, $this->includedTagNames);
    } // end isCommentTag()

    /**
     * Determine if the current token is a TODO codetag.
     *
     * @param string $content The input string.
     *
     * @return boolean
     */
    public function isCodeTag($content)
    {
        return array_key_exists($content, $this->includedTaskNames);
    } // end isCodeTag()

    /**
     * Generates the error or warning for this task.
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
            $task['body'],
        ];

        if ($task['body'] === null) {
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
     * Prepares the annotations that this test should include.
     *
     * The tag names are normalized and sanitized for use in a regular expression pattern.
     *
     * @return void
     */
    protected function registerAnnotations()
    {
        // Prepare the task names for processing
        $pregTasks = [];
        foreach ($this->taskNames as $taskName => $severity) {
            $taskName = strtoupper(trim($taskName));

            if (empty($taskName) === true) {
                /** @todo Throw exception if task name is invalid. */
                continue;
            }

            if (isset(static::$registeredTaskNames[$taskName]) === true) {
                /** @todo Throw exception if task name is already registered. */
                continue;
            }

            static::$registeredTaskNames[$taskName] = static::class;

            $severity = is_numeric($severity) ? (int)$severity : 0;
            $this->includedTaskNames[$taskName] = $severity;

            $pregTasks[] = preg_quote($taskName);
        }

        if (empty($pregTasks) === false) {
            $pcreTasks = implode('|', $pregTasks);

            $st = '(?:\A|[^\p{L}]+)'; // Beginning
            $bt = '(?<body>.*)';      // Body Text
            $bp = '(?<body>)';        // Body Placeholder
            $pl = '[^\p{L}]+';        // Non-Letter
            $se = '\s*(?:-+|:)\s*';   // Segmentation

            // Example: <em>TODO</em> - We need to clean this code up.
            $pattern = '/'.$st.'(?<name>'.$pcreTasks.')(?:'.$pl.'|\Z)/ui';
            $this->regexCodeTagPattern = $pattern;

            // Example: <em>TODO - We need to clean this code up.</em>
            $pattern = '/'.$st.'(?<name>'.$pcreTasks.')(?|'.$se.$bt.'|'.$bp.$pl.'|'.$bp.'\Z)/ui';
            $this->regexCommentStringPattern = $pattern;

            // Example: <em>TODO - We need to clean this code up.</em>
            $pattern = '/^(?<name>[A-Z-]+)(?|'.$se.$bt.'|'.$bp.$pl.')$/ui';
            $this->regexAnyCommentStringPattern = $pattern;

            $metadata = '[\[\{\(\<](.*)[\]\}\)\>]';

            // Example: <em>(Zeke)</em> We need to clean this code up. <em><#1234 2018-01-01 00:00:00></em>
            $pattern = '/^'.$metadata.'$|^'.$metadata.'[^\p{L}]*|[^\p{L}]*'.$metadata.'$/';
            $this->regexCommentMetadataPattern = $pattern;
        }

        // Prepare the comment tags for processing
        $pregTags = [];
        foreach ($this->tagNames as $tagName => $taskName) {
            $tagName = trim($tagName);
            if (substr($tagName, 0, 1) !== '@') {
                $tagName = '@'.$tagName;
            }

            $taskName = strtoupper(trim($taskName));
            if (empty($taskName)) {
                $taskName = null;
            }

            $this->includedTagNames[$tagName] = $taskName;

            $pregTags[] = preg_quote(ltrim($tagName, '@'));
        }

        if (empty($pregTags) === false) {
            $pcreTags = implode('|', $pregTags);
            if (empty($pregTasks) === false) {
                // Example: <em>@todo:yagni</em> We need to clean this code up.
                $pattern = '/^@(?|(?<name>'.$pcreTags.')|'.$pcreTags.':(?<name>'.$pcreTasks.'))$/i';
            } else {
                // Example: <em>@todo</em> We need to clean this code up.
                $pattern = '/^@(?<name>'.$pcreTags.')$/i';
            }

            $this->regexDocTagPattern = $pattern;
        }
    } // end registerAnnotations()
} // end class
