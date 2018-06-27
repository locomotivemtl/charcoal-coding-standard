<?php

namespace Charcoal\CodeSniffer\Tests\Commenting;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Unit test class for the Todo sniff.
 */
class TodoUnitTest extends AbstractSniffUnitTest
{
    /**
     * Returns the lines where errors should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of errors that should occur on that line.
     *
     * @param string $testFile The name of the file being tested.
     *
     * @return array<int, int>
     */
    public function getErrorList($testFile = 'TodoUnitTest.inc')
    {
        return [];
    } // end getErrorList()

    /**
     * Returns the lines where warnings should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of warnings that should occur on that line.
     *
     * @param string $testFile The name of the file being tested.
     *
     * @return array<int, int>
     */
    public function getWarningList($testFile = 'TodoUnitTest.inc')
    {
        return [
            3  => 1,
            4  => 1,
            7  => 1,
            10 => 1,
            13 => 1,
            16 => 1,
            18 => 1,
            21 => 1,
        ];
    } // end getWarningList()
} // end class
