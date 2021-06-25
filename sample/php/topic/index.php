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
<?php \nt\begin(); ?>
<meta property="og:type" content="article">
<meta property="og:url" content="{{post.url}}">
<meta property="og:title" content="{{post.title}}">
<meta property="og:description" content="{{post.excerpt}}">
<meta property="og:site_name" content="Newtrino Sample">
{{#post.meta.thumbnail}}
<meta property="og:image" content="{{url}}">
{{/post.meta.thumbnail}}
<title>{{post.title}} - Newtrino Sample</title>
<?php \nt\end( $view, isset( $view['post'] ) ); ?>
<?php \nt\begin(); ?>
<title>Newtrino Sample</title>
<?php \nt\end( $view, ! isset( $view['post'] ) ); ?>
</head>
<body>
	<header>
		<h1><a href="../">Newtrino Sample</a></h1>
	</header>
<!-- ======================================================================= -->
<?php \nt\begin(); ?>
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
<?php \nt\end( $view, empty( $view['post'] ) ); ?>
<?php \nt\begin(); ?>
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
<?php \nt\end( $view, empty( $view['post'] ) ); ?>
<!-- ======================================================================= -->
<?php \nt\begin(); ?>
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
<?php \nt\end( $view, ! empty( $view['post'] ) ); ?>
<?php \nt\begin(); ?>
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
<?php \nt\end( $view, ! empty( $view['post'] ) ); ?>
<!-- ======================================================================= -->
</body>
</html>
