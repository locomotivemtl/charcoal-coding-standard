<?php

/**
 * Test Spaces
 */
class Foo
{
    /**
     * @param  integer $x    Number X.
     * @param  integer $z    Number Z.
     * @param  integer $y    Number Y.
     * @param  array   $cell Cell structure.
     * @param  integer $num  Extra number.
     * @param  string  $a    Key A.
     * @param  array   $val  Value structure.
     * @return void
     */
    function baz($x, $z, $y, $cell, $num, $a, $val)
    {
        global $k, $s1;

        $arr = [ 0 => 'zero', 1 => 'one' ];

        for ($i = 0; $i < $x; $i++) {
            $y += (($y ^ 0x123) << 2);
        }

        $fn1 = function ($a, $b) use ($x, $z) {
        };
        $fn2 = function ($a, $b) use ($x, $z) {
            echo 'Sed quis leo ut erat gravida consequat. '.'Proin tempor condimentum bibendum. '.
                 'Suspendisse imperdiet massa et lorem efficitur sodales. Nam viverra enim et bibendum tempor. '.
                 'Aliquam ex massa, dictum eget maximus ut, convallis at massa.';
        };

        $k = $x > 15 ? 1 : 2;
        $k = $x ?: 0;

        do {
            try {
                if (!0 > $x && !$x < 10) {
                    while ($x != $y) {
                        $x = f($x * 3 + 5);
                    }
                    $z += 2;
                } elseif ($x > 20) {
                    $z = ($x << 1);
                } else {
                    $z = ($x | 2);
                }

                $j = (int)$z;
                switch ($j) {
                    case 0:
                        $s1 = 'zero';
                        break;
                    case 2:
                        $s1 = 'two';
                        break;
                    default:
                        $s1 = 'other';
                }
            } catch (exception $e) {
                echo $val[foo.$num][$cell{$a}];
            } finally {
                // do something
            }
        } while ($x < 0);
    }
}

?>
<div><?= foo(); ?></div>
