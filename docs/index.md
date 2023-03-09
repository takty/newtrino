# Newtrino Developers' Guide
Takuto Yanagida

## Introduction

Newtrino is a portable embeddable content management system (CMS). This document describes its features, how it works, and how to embed it.

## 1. What is Newtrino

### 1.1. Characteristics as a CMS

Newtrino is a very simple content management system (CMS) using flat files, available as a library

It is named after the fact that contents often updated on CMS website are news articles, and that it can be easily incorporated into other websites as a library, much like a subatomic particle (neutrino).

Newtrino has the following three main features:

#### Flat Files

Newtrino uses flat files for data storage. A flat file is simply a text file. In other words, it does not use a database (DB).

The first advantage of using text files for data storage is that there is no need to install any DBs. Second, because it is a file, it can be copied and backed up using normal file operations.

In addition to article data, Newtrino also stores post type and taxonomy information in JSON format, a type of text file. Therefore, you can change the settings by simply changing the text using your usual text editor.

#### A Part of Your Website

WordPress, a widely used CMS, is typically used to create an entire website. Basically, if a website is created using WordPress, all its pages and images will be under the control of WordPress.

Newtrino, on the other hand, provides the ability to view and edit articles as part of a website. Except for the article listing page and each article page, the rest of the website is usually managed separately from Newtrino. Simple HTML can be used, or some kind of static generator can be used. This is what differentiates Newtrino from a full-featured CMS such as WordPress.

Newtrino provides only a few PHP functions or JavaScript functions. Therefore, there are no theme functions, nor is there a fixed way to write CSS or HTML. It is up to the user who uses Newtrino as a library, i.e., the creator of the website, to decide how to use it.

#### Portability

Newtrino uses text files for data storage and does not rely on a specific DB. It also uses old-fashioned query parameters instead of using URL rewrites. This eliminates server dependencies and the need to worry about writing `.htaccess`.

Websites using Newtrino can easily be copied from one server to another. Under normal circumstances, a change of server will result in broken links here and there because the URL changes when the server is changed. To avoid this problem, you need to replace the URLs (written in the DB, etc.) with the correct ones in advance.

You can write absolute URLs in the edit window as much as you like, but after all, the data is all text files, so it is easy to replace them.

### 1.2. Operating Requirements

Newtrino (v2.x) has the following requirements:

- PHP 7.4.0 or later
- A directory where you can read and write files from and to PHP
- Evergreen browser (latest browser with automatic updates)

### 1.3. Running the Sample

First, let's get the sample working: clone the Github repository, install the packages using `npm`, and build the complete Newtrino set. In the following, Git and Node.js must be installed.

In a given directory (e.g. `C:\Git`), run the following command to clone the repository. The `newtrino` directory will be created.

```
git clone https://github.com/takty/newtrino.git
```

Install the required set of packages using `npm`. If you get an error, check the version of Node.js and `npm`.

```
npm i
```

It is is automatically built in the `dist` directory after package installations, but can also be built if necessary.

```
npm start build
```

Finally, build the sample. The `sample` directory will consist of Newtrino samples.

```
npm start sample
```

Now that you have a sample, place this folder anywhere under the document root of your web server, and open `sample/html/index.html` (JS version) or `sample/php/index.php` (PHP version) in your browser. should appear.

To access the Newtrino administration page, open `sample/nt/admin/login.php` in your browser. The initial account is `stxst` for both username and password.

The permissions should be as follows. Replace `user` here with your user.

- Account (group in common)
  - Apache: `apache:apache`
  - SFTP user: `user:apache` (change groups to `apache`)
- All files
  - `user:apache`
  - Directories: `0770`
  - Files: `0660`
- umask (default Apache settings are ok)
  - SFTP (`/etc/ssh/sshd_config`): `007`

| **Point**                                                                                                                                                                                                |
| -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| The inside of the `admin/` and `core/` directories are basically never rewritten. However, files are exceptionally created, updated, and deleted in the `var/` directories in each of these directories. |
| The `admin/var/` directory contains files for authentication and session management, and the `core/var/` directory contains log files.                                                                   |

### 1.4. Adding Accounts

To issue a new account, you must first use an existing valid account and issue an invitation code. The invitation code allows you to create a new account with a username and a password.

On the login screen, enter the username and password for the new account and press and hold the "Login" with the left button. An invitation code will then be issued.

Click on the "Newtrino" portion of the login screen to switch to the user registration screen. Enter the invitation code, username and password for the new account, and click "Register".

The invitation code is valid for 7 days and becomes invalid once used.


