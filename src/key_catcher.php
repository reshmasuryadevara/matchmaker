<?php
namespace matchmaker;

require_once('catcher.php');

/**
 * Returns matcher closure by $pattern
 *
 * @param array $pattern
 *
 * @return \Closure
 *
 * @see https://github.com/ptrofimov/matchmaker - ultra-fresh PHP matching functions
 * @author Petr Trofimov <petrofimov@yandex.ru>
 */
function key_catcher(array $pattern, $context)
{
    $keys = [];
    foreach ($pattern as $k => $v) {
        $chars = ['?' => [0, 1], '*' => [0, PHP_INT_MAX], '!' => [1, 1]];
        if (isset($chars[$last = substr($k, -1)])) {
            $keys[$k = substr($k, 0, -1)] = $chars[$last];
        } elseif ($last == '}') {
            list($k, $range) = explode('{', $k);
            $range = explode(',', rtrim($range, '}'));
            $keys[$k] = count($range) == 1
                ? [$range[0], $range[0]]
                : [$range[0] === '' ? 0 : $range[0], $range[1] === '' ? PHP_INT_MAX : $range[1]];
        } else {
            $keys[$k] = $chars[$k[0] == ':' ? '*' : '!'];
        }
        array_push($keys[$k], $v, 0);
    }

    return function ($key = null, $value = null) use (&$keys, $context) {
        if (is_null($key)) foreach ($keys as $count) {
            if ($count[3] < $count[0] || $count[3] > $count[1]) return false;
        } else foreach ($keys as $k => &$count) if (catcher($key, $k)) {
            if (!catches($value, $count[2], $context . '.' . $key)) return false;
            $count[3]++;
        }
        return true;
    };
}
