<?php
function copy_qhm_files($source, $dist)
{
    $source = rtrim($source, '/');
    $dist   = rtrim($dist, '/');
    // $dist が存在し、かつフォルダでない場合エラー
    if (file_exists($dist) && ! is_dir($dist)) {
        return false;
    }

    if ( ! file_exists($dist)) {
        mkdir($dist);
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $base_path = '';
    $index = 0;

    foreach ($files as $file)
    {
        // 最初のパス（qhm-master）は基準として使う
        if ($base_path === '') {
            $base_path = $file->getPathname() . '/';
            $index = strlen($base_path);
        } else {
            $file_path = $file->getPathname();
            $relative_path = substr($file_path, $index);
            // .htaccess は除外する
            $exclude_ptn = '/
                \A\.htaccess\z
            /x';
            if (preg_match($exclude_ptn, $relative_path)) {
                continue;
            }

            if ($file->isDir()) {
                mkdir($dist . '/' . $relative_path);
            } else {
                copy($file_path, $dist . '/' . $relative_path);
            }
        }
    }

    return true;
}

function delete_files($dir)
{
    if ( ! file_exists($dir)) return;

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file)
    {
        if ($file->isDir() === true)
        {
            rmdir($file->getPathname());
        }
        else
        {
            unlink($file->getPathname());
        }
    }

    rmdir($dir);
}

function get_environment()
{
    static $env;
    if (isset($env)) return $env;

    $env['writable']        = is_writable(INSTALL_DIR);
    $env['allow_url_fopen'] = ini_get('allow_url_fopen');
    $env['unzip_strategy']  = (unzip_strategy() !== '');

    $env['enable'] = $env['writable']
                  && $env['allow_url_fopen']
                  && $env['unzip_strategy'];
    return $env;
}

function get_qhm_archive()
{
    $tmpfile = tempnam(INSTALL_DIR, 'install-');
    file_put_contents($tmpfile, fopen(ARCHIVE_URL, 'r'));
    return $tmpfile;
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}



function response_json($data)
{
    $json = json_encode($data);
    header('HTTP/1.1 200 OK');
    header('Content-Type: application/json');
    header('Content-Length: ' . strlen($json));
    echo $json;
    exit;
}

/**
* Zip ファイルを展開し、展開先のパスを返す
* @param string $archive_path Zip ファイルのパス
* @return string 展開先のパス
*/
function unzip_qhm($archive_path)
{
    if ( ! file_exists($archive_path)) return false;
    $extract_to = "{$archive_path}_extract";
    if (file_exists($extract_to)) return false;

    $strategy = unzip_strategy();
    if ($strategy === 'ZipArchive') {
        $zip = new ZipArchive;
        $res = $zip->open($archive_path);
        if ($res === TRUE) {
            $zip->extractTo($extract_to);
            $zip->close();
        }
    } else if ($strategy === 'shell') {
        shell_exec("unzip {$$archive_path} -d {$extract_to}");
    }
    return $extract_to;
}

function unzip_strategy()
{
    if (class_exists('ZipArchive')) {
        return 'ZipArchive';
    } else if (shell_exec('which unzip')) {
        return 'shell';
    }
    return '';
}
