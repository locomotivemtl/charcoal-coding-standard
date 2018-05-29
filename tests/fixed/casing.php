<?php

define('foo_bar', 1);

if (true) {
    echo 'foo';
} elseif (false) {
    echo 'bar';
} elseif ($x !== null) {
    echo 'baz';
}