## 2. Newtrino Configuration

### 2.1. Differences Between PHP and JS Versions

The system part (Core) of Newtrino is made of PHP, but either the JS or PHP version can be used for the display side.

The PHP version runs on the server side, so it communicates directly with the Core to retrieve post data, which is then used to assemble HTML on the server side and send it to the client.

The JS version runs on the browser side, so it retrieves post data from Core via Ajax, assembles HTML on the browser side, and displays it.

Since the JS version is browser-side assembly of the article page, meta information (title and description) can be handled without problems when viewed in the browser, but may not be reflected when accessed by crawlers. Specifically, Google crawlers recognize and execute JavaScript, so there is no problem, but Facebook and Twitter crawlers do not execute JavaScript, so titles and descriptions are not reflected.

### 2.2. System Files

The files and directories that make up Newtrino are as follows. When incorporated into a website, a complete set will be included in some specific directory (e.g. `nt/`) (see sample). This directory is called the Newtrino directory.

- `admin/`
- `core/`
- `index.php`
- `index.min.is`
- `index.min.is.map`

The `admin/` directory contains scripts to log in to the Newtrino administration page and edit articles.

| **Point**                                                                                                                                                                                                     |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| If you do not need to use the edit function, for example, when you have been operating with Newtrino built in and no longer need to update it, you can delete the `admin` directory and operate as read-only. |

The `core/` directory, on the other hand, contains the functions involved in the display. Literally, it is the core of Newtrino.

Files beginning with `index` contain functions that are called from each page when displaying a list of articles or the articles themselves. The scripts in the `core/` directory are called from those functions. So this is the entry point for using Newtrino as a library.

Only this entry point is accessed from the display side in both the PHP and JS versions.

### 2.3. Files Different for Websites

The Newtrino directory contains several directories besides `core/` and `admin/`.

The first mandatory directory is the `data/` directory, which contains files related to Newtrino configuration. This directory contains account information, editing screen styles, and definitions of post types and taxonomies.

There are also directories that hold post data for each post type. The sample defines a post type `post`, so when you create a post, the `post/` directory will contain that data.

### 2.4. How to Update

By replacing the `admin/` and `core/` directories in the Newtrino directory of the existing site with the `dist/admin/` and `dist/core/` directories from the new Newtrino repository build. The update is complete.

| **Point**                                                                                                                                                                                                                                          |
| -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| If your Newtrino website is managed in a repository or used in combination with a static site generator, etc., there may be a separate means of upgrading Newtrino. In that case, please use the provided method instead of replacing it directly. |


## 3. Integrating Newtrino into a Website

As an example of how Newtrino can be integrated into a website, three separate cases are described: a front page that displays a certain number of the new-arrival articles, a list page that displays a list of articles, and an individual page that displays the articles themselves.

### 3.1. Displaying Latest Articles on the Front Page

Since the front page will contain much more than just new-arrival articles, let's create a JS version of the page.

First, write the following in the header and load the necessary scripts. `mustache.min.js` is a template library called Mustache, and `luxon.min.js` is a library that provides functions for formatting time and date. `... /nt/index.min.js` is a library that serves as the entry point for Newtrino, written as a URL relative to the Newtrino directory.

```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/mustache.js/4.0.1/mustache.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@2.3.1/build/global/luxon.min.js"></script>
<script src="../nt/index.min.js"></script>
```

In the `<script>` tag in the header, write the following to retrieve the post data. Here we are retrieving two posts with a post type of `post`. The first argument `'. /nt/'` is the URL of the Ajax API, i.e. the URL of the Newtrino directory.

The second argument is a callback function to be called when the data is retrieved. Here we are calling it via Promise, so by writing it like this, the retrieved data will be assigned to the constant `vp`.

The third argument is optional and sets various parameters such as the number of posts, the URL base of the post, the query, etc.

```js
    document.addEventListener('DOMContentLoaded', async () => {
        const vp = await new Promise(res => NT.queryRecentPosts('../nt/', view => res(view), { count: 2, base_url: './topic/', query: { type: 'post' }, option: { date_format: 'yyyy-MM-dd' } }));
        NT.renderTemplate('#post[type="text/html"]', vp);
    });
```

The retrieved post data is rendered in Mustache by `NT.renderTemplate('#post[type="text/html"]', vp);`. The first argument is a selector to get the `script` element representing the template.

The template will look like this:

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

The submission data retrieved by the `NT.queryRecentPosts()` function contains some auxiliary content in addition to the submission data.

