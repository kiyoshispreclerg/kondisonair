<?php
$allowedLanguages = [1,4,5,6];
$lang = isset($_SESSION['KondisonairUzatorDiom']) && in_array($_SESSION['KondisonairUzatorDiom'], $allowedLanguages)
    ? $_SESSION['KondisonairUzatorDiom']
    : 1; 
require "lang/{$lang}.php";

function _t($str, $v = null) {
    global $langpack;

    $translated = !empty($langpack[$str]) ? $langpack[$str] : $str;

    if (empty($langpack[$str])) file_put_contents("./lang/".$_SESSION['KondisonairUzatorDiom']."-faltando.txt",'"'.$str.'" => "'.$str.'",'.PHP_EOL,FILE_APPEND);

    if (!is_array($v) || empty($v)) {
        return $translated;
    }

    return preg_replace_callback(
        '/%(\d+|[sd])/',
        function ($matches) use ($v) {
            if (is_numeric($matches[1])) {
                $index = (int)$matches[1] - 1;
                return isset($v[$index]) ? htmlspecialchars($v[$index]) : $matches[0];
            } elseif ($matches[1] === 's') {
                $index = count($v) - count(array_keys($v, null, true));
                return isset($v[$index]) ? htmlspecialchars((string)$v[$index]) : $matches[0];
            } elseif ($matches[1] === 'd') {
                $index = count($v) - count(array_keys($v, null, true));
                return isset($v[$index]) ? (int)$v[$index] : $matches[0];
            }
            return $matches[0];
        },
        $translated
    );
}

?>