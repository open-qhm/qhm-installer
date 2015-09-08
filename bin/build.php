#!/usr/bin/env php
<?php
/**
* index.php の require と include を展開し、 install.php として保存する。
*/

function expand_php($path)
{
    $lines = explode("\n", file_get_contents($path));
    // php 開きタグを削除
    array_shift($lines);
    return join("\n", $lines);
}

function expand_html($path)
{
    $lines = explode("\n", file_get_contents($path));
    $built = array();
    foreach ($lines as $line) {
        if (preg_match('/<link rel="stylesheet" href="(css\/.+)"/', $line, $mts)) {
            $css_path = $mts[1];
            $built[] = expand_css($css_path);
        } elseif (preg_match('/<script src="(js\/.+)"/', $line, $mts)) {
            $js_path = $mts[1];
            $built[] = expand_js($js_path);
        } else {
            $built[] = $line;
        }
    }
    return join("\n", $built);
}

function expand_css($path)
{
    $css = file_get_contents($path);
    return "<style>\n" . $css . "\n</style>";
}

function expand_js($path)
{
    $js = file_get_contents($path);
    return "<script>\n" . $js . "\n</script>";
}

$main_file = 'index.php';
$processes = explode("\n", file_get_contents($main_file));

$built = array();

foreach ($processes as $process)
{
    if (preg_match('/^require \'(.+)\';/', $process, $mts)) {
        // PHP 読み込み
        $require_path = $mts[1];
        $built[] = '// require ' . $require_path;
        $built[] = expand_php($require_path);
    } elseif (preg_match('/^include \'(.+)\';/', $process, $mts)) {
        // ビューファイル読み込み
        $include_path = $mts[1];
        $built[] = '// include ' . $include_path;
        $built[] = '?' . '>'; // PHPタグを閉じる
        $built[] = expand_html($include_path);
        $built[] = '<' . '?php'; // PHPタグを開く
    } elseif (preg_match('/^define\(\'DEVELOPMENT\'/', $process)) {
        // 開発モードをオフにする
        $built[] = "define('DEVELOPMENT', false);";
    } else {
        // そのまま追加
        $built[] = $process;
    }
}

file_put_contents('install.php', join("\n", $built));
echo shell_exec('php -l install.php');
