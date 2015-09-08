<?php
/**
* QHM インストーラー
*/
define('INSTALL_DIR', dirname(__FILE__));
define('ARCHIVE_URL', 'https://github.com/open-qhm/qhm/archive/master.zip');
define('DEVELOPMENT', false);

// require src/func.php
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


$data = array(
    'location' => $_SERVER['SCRIPT_NAME'],
);

// require src/handling.php
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



extract($data);
$data_json = json_encode($data);
// include view/template.html
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>インストーラー - haik: QHM v6</title>

    <!-- Bootstrap Core CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">

<style>
/*!
 * Start Bootstrap - Creative Bootstrap Theme (http://startbootstrap.com)
 * Code licensed under the Apache License v2.0.
 * For details, see http://www.apache.org/licenses/LICENSE-2.0.
 */

html,
body {
    width: 100%;
    height: 100%;
}

body {
    font-family: "Open Sans","ヒラギノ角ゴ ProN","Hiragino Kaku Gothic ProN","メイリオ","Meiryo","MS ゴシック","MS Gothic","MS Pゴシック","MS PGothic",sans-serif;
}

hr {
    max-width: 50px;
    border-color: #f05f40;
    border-width: 3px;
}

hr.light {
    border-color: #fff;
}

a {
    color: #f05f40;
    -webkit-transition: all .35s;
    -moz-transition: all .35s;
    transition: all .35s;
}

a:hover,
a:focus {
    color: #eb3812;
}

h1,
h2,
h3,
h4,
h5,
h6 {
    /*font-family: 'Open Sans','Helvetica Neue',Arial,sans-serif;*/
}

p {
    margin-bottom: 20px;
    font-size: 16px;
    line-height: 1.5;
}

.bg-primary {
    background-color: #f05f40;
}

.bg-dark {
    color: #fff;
    background-color: #222;
}

.text-faded {
    color: rgba(255,255,255,.7);
}

section {
    padding: 100px 0;
}

aside {
    padding: 50px 0;
}

.no-padding {
    padding: 0;
}

.navbar-default {
    border-color: rgba(34,34,34,.05);
    /*font-family: 'Open Sans','Helvetica Neue',Arial,sans-serif;*/
    background-color: #fff;
    -webkit-transition: all .35s;
    -moz-transition: all .35s;
    transition: all .35s;
}

.navbar-default .navbar-header .navbar-brand {
    /*font-family: 'Open Sans','Helvetica Neue',Arial,sans-serif;*/
    font-weight: 700;
    color: #f05f40;
}

.navbar-default .navbar-header .navbar-brand:hover,
.navbar-default .navbar-header .navbar-brand:focus {
    color: #eb3812;
}

.navbar-default .nav > li>a,
.navbar-default .nav>li>a:focus {
    font-size: 13px;
    font-weight: 700;
    color: #222;
}

.navbar-default .nav > li>a:hover,
.navbar-default .nav>li>a:focus:hover {
    color: #f05f40;
}

.navbar-default .nav > li.active>a,
.navbar-default .nav>li.active>a:focus {
    color: #f05f40!important;
    background-color: transparent;
}

.navbar-default .nav > li.active>a:hover,
.navbar-default .nav>li.active>a:focus:hover {
    background-color: transparent;
}

@media(min-width:768px) {
    .navbar-default {
        border-color: rgba(255,255,255,.3);
        background-color: transparent;
    }

    .navbar-default .navbar-header .navbar-brand {
        color: rgba(255,255,255,.7);
    }

    .navbar-default .navbar-header .navbar-brand:hover,
    .navbar-default .navbar-header .navbar-brand:focus {
        color: #fff;
    }

    .navbar-default .nav > li>a,
    .navbar-default .nav>li>a:focus {
        color: rgba(255,255,255,.7);
    }

    .navbar-default .nav > li>a:hover,
    .navbar-default .nav>li>a:focus:hover {
        color: #fff;
    }

    .navbar-default.affix {
        border-color: rgba(34,34,34,.05);
        background-color: #fff;
    }

    .navbar-default.affix .navbar-header .navbar-brand {
        font-size: 14px;
        color: #f05f40;
    }

    .navbar-default.affix .navbar-header .navbar-brand:hover,
    .navbar-default.affix .navbar-header .navbar-brand:focus {
        color: #eb3812;
    }

    .navbar-default.affix .nav > li>a,
    .navbar-default.affix .nav>li>a:focus {
        color: #222;
    }

    .navbar-default.affix .nav > li>a:hover,
    .navbar-default.affix .nav>li>a:focus:hover {
        color: #f05f40;
    }
}

header {
    position: relative;
    width: 100%;
    min-height: 100%;
    text-align: center;
    color: #fff;
    background-color: #666;
    background-position: center;
    -webkit-background-size: cover;
    -moz-background-size: cover;
    background-size: cover;
    -o-background-size: cover;
}

header .header-content {
    position: relative;
    width: 100%;
    padding: 100px 15px;
    text-align: center;
}

header .header-content .header-content-inner h1 {
    margin-top: 0;
    margin-bottom: 0;
    font-weight: 700;
}

header .header-content .header-content-inner hr {
    margin: 30px auto;
}

header .header-content .header-content-inner p {
    margin-bottom: 50px;
    font-size: 16px;
    font-weight: 300;
    color: rgba(255,255,255,.7);
}

@media(min-width:768px) {
    header .header-content {
        position: absolute;
        top: 50%;
        padding: 0 50px;
        -webkit-transform: translateY(-50%);
        -ms-transform: translateY(-50%);
        transform: translateY(-50%);
    }

    header .header-content .header-content-inner {
        margin-right: auto;
        margin-left: auto;
        max-width: 1000px;
    }

    header .header-content .header-content-inner p {
        margin-right: auto;
        margin-left: auto;
        max-width: 80%;
        font-size: 18px;
    }

    header .alert {
      text-align: left;
      width: 50%;
      margin: 15px auto 30px;
      line-height: 1.8em;
    }
}

