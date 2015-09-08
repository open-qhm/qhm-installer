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
        return false;
    }
    # 最新版の Zip ファイルをダウンロード
    $file_path = get_qhm_archive();

    # 展開
    $dist = DEVELOPMENT ? 'test' : '.';
    $extract_path = unzip_qhm($file_path);
    $result = copy_qhm_files($extract_path, $dist);

    # 削除
    unlink($file_path);
    delete_files($extract_path);

    $data = array();
    if ($result) {
        $data['message'] = 'インストールに成功しました';
        $data['redirect'] = '/index.php';
    } else {
        $data['error'] = true;
        $data['message'] = 'インストールできませんでした';
    }
    response_json($data);
}

function delete_self()
{
    if ( ! DEVELOPMENT) {
        unlink(__FILE__);
    }
    # 自分自信を削除
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
