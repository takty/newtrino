# Newtrino開発者ガイド
Takuto Yanagida

- [Newtrino開発者ガイド](#newtrino開発者ガイド)
	- [はじめに](#はじめに)
	- [1. Newtrinoとは何か](#1-newtrinoとは何か)
		- [1.1. CMSとしての特徴](#11-cmsとしての特徴)
			- [■ フラット・ファイル](#-フラットファイル)
			- [■ ウェブサイトの一部](#-ウェブサイトの一部)
			- [■ ポータブル](#-ポータブル)
		- [1.2. 動作要件](#12-動作要件)
		- [1.3. サンプルを動かす](#13-サンプルを動かす)
		- [1.4. アカウントの追加](#14-アカウントの追加)
	- [2. Newtrinoの構成](#2-newtrinoの構成)
		- [2.1. PHP版とJS版の違い](#21-php版とjs版の違い)
		- [2.2. システムファイル](#22-システムファイル)
		- [2.3. ウェブサイトによって異なるファイル](#23-ウェブサイトによって異なるファイル)
		- [2.4. アップデートの方法](#24-アップデートの方法)
	- [3. ウェブサイトに組み込む](#3-ウェブサイトに組み込む)
		- [3.1. フロント・ページに新着記事を表示](#31-フロントページに新着記事を表示)
		- [3.2. 一覧ページに記事の一覧を表示](#32-一覧ページに記事の一覧を表示)
		- [3.3. 個別ページに記事の内容を表示](#33-個別ページに記事の内容を表示)
	- [4. カスタマイズする](#4-カスタマイズする)
		- [4.1. タクソノミーの定義](#41-タクソノミーの定義)
			- [`data/taxonomy.json`](#datataxonomyjson)
		- [4.2. 投稿タイプの定義](#42-投稿タイプの定義)
			- [`data/type.json`](#datatypejson)
			- [`media_image`](#media_image)
		- [4.3. コンフィグファイル](#43-コンフィグファイル)
			- [`data/config.json`](#dataconfigjson)
			- [`data/config.php`](#dataconfigphp)
		- [4.4. 管理画面のカスタマイズ](#44-管理画面のカスタマイズ)
		- [4.5. 多言語化](#45-多言語化)
	- [5. クエリの書き方](#5-クエリの書き方)
		- [5.1. クエリの種類](#51-クエリの種類)
			- [直接指定](#直接指定)
			- [範囲指定](#範囲指定)
		- [5.2. 複合クエリ](#52-複合クエリ)
	- [6. APIリファレンス](#6-apiリファレンス)
		- [6.1. PHP版API](#61-php版api)
		- [6.2. JS版API](#62-js版api)
		- [6.3. Mustacheの使い方](#63-mustacheの使い方)
			- [Variables](#variables)
			- [Section](#section)
			- [Invert Sections](#invert-sections)


## はじめに

Newtrinoはポータブルな組み込み用コンテンツ・マネジメント・システム（CMS）です。本ドキュメントでは、その特徴と仕組み、組み込み方について説明します。

## 1. Newtrinoとは何か

### 1.1. CMSとしての特徴

Newtrinoはフラット・ファイルを用いたライブラリとして利用可能な、極めてシンプルなコンテンツ・マネジメント・システム（CMS）です。

CMSを用いたウェブサイトでよく更新されるのはニュース（News）記事であり、また、ライブラリとして、他のウェブサイトに容易に組み込む（入り込む）ことができる点が素粒子（ニュートリノ）のようであるという比喩から、名付けられました。

Newtrinoの特徴は大きく次の三点です。

#### ■ フラット・ファイル

Newtrinoはデータの保存にフラット・ファイルを使っています。フラット・ファイルとは、単なるテキスト・ファイルを指します。言い換えると、データベース（DB）を使っていないということになります。

データの保存にテキスト・ファイルを使う利点の一つ目は、DBをインストールする必要がないという点です。二つ目は、ファイルなので、コピーやバックアップが普通のファイル操作で行えるという点です。

Newtrinoでは記事データ以外にも、ポストタイプやタクソノミーの情報もテキスト・ファイルの一種、JSON形式で保存しています。従って、普段使っているテキスト・エディターを使って、テキストを変更するだけで設定を変えることができます。

#### ■ ウェブサイトの一部

広く使われている CMSの一種、WordPressは、通常、ウェブサイトの全体を作るために使われます。基本的に、あるウェブサイトをWordPressで作るなら、そのすべてのページや画像はWordPressの管理下に置かれることになります。

一方、Newtrinoはウェブサイトの一部として、記事の表示・編集機能を提供します。記事の一覧ページと各記事ページ以外は、通常、Newtrinoとは別に管理されます。単純なHTMLでも構いませんし、何らかの静的ジェネレーターを使うことも可能です。この点が、WordPressのなどのフル機能を提供するCMSとの違いです。

Newtrinoが提供するのは、いくつかのPHPの関数、もしくはJavaScriptの関数だけです。よって、テーマ機能はもちろん、CSSやHTMLの決まった書き方などもありません。それをどう使うかはNewtrinoをライブラリとして使う利用者、すなわちウェブサイトの制作者に委ねられています。

#### ■ ポータブル

Newtrinoはデータの保管にテキスト・ファイルを使っているので、特定のDBに依存しません。また、URLのリライト機能を使う代わりに、昔ながらのクエリ・パラメターを使っています。これにより、サーバーへの依存や、`.htaccess`の書き方に悩まされることがなくなります。

Newtrinoを使ったウェブサイトは、あるサーバーから別のサーバーにコピーしても、容易に動かすことが可能です。通常では、サーバーが変わるとURLが変わるため、あちこちにリンク切れが発生してしまいます。それを回避するには、あらかじめ（DB等に書き込まれている）URLを正しいものに置換しなければなりません。Newtrinoでは、投稿データに書き込まれる画像等のURLを相対URLにしているので、この問題は起きません。

もっとも、編集画面でいくらでも絶対URLを書き込むことはできますが、結局のところデータはすべてテキスト・ファイルなので、置換も容易に行えます。

### 1.2. 動作要件

Newtrino（2系）の動作要件は次の通りです。

- PHP 7.4.0 以降
- PHPからファイルの読み書きのできるディレクトリ
- Evergreenブラウザー（自動的に更新されるようになっている最新のブラウザー）

### 1.3. サンプルを動かす

まずはサンプルを動かしてみましょう。Githubリポジトリをクローンして、`npm`を使ったパッケージのインストールを行い、Newtrino一式をビルドします。以下では、GitとNode.jsがインストールされている必要があります。

所定のディレクトリ（例: `C:\Git`）で、以下のコマンドを実行し、リポジトリをクローンします。`newtrino`ディレクトリが作られます。

```
git clone https://github.com/takty/newtrino.git
```

`npm`を使って必要なパッケージ一式をインストールします。エラーが出るときは、Node.jsや`npm`のバージョンを確認してください。

```
npm i
```

パッケージのインストールが終わると自動的に`dist`ディレクトリにビルドされますが、必要に応じて、ビルドすることもできます。

```
npm start build
```

最後にサンプルをビルドします。`sample`ディレクトリにNewtrinoのサンプルが構成されます。

```
npm start sample
```

これでサンプルができましたので、このフォルダをウェブ・サーバーのドキュメント・ルート以下の任意の場所に置き、ブラウザーで`sample/html/index.html`（JS版）もしくは`sample/php/index.php`（PHP版）を開くと、サンプルが表示されるはずです。

Newtrinoの管理画面にアクセスするには、`sample/nt/admin/login.php`をブラウザーで開きます。初期アカウントは、ユーザー名、パスワードともに`stxst`です。

パーミッションは次の通りとします。ここでの`user`はお使いのユーザーに置き換えてください。

- アカウント（グループを共通に）
  - Apache: `apache:apache`
  - SFTPユーザー: `user:apache`（groupsを`apache`に変更）
- 全ファイル
  - `user:apache`
  - ディレクトリ: `0770`
  - ファイル: `0660`
- umask (Apacheの設定はデフォルトでOK)
  - SFTP (`/etc/ssh/sshd_config`): `007`

| **ポイント**                                                                                                                                                                                          |
| ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `admin/`ディレクトリと`core/`ディレクトリの中は基本的に書き換えられることはありません。しかし、それぞれのディレクトリにある`var/`ディレクトリ内には、例外的にファイルの作成、更新、削除が行われます。 |
| `admin/var/`ディレクトリには、認証とセッション管理のためのファイルが、`core/var/`ディレクトリにはログ・ファイルが作成されます。                                                                       |

### 1.4. アカウントの追加

新規にアカウントを発行するには、まず既存の有効なアカウントを使って、招待コードを発行する必要があります。招待コードを使うと、ユーザー名とパスワードを指定して新たなアカウントを作成することができます。

ログイン画面でアカウントのユーザー名とパスワードを入力して、「ログイン」ボタンを左ボタン長押しします。すると、招待コードが発行されます。

ログイン画面の「Newtrino」の部分をクリックすると、ユーザー登録画面に切り替わります。ここに、招待コードと、新しいアカウントのユーザー名、パスワードを入力し「登録」をクリックします。

招待コードの有効期限は7日間で、一度使用すると無効になります。


## 2. Newtrinoの構成

### 2.1. PHP版とJS版の違い

Newtrinoのシステム部分（Core）はPHPで出来ていますが、表示側にはJS版とPHP版のいずれかを使用可能です。

PHP版はサーバー側で動作するので、直接Coreとやり取りしてポストデータを取得して、それを元にサーバー側でHTMLを組み立て、クライアントに送信します。

JS版はブラウザー側で動作するので、AjaxによってCoreからポストデータを取得して、ブラウザー側でHTMLを組み立て、表示します。

JS版はブラウザー側が記事ページを組み立てることになるので、ブラウザーで閲覧している分には、メタ情報（タイトルやディスクリプション）も問題なく扱えますが、クローラーからのアクセスには反映されないことがあります。具体的には、GoogleのクローラーはJavaScriptを認識し実行するので問題ありませんが、FacebookやTwitterのクローラーはJavaScriptを実行しないので、タイトルやディスクリプションが反映されません。

### 2.2. システムファイル

Newtrinoを構成するファイル、ディレクトリは次のとおりです。ウェブサイトに組み込まれた状態では、どこか特定のディレクトリ（例えば`nt/`）に一式が含まれることになります（サンプルを参照）。このディレクトリをNewtrinoディレクトリと呼びます。

- `admin/`
- `core/`
- `index.php`
- `index.min.is`
- `index.min.is.map`

`admin/`ディレクトリには、Newtrinoの管理画面にログインして記事を編集するためのスクリプトが含まれています。

| **ポイント**                                                                                                                                                                                |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 編集機能を使う必要がない場合、例えば、Newtrinoを組み込んで運用していたがもう更新する必要がなくなったときなどは、`admin`ディレクトリを削除して、リードオンリーとして運用することができます。 |

一方、`core/`ディレクトリには、表示に携わる機能が含まれています。文字通り、Newtrinoのコア部分となります。

`index`で始まるファイルには、記事の一覧や記事自体を表示するときに、各ページから呼び出される関数が含まれています。その関数の中から`core/`ディレクトリの中にあるスクリプトが呼び出されます。ですので、Newtrinoをライブラリとして使うときのエントリー・ポイントとも言えます。

PHP版もJS版も、表示側からはこのエントリー・ポイントのみがアクセスされます。

### 2.3. ウェブサイトによって異なるファイル

Newtrinoディレクトリには、`core/`と`admin/`以外にも、いくつかのディレクトリが含まれます。

まず必須となるのが、Newtrinoの設定にかかわるファイルが含まれる`data/`ディレクトリです。ここには、アカウント情報や編集画面のスタイル、投稿タイプやタクソノミーの定義が含まれます。

また、投稿タイプごとの投稿データを保持するディレクトリも存在します。サンプルでは投稿タイプ`post`を定義しているので、投稿を作成すると、`post/`ディレクトリにそのデータが入れられることになります。

### 2.4. アップデートの方法

既存のサイトのNewtrinoディレクトリにある、`admin/`ディレクトリ、`core/`ディレクトリを、新しいNewtrinoのリポジトリをビルドした`dist/admin/`ディレクトリ、`dist/core/`ディレクトリで置き換えることで、アップデートは完了します。

| **ポイント**                                                                                                                                                                                                                                                             |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Newtrinoを使ったウェブサイトをリポジトリで管理しているような場合や、静的サイト・ジェネレーター等と組み合わせて使用されている場合は、Newtrinoのアップグレード手段が別途用意されている可能性があります。その場合は、直接置き換えずに、用意されている手段で行ってください。 |


## 3. ウェブサイトに組み込む

Newtrinoをウェブサイトに組み込む例として、最新記事を一定数掲載するフロント・ページ、記事の一覧を表示する一覧ページ、記事そのものを表示する個別ページの三つの場合に分けて説明します。

### 3.1. フロント・ページに新着記事を表示

フロント・ページは新着記事以外の内容も多く含まれるでしょうから、ここではJS版で作成してみましょう。

まず、ヘッダーに次のように書いて、必要なスクリプトを読み込みます`。mustache.min.js`はMustacheというテンプレート・ライブラリ、`luxon.min.js`は時間や日付をフォーマットする関数を提供するライブラリです。`../nt/index.min.js`はNewtrinoのエントリー・ポイントとなるライブラリです。Newtrinoディレクトリへの相対URLで書いています。

```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/mustache.js/4.0.1/mustache.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@2.3.1/build/global/luxon.min.js"></script>
<script src="../nt/index.min.js"></script>
```

ヘッダー内の`<script>`タグで、次のように書いて、投稿データを取得します。ここでは、投稿タイプが`post`の記事を2件取得しています。一つ目の引数の`'../nt/'`はAjax APIのURL、つまりNewtrinoディレクトリのURLです。

二つ目の引数はデータを取得できたときに呼び出すコールバック関数です。ここではPromise経由で呼び出しているので、このように書くことで、取得したデータは定数`vp`に代入されます。

三つ目の引数はオプションで、投稿数、投稿のURLのベース、クエリ等、様々なパラメターを設定しています。

```js
    document.addEventListener('DOMContentLoaded', async () => {
        const vp = await new Promise(res => NT.queryRecentPosts('../nt/', view => res(view), { count: 2, base_url: './topic/', query: { type: 'post' }, option: { date_format: 'yyyy-MM-dd' } }));
        NT.renderTemplate('#post[type="text/html"]', vp);
    });
```

取得した投稿データは`NT.renderTemplate('#post[type="text/html"]', vp);`によって、Mustacheでレンダリングされます。一つ目の引数はテンプレートを表す`script`要素を取得するためのセレクターです。

テンプレートは次のようになります。

```html
<script type="text/html" id="post">
    <ul id="list-item-post">
{{#posts}}
        <li class="{{class@joined}}{{#meta.sticky}} sticky{{/meta.sticky}}">
            <a href="{{url}}">
                {{#taxonomy.category}}
                <span class="category">{{label}}</span>
                {{/taxonomy.category}}
                <div class="title">{{title}}</div>
                <div class="excerpt">{{{excerpt}}}</div>
                <div class="date">{{date}}</div>
                {{#meta.thumbnail}}
                <img src="{{url}}" width="{{width}}" height="{{height}}" srcset="{{srcset}}">
                {{/meta.thumbnail}}
            </a>
        </li>
{{/posts}}
    </ul>
</script>
```

`NT.queryRecentPosts()`関数で取得した投稿データには、投稿データ以外にもいくつかの補助的な内容が含まれます。

投稿データはオブジェクトの`posts`というキーの値（配列）なので、`{{#posts}}`～`{{/posts}}`の間が、各投稿データ一つずつに対して展開されます。ここでは、`<li>`～`</li>`とありますので、投稿の1件ずつがリストの要素としてレンダリングされます。

各投稿データは次のようなデータを持ちます。

| キー       | 値                                                                                                    |
| ---------- | ----------------------------------------------------------------------------------------------------- |
| `url`      | URL。`queryRecentPosts()`のオプションの`base_url`に投稿IDがクエリ・パラメターとして連結されたもの。   |
| `title`    | タイトル。                                                                                            |
| `excerpt`  | 抜粋。                                                                                                |
| `date`     | 日付。`date_format`で指定したフォーマットでフォーマットされたもの。                                   |
| `taxonomy` | タクソノミー。タクソノミーごとにタームの配列を持つ。                                                  |
| `meta`     | メタ・データ。キーごとに値の配列を持つ。                                                              |
| `class`    | 投稿ステータスや投稿タイプのクラス表記の配列。`class@joined`は`class`をスペース区切りでつないだもの。 |

### 3.2. 一覧ページに記事の一覧を表示

記事一覧ページはPHPで書いてみましょう。一覧ページは、`query_recent_posts()`関数（JS版は`queryRecentPosts()`関数）ではなく、`query()`関数を使います。引数は同じです。ポイントは`filter`をキーにしたデータを渡し、フィルター用の情報も取得しているところです。

```php
<?php
require_once( __DIR__ . '/../../nt/index.php' );
$view = \nt\query( [
    'filter' => [ 'taxonomy' => [ 'category' ], 'date_format' => 'Y' ],
    'option' => [ 'lang' => 'ja', 'date_format' => 'Y-m-d' ]
] );
header( 'Content-Type: text/html;charset=utf-8' );
?>
```

データを取得出来たら、Mustacheでレンダリングします。PHP版のMustacheは、`begin()`関数と`end()`関数の間がテンプレートとなります。`begin()`関数の一つ目の引数がデータ、二つ目の引数がレンダリングする条件（`true`ならばレンダリングする）です。

最初にフィルター部分をレンダリングします。ここでは、日付（年）、タクソノミー（`category`）の選択、そして自由検索欄を設けています。

```html
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Newtrino Sample</title>
</head>
<body>
    <header>
        <h1><a href="../">Newtrino Sample</a></h1>
    </header>

<?php \nt\begin( $view, empty( $view['post'] ) ); ?>
    <main>
        <header class="entry-header">
            <h2>Topics</h2>
        </header>
        <div class="aside aside-filter">
            <div class="filter-date">
                {{#filter.date}}
                <select onchange="document.location.href = this.value;">
                    <option value="./">Year</option>
                    {{#year}}<option value="{{url}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>{{/year}}
                </select>
                {{/filter.date}}
            </div>
            <div class="filter-taxonomy">
                {{#filter.taxonomy}}
                <select onchange="document.location.href = this.value;">
                    <option value="./">Category</option>
                    {{#category}}<option value="{{url}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>{{/category}}
                </select>
                {{/filter.taxonomy}}
            </div>
            <div class="filter-search">
                {{#filter.search}}
                <form action="./" method="get">
                    <input type="text" name="search" value="{{keyword}}">
                    <input type="submit" value="Search">
                </form>
                {{/filter.search}}
            </div>
        </div>
```

続いて記事のリストです。これは、新着記事のレンダリングの部分とほぼ同じです。`posts`をキーとしてその配列の要素ひとつずつがリストの要素となるようにレンダリングしています。

```html
        <div class="entry-content">
            <ul id="list-item-post">
{{#posts}}
                <li class="{{class@joined}}" id="temp-item-post">
                    <a href="{{url}}">
                        {{#taxonomy.category}}
                        <span class="category">{{label}}</span>
                        {{/taxonomy.category}}
                        {{#meta.duration}}
                        <span class="event-date">Event Date: {{from}} to {{to}}</span>
                        {{/meta.duration}}
                        <div class="title">{{title}}</div>
                        <div class="excerpt">{{{excerpt}}}</div>
                        <div class="date">{{date}}</div>
                        {{#meta.thumbnail}}
                        <img src="{{url}}" width="{{width}}" height="{{height}}" srcset="{{srcset}}">
                        {{/meta.thumbnail}}
                    </a>
                </li>
{{/posts}}
            </ul>
        </div>
    </main>
```

一覧ページに欠かせないもう一つの要素が、ページネーションです。こちらも、`query()`関数の戻り値として得られるビューに必要な情報が含まれているので、それを使ってレンダリングします。

```html
{{#navigation.pagination}}
    <div class="aside aside-navigation">
        <div class="pagination">
            {{#previous}}
            <a href="{{.}}">Previous</a>
            {{/previous}}
            <select onchange="document.location.href = this.value;">
                {{#pages}}<option value="{{url}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>{{/pages}}
            </select>
            {{#next}}
            <a href="{{.}}">Next</a>
            {{/next}}
    </div>
</div>
{{/navigation.pagination}}
<?php \nt\end(); ?>
</body>
</html>
```

### 3.3. 個別ページに記事の内容を表示

個別ページもPHP版で書いてみましょう。ビューを取得するところまでは同じです。投稿1件の情報は、ビューの`post`キーの値となって返されます。

違いは、`<head>`要素のなかのメタ情報です。メタ情報も、ビューの`post`キーに含まれる`url`や`title`などを使ってレンダリングしています。このメタ情報は、SNSでシェアしたときにもきちんと反映されます。これがPHP版を使うメリットです。

PHP版ではサーバーでレンダリングが行われるのでブラウザーやSNSのクローラーが取得するHTMLにはすでにメタ情報が展開された状態になっているからです。

```php
<?php
require_once( __DIR__ . '/../../nt/index.php' );
$view = \nt\query( [
    'filter' => [ 'taxonomy' => [ 'category' ], 'date_format' => 'Y' ],
    'option' => [ 'lang' => 'ja', 'date_format' => 'Y-m-d' ]
] );
header( 'Content-Type: text/html;charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php \nt\begin( $view, isset( $view['post'] ) ); ?>
<meta property="og:type" content="article">
<meta property="og:url" content="{{post.url}}">
<meta property="og:title" content="{{post.title}}">
<meta property="og:description" content="{{post.excerpt}}">
<meta property="og:site_name" content="Newtrino Sample">
{{#post.meta.thumbnail}}
<meta property="og:image" content="{{url}}">
{{/post.meta.thumbnail}}
<title>{{post.title}} - Newtrino Sample</title>
<?php \nt\end(); ?>
a</head>
```

記事の内容は、ビューの`post`キーの情報をもとにレンダリングするという点以外、一覧表示とそれ程違いないと思います。

```html
<body>
    <header>
        <h1><a href="../">Newtrino Sample</a></h1>
    </header>
<?php \nt\begin( $view, ! empty( $view['post'] ) ); ?>
{{#post}}
    <main class="entry {{class@joined}}">
        <header class="entry-header">
            {{#taxonomy.category}}
            <div class="category">{{label}}</div>
            {{/taxonomy.category}}
            <h2>{{title}}</h2>
            {{#meta.duration}}
            <span class="event-date">Event Date: {{from}} to {{to}}</span>
            {{/meta.duration}}
            {{^meta.duration}}
            <div class="date">{{date}}</div>
            {{/meta.duration}}
        </header>
        <div class="entry-content">
            {{&post.content}}
        </div>
    </main>
{{/post}}
```

投稿ページには、通常、次の投稿、前の投稿に移動するボタンが含まれますので、それもレンダリングしましょう。ビューにはそのための情報が`navigation`キーに含まれます。

```php
{{#navigation.post_navigation}}
    <div class="aside aside-navigation">
        <div class="post_navigation">
            {{#previous}}
            <a href="{{url}}">Previous</a>
            {{/previous}}
            {{#next}}
            <a href="{{url}}">Next</a>
            {{/next}}
        </div>
    </div>
{{/navigation.post_navigation}}
<?php \nt\end(); ?>
</body>
</html>
```

| **ポイント**                                                                                                                                                                                                                        |
| ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 一覧ページも個別ページもデータの取得部分は同じ`query()`関数です。では、どうやってどちらのページなのかを区別しているのでしょうか？                                                                                                   |
| Newtrinoでは、クエリ・パラメターによって、表示すべきページを決定しています。`query()`関数は、引数に渡された各種情報と、現在のクエリ・パラメターをもとに、返すビューを決定しています。そのため、同じ関数で異なる表示が可能なのです。 |

 
## 4. カスタマイズする

### 4.1. タクソノミーの定義

タクソノミーの定義は、`data/taxonomy.json`で行います。なお、あらかじめ組み込まれたタクソノミーというものはありません。

`data/taxonomy.json`はタクソノミーの配列がルート要素となります。配列の要素はタクソノミーごとのオブジェクトで、スラッグやラベル、各タームの定義を含みます。サンプルに含まれる`data/taxonomy.json`は次のような内容です。

#### `data/taxonomy.json`
```json
[
    {
        "slug"        : "category",
        "label"       : "Categories",
        "label@ja"    : "カテゴリ",
        "sg_label"    : "Category",
        "is_exclusive": true,
        "terms"       : [
            {
                "slug"    : "news",
                "label"   : "News",
                "label@ja": "ニュース"
            },
            {
                "slug"    : "column",
                "label"   : "Columns",
                "label@ja": "コラム"
            }
        ]
    },
    {
        "slug"    : "lang",
        "label"   : "Languages",
        "label@ja": "言語",
        "terms"   : [
            {
                "slug"    : "ja",
                "label"   : "Japanese",
                "label@ja": "日本語"
            },
            {
                "slug"    : "en",
                "label"   : "English",
                "label@ja": "英語"
            }
        ]
    }
]
```

タクソノミーごとのオブジェクトは次のデータを含みます。

| キー           | 値                                                                         |
| -------------- | -------------------------------------------------------------------------- |
| `slug`         | タクソノミーのスラッグを表す文字列。クエリ・パラメターに使用されます。     |
| `label`        | タクソノミーのラベルを表す文字列。                                         |
| `label@ja`     | タクソノミーの言語ごとのラベルを表す文字列。ここでは`ja`（日本語）の意味。 |
| `sg_label`     | タクソノミーの単数形の場合のラベルを表す文字列。                           |
| `is_exclusive` | 排他的かどうか。`true`か`false`。                                          |
| `terms`        | ターム定義の配列。                                                         |

ターム定義の配列、`terms`には、タームごとのオブジェクトが含まれます。タームごとのオブジェクトは次のデータを含みます。

| キー       | 値                                                                   |
| ---------- | -------------------------------------------------------------------- |
| `slug`     | タームのスラッグを表す文字列。クエリ・パラメターに使用されます。     |
| `label`    | タームのラベルを表す文字列。                                         |
| `label@ja` | タームの言語ごとのラベルを表す文字列。ここでは`ja`（日本語）の意味。 |

### 4.2. 投稿タイプの定義

投稿タイプの定義は、`data/type.json`ファイルで行います。こちらもタクソノミーと同様に、組み込みタイプは存在しませんので、必ず一つは定義されている必要があります。

`data/type.json`は投稿タイプの配列がルート要素となります。配列の要素は投稿タイプごとのオブジェクトで、スラッグやラベルなどの定義を含みます。

サンプルに含まれる`data/type.json`は次のような内容です。ここでは、`post`と`event`の二つの投稿タイプを定義しています。

#### `data/type.json`
```json
[
    {
        "slug"    : "post",
        "label"   : "Posts",
        "label@ja": "投稿",
        "taxonomy": [ "category", "lang" ],
        "meta"    : [
            {
                "key"     : "sticky",
                "type"    : "checkbox",
                "label"   : "Stick this post to the front page",
                "label@ja": "この投稿を先頭に固定表示"
            },
            {
                "key"     : "thumbnail",
                "type"    : "media_image",
                "label"   : "Featured Image",
                "label@ja": "アイキャッチ画像",
                "option"  : {
                    "size": "small"
                }
            }
        ]
    },
    {
        "slug"    : "event",
        "label"   : "Events",
        "label@ja": "イベント",
        "taxonomy": [ "target", "lang" ],
        "meta"    : [
            {
                "key"     : "thumbnail",
                "type"    : "media_image",
                "label"   : "Featured Image",
                "label@ja": "アイキャッチ画像",
                "option"  : {
                    "size": "small"
                }
            },
            {
                "key"           : "duration",
                "type"          : "date_range",
                "label"         : "Event Duration",
                "label@ja"      : "開催期間",
                "do_show_column": true
            }
        ]
    }
]
```

各投稿タイプを表すオブジェクトは次の情報を持ちます。

| キー       | 値                                                                       |
| ---------- | ------------------------------------------------------------------------ |
| `slug`     | 投稿タイプのスラッグを表す文字列。クエリ・パラメターに使用されます。     |
| `label`    | 投稿タイプのラベルを表す文字列。                                         |
| `label@ja` | 投稿タイプの言語ごとのラベルを表す文字列。ここでは`ja`（日本語）の意味。 |
| `taxonomy` | 関連付けられるタクソノミーの配列。タクソノミーのスラッグを指定します。   |
| `meta`     | メタ情報を表す配列。                                                     |

メタ・フィールドを表す配列は、その要素がフィールドを表し、次の情報を持ちます。

| キー             | 値                                                                                                                          |
| ---------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `key`            | フィールドのキーを表す文字列。                                                                                              |
| `type`           | フィールドの型を表す文字列。`text`、`checkbox`、`date`、`date_range`、`media`、`media_image`、`group`のいずれかを取ります。 |
| `label`          | フィールドのラベルを表す文字列。                                                                                            |
| `label@ja`       | フィールドの言語ごとのラベルを表す文字列。ここでは`ja`（日本語）の意味。                                                    |
| `do_show_column` | 管理画面の一覧表示にコラムとして表示するかを表す真偽値。デフォルトは`false`。                                               |
| `option`         | 型ごとのオプション。                                                                                                        |

型が`group`の時は、`items`に、フィールドの情報を表すオブジェクトの配列を指定できます。これによって、管理画面におけるフィールドの表示をまとめることができます。

```json
"meta": [
    {
        "type"    : "group",
        "label"   : "Group",
        "label@ja": "グループ",
        "items"   : [
            {
                "key"     : "sticky",
                "type"    : "checkbox",
                "label"   : "Stick this post to the front page",
                "label@ja": "この投稿を先頭に固定表示"
            },
            {
                "key"     : "thumbnail",
                "type"    : "media_image",
                "label"   : "Featured Image",
                "label@ja": "アイキャッチ画像",
                "option"  : {
                    "size": "small"
                }
            }
        ]
    }
]
```

`option`には、型ごとに次の設定が可能です。

#### `media_image`
| キー   | 値                                 |
| ------ | ---------------------------------- |
| `size` | 画像サイズ。デフォルトは`medium`。 |

### 4.3. コンフィグファイル

サイト全体の設定を行うファイルは2種類あります。一つ目は、`data/config.json`です。サンプルの内容と、各設定内容は次の通りです。

#### `data/config.json`
```json
{
    "lang"              : "ja",
    "lang_admin"        : "ja",
    "per_page"          : 10,
    "new_arrival_period": 7,
    "date_format"       : "Y-m-d",
    "archive_by_year"   : true,
    "archive_by_type"   : true,
    "image_sizes"       : {
        "small"       : { "width":  128, "label": "Small" },
        "medium_small": { "width":  256, "label": "Medium Small" },
        "medium"      : { "width":  384, "label": "Medium" },
        "medium_large": { "width":  512, "label": "Medium Large" },
        "large"       : { "width":  768, "label": "Large" },
        "extra_large" : { "width": 1024, "label": "Extra Large" },
        "huge"        : { "width": 1536, "label": "Huge" }
    }
}
```

| キー                 | 値                                                                                                       |
| -------------------- | -------------------------------------------------------------------------------------------------------- |
| `timezone`           | タイムゾーンを表す文字列。デフォルトは`Asia/Tokyo`。                                                     |
| `lang`               | 言語を表す文字列。デフォルトは`en`。                                                                     |
| `lang_admin`         | 管理画面の言語を表す文字列。デフォルトは`en`。                                                           |
| `per_page`           | アーカイブ表示の1ページ当たりの投稿数を表す整数。デフォルトは`10`。                                      |
| `new_arrival_period` | 新着記事として扱う現在からの日数を表す整数。デフォルトは`7`。                                            |
| `date_format`        | 日付フォーマットを表す文字列。PHPの`date`関数が受け入れるフォーマットを指定します。デフォルトは`Y-m-d`。 |
| `archive_by_year`    | 投稿を保存するディレクトリを年ごとに分けるかどうかを表す真偽値。デフォルトは`true`。                     |
| `archive_by_type`    | 投稿を保存するディレクトリを投稿タイプごとに分けるかどうかを表す真偽値。デフォルトは`true`。             |
| `image_sizes`        | 画像サイズ。                                                                                             |

もう一つの設定ファイルは、`data/config.php`です。これは普通に読みこまれる（実行される）ファイルです。中では次のような定数を定義しています。それぞれの意味は次の通りです。

#### `data/config.php`
| キー           | 値                                                                                                                    |
| -------------- | --------------------------------------------------------------------------------------------------------------------- |
| `NT_MODE_DIR`  | NTが作成するディレクトリのパーミッションを表す8進数の数値です。デフォルトは`0770`。                                   |
| `NT_MODE_FILE` | NTが作成するファイルのパーミッションを表す8進数の数値です。デフォルトは`0660`。                                       |
| `NT_DEBUG`     | デバッグモードにするかどうかを表す真偽値。`true`にすると、通知がすべて表示されるようになります。デフォルトは`false`。 |
| `NT_AUTH_KEY`  | 認証キーを表す文字列。キーはログイン画面のHTMLに出力されます。デフォルトは`newtrino`。                                |

### 4.4. 管理画面のカスタマイズ

エディターのスタイルを変更するには、`data/editor.css`を編集します。同様に、プレビュー画面のスタイルを変更するには、`data/preview.css`を編集します。

エディター（TinyMCE）のオプションを指定するには、`data/editor.json`を変更します。`editor.ja.json`のような名前のファイルを用意すると、管理画面の言語によって読みこむファイルを切り替えることができます（例えば、言語が`ja`の時に`editor.ja.json`が読みこまれます）。

その他の設定を変えたいときなどは、`editor.js`を用意しておくと、編集画面で読みこまれます。こちらも言語指定があればそれが優先されて読みこまれます。また、ミニファイされたファイルがあればそちらが優先されます。例えば、`editor.ja.min.js`、`editor.ja.js`、`editor.min.js`、`editor.js`の順でファイルが探索され、見つけるとそれが読みこまれます。

### 4.5. 多言語化

タクソノミーやターム、投稿タイプなど、ラベル（`label`）を指定するものはすべて、`label@ja`のように、キーにアットマークと言語タグを繋げることで、その言語のラベルを設定できます（ここでは日本語`ja`を指定）。

また、設定ファイルに関しては、`editor.ja.json`のように、ファイル名の拡張子と名前の間に言語タグを挿入することで、その言語用の設定ファイルとすることができます（ここでは日本語`ja`を指定）。

いずれの場合も、多言語化する必要がない時は、言語タグのなしのラベルを設定し、ファイルを用意すれば、それが使用されます。


## 5. クエリの書き方

投稿を取得するクエリは`query`パラメターとして、PHP版の場合は`\nt\query()`関数か`\nt\query_recent_posts()`関数、JavaScript版の場合は`NT.query()`関数か`NT.queryRecentPosts()`関数に渡します。

例えば、下記の例では、一つ目のクエリとして、投稿数`-1`（すべて）、投稿タイプ`post`、メタ・クエリとして、キーが`sticky`であるフィールドを持つことを条件に検索しています。そして二つ目のクエリとして、投稿数`10`、投稿タイプ`post`、メタ・クエリとしてキーが`sticky`のフィールドが存在しないことを条件に検索しています。

複数のクエリを指定した場合、検索は順番に行われ、最初の検索結果が優先される形で複数の検索結果がマージされます。

```js
    document.addEventListener('DOMContentLoaded', async () => {
        const vp = await new Promise(res => NT.queryRecentPosts('../nt/', view => res(view),
            {
                base_url: './topic/',
                query: [
                    {
                        per_page  : -1,
                        type      : 'post',
                        meta_query: [ { key: 'sticky' } ]
                    },
                    {
                        per_page  : 10,
                        type      : 'post',
                        meta_query: [ { key: 'sticky', compare: 'not exist' } ]
                    },
                ],
                option: { date_format: 'yyy-MM-dd' }
            }
        ));
        NT.renderTemplate('#post[type="text/html"]', vp);
    });
```

### 5.1. クエリの種類

基本的なクエリ・パラメターは以下の通りです。

| キー         | 値                                                                              |
| ------------ | ------------------------------------------------------------------------------- |
| `id`         | 投稿IDを表す整数。                                                              |
| `page`       | ページング時のページを表す整数。デフォルトは1。                                 |
| `per_page`   | ページング時の一ページ当たりの投稿数を表す整数。デフォルトはconfig.jsonに従う。 |
| `type`       | 投稿タイプを表す文字列。                                                        |
| `status`     | 投稿ステータスを表す文字列。                                                    |
| `search`     | 検索文字列。                                                                    |
| `tax_query`  | タクソノミー・クエリを表す配列、もしくはオブジェクト。                          |
| `date_query` | 日付クエリを表す配列、もしくはオブジェクト。                                    |
| `meta_query` | メタ・クエリを表す配列、もしくはオブジェクト。                                  |

タクソノミー・クエリの配列には、以下の内容を持ったオブジェクトを複数含みます。

| キー     | 値                                               |
| -------- | ------------------------------------------------ |
| taxonomy | タクソノミーを表す文字列。                       |
| terms    | ターム・スラッグを表す文字列、もしくはその配列。 |

オブジェクトを複数含む場合は、その関係を指定することも可能です。`tax_query`にセットする配列に、キーを`relation`として`AND`か`OR`を指定することができます。JavaScript版では配列にキーを指定できないので、全体をオブジェクトとし、条件を表すオブジェクトをキー`0`から順に数値のキーにしてセットします。

日付クエリには、以下の内容を持ったオブジェクトを複数含めることができます。タクソノミー・クエリと同様に、`relation`を指定することもできます。

#### 直接指定

| キー    | 値                                     | 補足                                                        |
| ------- | -------------------------------------- | ----------------------------------------------------------- |
| `year`  | 年を表す数値。                         | `before`と`after`の両方が指定されていないときのみ有効です。 |
| `month` | 月を表す数値。                         | 同上                                                        |
| `day`   | 日を表す数値。                         | 同上                                                        |
| `date`  | 対象となる日付を表す4桁から8桁の数値。 | 同上                                                        |

#### 範囲指定

| キー     | 値                                 |                                                                                                                                                     |
| -------- | ---------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| `before` | 範囲の開始日付を表すオブジェクト。 | 日付を表すオブジェクトとは、`year`、`month`、`day`、`date`をキーとして値を持つオブジェクトです。`before`も`after`も指定した日付は範囲に含まれます。 |
| `after`  | 範囲の終了日付を表すオブジェクト。 | 同上                                                                                                                                                |

メタ・クエリには、以下の内容を持ったオブジェクトを複数含められます。タクソノミー・クエリと同様に、`relation`を指定することもできます。

| キー      | 値                                 |
| --------- | ---------------------------------- |
| `key`     | メタ・キーを表す文字列。           |
| `type`    | メタ情報の型。                     |
| `val`     | メタ・バリュー。                   |
| `compare` | どのように比較するかを表す文字列。 |

### 5.2. 複合クエリ

PHP版の場合の`\nt\query()`関数や`\nt\query_recent_posts()`関数、JavaScript版の場合の`NT.query()`関数や`NT.queryRecentPosts()`関数は、`query`パラメターに複数のクエリを渡すことによって、複数の検索結果を組み合わせられます。

複数のクエリを指定した場合、検索は順番に行われ、これまでの検索結果に含まれない投稿が、検索結果に追加されていきます。


## 6. APIリファレンス

### 6.1. PHP版API

```php
query( array $args = [] ): array { ... }
query_recent_posts( array $args = [] ): array { ... }
```

### 6.2. JS版API

```js
function query(url, callback, args = {}) { ... }
function queryRecentPosts(url, callback, args = {}) { ... }
```

### 6.3. Mustacheの使い方

#### Variables

```
{{key}}
```

`key`で受け取った値に置換します。

#### Section

```
{{#key}} ~ {{/key}}
```

`key`で受け取った値が`false`でも空のリストでもないとき、`#key`と`/key`の中身が有効になります。`key`で受け取った値が空でないリストの場合は、リストの中身を列挙します。

#### Invert Sections

```
{{^key}} ~ {{/key}}
```

`key`で受け取った値が`false`または空のリストのとき、`^key`と`/key`の中身が有効になります。