Since the post data is the value (array) of the key `posts` of the object, the interval between `{{#posts}}` and `{{/posts}}` is expanded for each post data one by one.Here we have `<li>` to `</li>`, so each post is rendered as a list element.

Each post data will have the following data:

| Key        | Value                                                                                                                       |
| ---------- | --------------------------------------------------------------------------------------------------------------------------- |
| `url`      | URL, where the post ID is concatenated with the optional `base_url` of `queryRecentPosts()` as a query parameter.           |
| `title`    | Title.                                                                                                                      |
| `excerpt`  | Excerpt.                                                                                                                    |
| `date`     | Date. Formatted in the format specified by `date_format`.                                                                   |
| `taxonomy` | Taxonomy. Each taxonomy has an array of terms.                                                                              |
| `meta`     | Meta data. Each key has an array of values.                                                                                 |
| `class`    | An array of class notations for post status and post type. `class@joined` is a string where `class` are joined with spaces. |

### 3.2. Display the List of Articles on the List Page

Let's write the article list page in PHP. The list page uses the `query()` function instead of the `query_recent_posts()` function (the JS version is the `queryRecentPosts()` function). The arguments are the same. The point is that data are passed with `filter` as a key and also get information for filtering.

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

Once the data is retrieved, it is rendered by Mustache, the PHP version of Mustache is a template between the `begin()` and `end()` functions. The first argument of the `begin()` function is the data and the second argument is the condition to render (if `true`, render).

First render the filter section. Here we have the date (year), taxonomy (`category`) selection, and a free search field.

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

Next is the list of articles. This is almost the same as the part of rendering new-arrival posts. The `posts` is used as a key and each element of the array is rendered as an element of the list.

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

Another essential element of a listing page is pagination. This one, too, is rendered by the view obtained as the return value of the `query()` function, which contains the necessary information.

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

### 3.3. display Article Content on Individual Pages

Let's write the individual pages in the PHP version as well. It is the same up to the point where the view is retrieved. The information of one post is returned as the value of the `post` key of the view.

The difference is the meta information in the `<head>` element. The meta information is also rendered using the `url`, `title`, etc. in the view's `post` key. This meta information is also reflected when shared on social networking sites. This is the advantage of using the PHP version.

This is because the PHP version is rendered on the server, so the meta information is already in the HTML that the browser or SNS crawler retrieves.

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

The display of article content is not that different from the list view, except that it is rendered based on the information in the view's `post` key.

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

The post page will usually contain buttons to go to the next or previous post, so let's render that as well. The view will contain information for this in the `navigation` key.

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

| **Point**                                                                                                                                                                                                                                                                  |
| -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| The same `query()` function is used to retrieve data for both the list page and the individual pages. So how does it distinguish which page is which?                                                                                                                      |
| Newtrino determines which page to display by the query parameter. The `query()` function determines which view to return based on the various information passed as arguments and the current query parameters. This is why the same function can display different views. |
 
## 4. Customize

### 4.1. Taxonomy Definition

Taxonomies are defined in `data/taxonomy.json`. Note that there is no built-in taxonomy.

The root element of `data/taxonomy.json` is an array of taxonomies. The elements of the array are objects for each taxonomy, including slugs, labels, and definitions for each term. The `data/taxonomy.json` in the sample looks like this:

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

The per-taxonomy object contains the following data:

| Key            | Value                                                                                                  |
| -------------- | ------------------------------------------------------------------------------------------------------ |
| `slug`         | A string representing the taxonomy slug. Used for query parameters.                                    | `label` | A string representing the taxonomy label. |
| `label`        | A string representing the taxonomy label.                                                              |
| `label@ja`     | A string representing the per-language label for the taxonomy. In this case, it means `ja` (Japanese). |
| `sg_label`     | A string representing the label for the singular form of the taxonomy.                                 |
| `is_exclusive` | Exclusive or not. `true` or `false`.                                                                   |
| `terms`        | An array of term definitions.                                                                          |

An array of term definitions, `terms`, contains objects for each term. The object per term contains the following data:

| Key        | Value                                                                                                  |
| ---------- | ------------------------------------------------------------------------------------------------------ |
| `slug`     | A string representing the slug of the term. Used for query parameters.                                 | `label` | A string representing the label of the term. |
| `label`    | A string representing the label of the term.                                                           |
| `label@ja` | A string representing the label for each language of the term. In this case, it means `ja` (Japanese). |

### 4.2. Defining Posting Types

Posting types are defined in the `data/type.json` file. As with taxonomy, there is no built-in type here, so one must always be defined.

