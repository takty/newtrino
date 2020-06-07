<?php
require_once( __DIR__ . '/../nt/core/view.php' );
$view = \nt\query();
header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>
	<?php \nt\begin(); ?>{{post.title}} - <?php \nt\end( $view, isset( $view['post'] ) ); ?>
	Newtrino Sample
</title>
</head>
<body>
	<header>
		<h1><a href="../">Newtrino Sample</a></h1>
	</header>
	<main>
<!-- ======================================================================= -->
		<header class="entry-header">
			<?php \nt\begin(); ?>
				<h2>Topics</h2>
			<?php \nt\end( $view, isset( $view['posts'] ) ); ?>
<!-- ----------------------------------------------------------------------- -->
			<?php \nt\begin(); ?>
{{#post}}
				{{#taxonomy.category}}
				<div class="category">{{label}}</div>
				{{/taxonomy.category}}
				<h2>{{title}}</h2>
				{{#taxonomy.$category.event}}
				<span class="event-term {{meta.event_state}}">
					Event Date: {{meta.event_date_bgn}} to {{meta.event_date_end}}
				</span>
				{{/taxonomy.$category.event}}
				{{^taxonomy.$category.event}}
				<div class="date">{{date}}</div>
				{{/taxonomy.$category.event}}
{{/post}}
			<?php \nt\end( $view, isset( $view['post'] ) ); ?>
		</header>
<!-- ======================================================================= -->
		<div class="aside aside-filter">
			<?php \nt\begin(); ?>
				<div class="filter-date">
{{#filter.date}}
					<select onchange="document.location.href = this.value;">
						<option value="./">Month</option>
						{{#month}}<option value="{{url}}" {{#current}}selected{{/current}}>{{label}}</option>{{/month}}
					</select>
{{/filter.date}}
				</div>
				<div class="filter-taxonomy">
{{#filter.taxonomy}}
					<select onchange="document.location.href = this.value;">
						<option value="./">Category</option>
						{{#category}}<option value="{{url}}" {{#current}}selected{{/current}}>{{label}}</option>{{/category}}
					</select>
{{/filter.taxonomy}}
				</div>
				<div class="filter-search">
{{#filter.search}}
					<form action="./" type="GET">
						<input type="text" name="search" value="{{keyword}}">
						<input type="submit" value="Search">
					</form>
{{/filter.search}}
				</div>
			<?php \nt\end( $view, isset( $view['posts'] ) ); ?>
		</div>
<!-- ======================================================================= -->
		<div class="entry-content">
			<?php \nt\begin(); ?>
				<ul id="list-item-post">
{{#posts}}
					<li class="{{status}}" id="temp-item-post">
						<a href="{{url}}">
							{{#taxonomy.category}}
							<span class="category">{{label}}</span>
							{{/taxonomy.category}}
							{{#taxonomy.$category.event}}
							<span class="event-date">Event Date: {{meta.event_date_bgn}} to {{meta.event_date_end}}</span>
							{{/taxonomy.$category.event}}
							<div class="title">{{title}}</div>
							<div class="excerpt">{{excerpt}}</div>
							<div class="date">{{date}}</div>
						</a>
					</li>
{{/posts}}
				</ul>
			<?php \nt\end( $view, isset( $view['posts'] ) ); ?>
<!-- ----------------------------------------------------------------------- -->
			<?php \nt\begin(); ?>
				{{&post.content}}
			<?php \nt\end( $view, isset( $view['post'] ) ); ?>
		</div>
<!-- ======================================================================= -->
		<div class="aside aside-navigation">
			<?php \nt\begin(); ?>
				<div class="post_navigation">
{{#navigation.post_navigation}}
					{{#previous}}<a href="{{url}}">Previous</a>{{/previous}}
					{{#next}}<a href="{{url}}">Next</a>{{/next}}
{{/navigation.post_navigation}}
				</div>
			<?php \nt\end( $view, isset( $view['navigation']['post_navigation'] ) ); ?>
<!-- ----------------------------------------------------------------------- -->
			<?php \nt\begin(); ?>
				<div class="pagination">
{{#navigation.pagination}}
					<a href="{{previous}}">Previous</a>
					<select onchange="document.location.href = this.value;">
						{{#pages}}<option value="{{url}}" {{#current}}selected{{/current}}>{{label}}</option>{{/pages}}
					</select>
					<a href="{{next}}">Next</a>
{{/navigation.pagination}}
				</div>
			<?php \nt\end( $view, isset( $view['navigation']['pagination'] ) ); ?>
		</div>
<!-- ======================================================================= -->
	</main>
</body>
</html>