.btn-default {
    border-color: #fff;
    color: #222;
    background-color: #fff;
    -webkit-transition: all .35s;
    -moz-transition: all .35s;
    transition: all .35s;
}

.btn-default:hover,
.btn-default:focus,
.btn-default.focus,
.btn-default:active,
.btn-default.active,
.open > .dropdown-toggle.btn-default {
    border-color: #ededed;
    color: #222;
    background-color: #f2f2f2;
}

.btn-default:active,
.btn-default.active,
.open > .dropdown-toggle.btn-default {
    background-image: none;
}

.btn-default.disabled,
.btn-default[disabled],
fieldset[disabled] .btn-default,
.btn-default.disabled:hover,
.btn-default[disabled]:hover,
fieldset[disabled] .btn-default:hover,
.btn-default.disabled:focus,
.btn-default[disabled]:focus,
fieldset[disabled] .btn-default:focus,
.btn-default.disabled.focus,
.btn-default[disabled].focus,
fieldset[disabled] .btn-default.focus,
.btn-default.disabled:active,
.btn-default[disabled]:active,
fieldset[disabled] .btn-default:active,
.btn-default.disabled.active,
.btn-default[disabled].active,
fieldset[disabled] .btn-default.active {
    border-color: #fff;
    background-color: #fff;
}

.btn-default .badge {
    color: #fff;
    background-color: #222;
}

.btn-primary {
    border-color: #f05f40;
    color: #fff;
    background-color: #f05f40;
    -webkit-transition: all .35s;
    -moz-transition: all .35s;
    transition: all .35s;
}

.btn-primary:hover,
.btn-primary:focus,
.btn-primary.focus,
.btn-primary:active,
.btn-primary.active,
.open > .dropdown-toggle.btn-primary {
    border-color: #ed431f;
    color: #fff;
    background-color: #ee4b28;
}

.btn-primary:active,
.btn-primary.active,
.open > .dropdown-toggle.btn-primary {
    background-image: none;
}

.btn-primary.disabled,
.btn-primary[disabled],
fieldset[disabled] .btn-primary,
.btn-primary.disabled:hover,
.btn-primary[disabled]:hover,
fieldset[disabled] .btn-primary:hover,
.btn-primary.disabled:focus,
.btn-primary[disabled]:focus,
fieldset[disabled] .btn-primary:focus,
.btn-primary.disabled.focus,
.btn-primary[disabled].focus,
fieldset[disabled] .btn-primary.focus,
.btn-primary.disabled:active,
.btn-primary[disabled]:active,
fieldset[disabled] .btn-primary:active,
.btn-primary.disabled.active,
.btn-primary[disabled].active,
fieldset[disabled] .btn-primary.active {
    border-color: #f05f40;
    background-color: #f05f40;
}

.btn-primary .badge {
    color: #f05f40;
    background-color: #fff;
}

.btn {
    border: 0;
    border-radius: 300px;
    font-family: 'Open Sans','Helvetica Neue',Arial,sans-serif;
    font-weight: 700;
}

.btn-xl {
    padding: 15px 30px;
}

::-moz-selection {
    text-shadow: none;
    color: #fff;
    background: #222;
}

::selection {
    text-shadow: none;
    color: #fff;
    background: #222;
}

img::selection {
    color: #fff;
    background: 0 0;
}

img::-moz-selection {
    color: #fff;
    background: 0 0;
}

body {
    webkit-tap-highlight-color: #222;
}

</style>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body id="page-top">

    <nav id="mainNav" class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="<?php echo h($location)?>">haik: QHM v6</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="http://www.open-qhm.net/" target="_blank">Tutorial</a>
                    </li>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container-fluid -->
    </nav>

    <header>
        <div class="header-content">
            <div class="header-content-inner">
                <h1>haik: QHM v6<span class="visible-xs-inline"><br></span> インストーラー</h1>
                <hr>
                <p>
                  ここに haik: QHM v6 をインストールします。
                </p>
                <?php if ($env['enable']): ?>
                  <button type="button" id="install" class="btn btn-primary btn-xl">インストールする</button>
                <?php else: ?>
                  <div class="alert alert-danger">
                    <?php if ( ! $env['writable']): ?>
                      <i class="fa fa-exclamation"></i> <code><?php echo h(INSTALL_DIR) ?></code>
                      に書き込みができません。<br>
                    <?php endif?>
                    <?php if ( ! $env['allow_url_fopen']): ?>
                      <i class="fa fa-exclamation"></i> PHP設定 <code>allow_url_fopen</code>
                      を有効にしてください。<br>
                    <?php endif ?>
                    <?php if ( ! $env['unzip_strategy']): ?>
                      <i class="fa fa-exclamation"></i> Zip ファイルの解凍ができません。
                    <?php endif ?>
                  </div>
                  <button type="button" class="btn btn-default btn-xl disabled">インストールできません</button>
                <?php endif ?>
            </div>
        </div>
    </header>

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

    <script>
    InstallerData = <?php echo $data_json?>;
    </script>
<script>
(function($){
  'use strict';

  function completeInstall(res, cb) {
    alert(res.message);
    setTimeout(cb, 1000);
  }

  $("#install").on("click", function(){
    $.post(InstallerData.location, {mode: "install"}, function(res){
      if ( ! res.error) {
        $.post(InstallerData.location, {mode: "delete"});
        completeInstall(res, function(){
          //location.href = res.redirect
        });
      } else {
        alert(res.message);
      }
    });
  });
})(jQuery);

</script>
</body>

</html>

<?php