The root element of the `data/type.json` file is an array of post types. The elements of the array are objects for each post type and contain definitions of slugs, labels, etc.

The `data/type.json` in the sample looks like this. Here, two post types are defined: `post` and `event`.

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

The object representing each submission type has the following information:

| Key        | Value                                                                                                       |
| ---------- | ----------------------------------------------------------------------------------------------------------- |
| `slug`     | A string representing the slug of the post type. Used for query parameters.                                 |
| `label`    | A string representing the label of the post type.                                                           |
| `label@ja` | A string representing the label for each language of the post type. In this case, it means `ja` (Japanese). |
| `taxonomy` | An array of taxonomies to be associated. Specifies the taxonomy slug.                                       |
| `meta`     | An array of meta information.                                                                               |

An array representing a meta-field, whose elements represent the field, has the following information:

| Key              | Value                                                                                                                                       |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| `key`            | A string representing the key of the field.                                                                                                 |
| `type`           | A string representing the type of the field. It can be one of `text`, `checkbox`, `date`, `date_range`, `media`, `media_image`, or `group`. |
| `label`          | A string representing the label of the field.                                                                                               |
| `label@ja`       | A string representing the label for each language of the field. In this case, it means `ja` (Japanese).                                     |
| `do_show_column` | A boolean value indicating whether the field is shown as a column in the list view of the admin page. Default is `false`.                   |
| `option`         | An option for each type.                                                                                                                    |

When the type is `group`, the `items` can be an array of objects representing the field information. This allows you to organize the display of fields on the administration screen.

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

The `option` can have the following settings for each type:

#### `media_image`
| Key    | Value                            |
| ------ | -------------------------------- |
| `size` | Image size. Default is `medium`. |

### 4.3. Config Files

There are two types of files for site-wide configuration. The first is `data/config.json`. The sample contents and each setting are as follows:

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

| Key                  | Value                                                                                                                   |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| `timezone`           | A string representing the time zone. Default is `Asia/Tokyo`.                                                           |
| `lang`               | A String representing the language. Default is `en`.                                                                    |
| `lang_admin`         | A string representing the language of the admin screen. Default is `en`.                                                |
| `per_page`           | An integer representing the number of posts per page in the archive view. Default is `10`.                              |
| `new_arrival_period` | An integer representing the number of days from the current date to be treated as new-arrival articles. Default is `7`. |
| `date_format`        | A string representing the date format, specifying the format accepted by PHP's `date` function. Default is `Y-m-d`.     |
| `archive_by_year`    | A boolean value indicating whether the directory where posts are stored should be separated by year. Default is `true`. |
| `archive_by_type`    | A boolean indicating whether or not to separate the directory where posts are stored by post type. Default is `true`.   |
| `image_sizes`        | Image Sizes.                                                                                                            |

The other configuration file is `data/config.php`. This is the file that is read (and executed) normally. It defines the following constants. The meanings of each of them are as follows:

#### `data/config.php`
| Key            | Value                                                                                                                                |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| `NT_MODE_DIR`  | An octal number representing the permissions on the directories created by NT. The default is `0770`.                                |
| `NT_MODE_FILE` | An octal number representing the permissions on the files created by NT. The default is `0660`.                                      |
| `NT_DEBUG`     | A boolean value indicating whether or not to go into debug mode. If `true`, all notifications will be displayed. Default is `false`. |
| `NT_AUTH_KEY`  | A string representing the authentication key. The key is output to the HTML of the login screen. Default is `newtrino`.              |

### 4.4. Customizing the Administration Screen

To change the style of the editor, edit `data/editor.css`. Similarly, to change the style of the preview screen, edit `data/preview.css`.

To specify options for the editor (TinyMCE), change `data/editor.json`. If you prepare a file with a name like `editor.ja.json`, you can switch the file to be loaded depending on the language of the admin page (for example, `editor.ja.json` is loaded when the language is `ja`).

If you want to change other settings, for example, you can prepare `editor.js`, which will be loaded on the edit screen. If you specify a language, it will be loaded first. Also, if there is a minified file, it will be given priority. For example, `editor.ja.min.js`, `editor.ja.js`, `editor.min.js`, and `editor.js` will be searched in this order and loaded if found.

### 4.5. Multilingualization

Any taxonomy, term, post type, etc. that specifies a label (`label`) can be set to the label for that language by joining the atmark and the language tag to the key, such as `label@ja` (here specifying Japanese `ja`).

