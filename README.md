# QHM インストーラー

haik (QHM v6) を簡単にインストールできます。


## インストーラーのビルド

下記スクリプトを実行し、 `install.php` を生成してください。

```base
$ bin/build.php
# => built/install.php が生成される
```

## QHMのインストール手順

1. `install.php` をサーバーの公開フォルダへ設置
2. ブラウザで、設置した `install.php` へアクセス
例） `http://example.jp/install.php`
3. 画面の支持に従い、インストールを行う
4. QHMの初期画面が表示されれば成功


## 開発

開発時は `index.php` を使う。

`bin/build.php` を実行することで `require` と `include` 先を展開し、
一つのPHPスクリプト `install.php` として `built` ディレクトリへ保存される。

1. 何らかの改良
2. `$ bin/build.php`
3. `built/install.php` が生成される
4. テスト


### ビルドスクリプトについて

`index.php` 内の `require` と `include` を展開する。
`include` した HTML ファイル内で読み込んだ CSS と JS も展開する。
下記の約束事を順守すること。

- インデントしない
- PHPファイルを読み込む
- 括弧は付けない
- パスは引用符で囲む
- セミコロンの前に空白を付けない
- 入れ子にしない
- require は PHP ファイルのみ
- include は HTML ファイルのみ
- CSS は css フォルダのみ
- JS は js フォルダのみ


```php
// PHP ファイルは require で読み込む
require 'path/to/script.php';

// HTML ファイルは include で読み込む
include 'path/to/view.html';
```

```html
<!-- CSS は css フォルダ内のファイルのみ -->
<link rel="stylesheet" href="css/style.css">

<!-- JavaScript は js フォルダ内のファイルのみ -->
<script src="js/script.js"></script>
```
