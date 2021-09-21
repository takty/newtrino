<?php
require_once( __DIR__ . '/../nt/index.php' );
$view_post = \nt\query_recent_posts(
	[
		'base_url' => './topic/',
		'query'    => [
			[
				'per_page'   => -1,
				'type'       => 'post',
				'meta_query' => [ [ 'key' => 'sticky' ] ]
			],
			[
				'per_page'   => 10,
				'type'       => 'post',
				'meta_query' => [ [ 'key' => 'sticky', 'compare' => 'not exist' ] ]
			],
		],
		'option'   => [ 'date_format' => 'Y-m-d' ],
	]
);
$view_event = \nt\query_recent_posts( [ 'count' => 2, 'base_url' => './topic/', 'query' => [ 'type' => 'event' ], 'option' => [ 'date_format' => 'Y-m-d' ] ] );
header( 'Content-Type: text/html;charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Newtrino Sample</title>
</head>
<body>
	<header>
		<h1>Newtrino Sample</h1>
	</header>
	<main>
		<div>
			<h2>Posts</h2>
			<?php \nt\begin(); ?>
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
			<?php \nt\end( $view_post ); ?>
		</div>
		<div>
			<h2>Events</h2>
			<?php \nt\begin(); ?>
				<ul id="list-item-event">
{{#posts}}
					<li class="{{class@joined}}">
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
			<?php \nt\end( $view_event ); ?>
		</div>
		<nav>
			<a href="topic/">Show More...</a>
		</nav>
	</main>
</body>
</html>