As for the configuration file, you can also set a configuration file for a language by inserting a language tag between the file name extension and the name, such as `editor.ja.json` (in this case, specifying Japanese `ja`).

In either case, when there is no need to make the file multilingual, just set the label without the language tag and prepare the file, and it will be used.


## 5. How to Write a Query

The query to retrieve posts is passed as a `query` parameter to either the `\nt\query()` function or the `\nt\query_recent_posts()` function for the PHP version, or the `NT.query()` function or the `NT.queryRecentPosts ()` function for the Javascript Version.

For example, in the example below, as the first query, we retrieve for the number of posts `-1` (all), post type `post`, and as the meta query, having a field whose key is `sticky`. And as a second query, we retrieve for the number of posts `10`, post type `post`, and as a meta query, with the condition that there are no fields with the key `sticky`.

If multiple queries are specified, the retrieves are performed in order and the results of them are merged with the first result taking precedence.

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

### 5.1. Query Types

The basic query parameters are as follows:

| Key          | Value                                                                                                |
| ------------ | ---------------------------------------------------------------------------------------------------- |
| `id`         | An integer representing the post ID.                                                                 |
| `page`       | An integer representing the page at paging time. Default is 1.                                       |
| `per_page`   | An integer representing the number of posts per page at paging. Default is according to config.json. |
| `type`       | A string representing the post type.                                                                 |
| `status`     | A string representing the post status.                                                               |
| `search`     | A string representing the search string.                                                             |
| `tax_query`  | An array or object representing the taxonomy query.                                                  |
| `date_query` | An array or object representing a date query.                                                        |
| `meta_query` | An array or object representing a meta query.                                                        |

The taxonomy query array contains several objects with the following contents:

| Key      | Value                                                    |
| -------- | -------------------------------------------------------- |
| taxonomy | A string representing the taxonomy.                      |
| terms    | A string or array of strings representing the term slug. |

If the query contains more than one object, it is also possible to specify the relationship between them. The array set in `tax_query` can have the key `relation` as `AND` or `OR`; the JavaScript version does not allow keys to be specified for arrays, so the entire array is an object, and the objects representing the conditions are set as numeric keys starting with key `0`.

A date query can contain multiple objects with the following contents As with taxonomy queries, you can also specify a `relation`.

#### Direct Specification

| Key     | Value                                         | Note                                                       |
| ------- | --------------------------------------------- | ---------------------------------------------------------- |
| `year`  | A number representing the year.               | Valid only if both `before` and `after` are not specified. |
| `month` | A Number representing the month.              | Same as above                                              |
| `day`   | A number representing the day.                | Same as above                                              |
| `date`  | A 4- to 8-digit number representing the date. | Same as above                                              |

#### Range Specification

| Key      | Value                                                  | Note                                                                                                                                                          |
| -------- | ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `before` | An object representing the starting date of the range. | An object representing a date is an object with values as keys `year`, `month`, `day`, and `date`. Both `before` and `after` dates are included in the range. |
| `after`  | An object representing the end date of the range.      | Same as above                                                                                                                                                 |

A meta query can contain multiple objects with the following contents. As with taxonomy queries, you can also specify a `relation`.

| Key       | Value                                 |
| --------- | ------------------------------------- |
| `key`     | A string representing the meta key.   |
| `type`    | Type of meta information.             |
| `val`     | Meta value.                           |
| `compare` | A string representing how to compare. |

### 5.2. Composite Query

The `NT.query()` and `NT.queryRecentPosts()` functions in the PHP version and the `NT.query()` and `NT.queryRecentPosts()` functions in the JavaScript version combine multiple queries by passing multiple queries as the `query` parameter.

If multiple queries are specified, the retrieves are performed in order, and posts not included in the previous retrieve results are added to the retrieve results.


## 6. API Reference

### 6.1. APIs in PHP

```php
query( array $args = [] ): array { ... }
query_recent_posts( array $args = [] ): array { ... }
```

### 6.2. APIs in JS

```js
function query(url, callback, args = {}) { ... }
function queryRecentPosts(url, callback, args = {}) { ... }
```

### 6.3. How to use Mustache

#### Variables

```
{{key}}
```

Replace with the value received in `key`.

#### Section

```
{{#key}} ~ {{/key}}
```

If the value received at `key` is neither `false` nor an empty list, the contents of `#key` and `/key` are rendered. If the value received at `key` is a non-empty list, the contents of the list are enumerated.

#### Invert Sections

```
{{^key}} ~ {{/key}}
```

If the value received for `key` is `false` or an empty list, the contents of `^key` and `/key` are rendered.
