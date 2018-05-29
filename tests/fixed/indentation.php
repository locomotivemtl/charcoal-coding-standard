<?php

/**
 * Test Indentation
 */
function foo($x)
{
    if ($x > 5) {
        echo 'bar';
    }

    switch ($x) {
        case 1:
            echo 'foo';
            break;

        case 2:
            echo 'adf';
            break;
    }

    return 'string';
}
