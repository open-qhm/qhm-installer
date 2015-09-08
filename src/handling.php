<?php
/**
* POST[mode] によりリクエストハンドリングを行う。
* デフォルト：インストーラートップページ
* install：インストールを行う
* delete：install.php を削除する
*/

function install_qhm()
{
    $env = get_environment();
    if ( ! $env['enable']) {
        response_json(array(
            'error'   => true,
            'message' => 'インストールできません',
        ));
    }
    if (DEVELOPMENT) {
        sleep(3);
        response_json(array(
            'message'  => 'インストール成功（デバッグモード）',
            'redirect' => rtrim($env['path'], '/') . '/index.php',
        ));
    }

    # 最新版の Zip ファイルをダウンロード
    $file_path = get_qhm_archive();

    # 展開
    $dist = '.';
    $extract_path = unzip_qhm($file_path);
    $result = copy_qhm_files($extract_path, $dist);

    # 削除
    unlink($file_path);
    delete_files($extract_path);

    $data = array();
    if ($result) {
        $data['message'] = 'インストールに成功しました';
        $data['redirect'] = rtrim($env['path'], '/') . '/index.php';
    } else {
        $data['error'] = true;
        $data['message'] = 'インストールできませんでした';
    }
    response_json($data);
}

function delete_self()
{
    if ( ! DEVELOPMENT) {
        # 自分自信を削除
        unlink(__FILE__);
    }
    exit;
}

$mode = isset($_POST['mode']) ? $_POST['mode'] : '';

switch ($mode) {
    // インストールを行う
    case 'install':
        install_qhm();
    // インストール後に不要なファイルを削除する
    case 'delete':
        delete_self();
    // インストーラートップページを表示する
    default:
        $data['env'] = get_environment();
}
