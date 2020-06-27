<?php
require_once( __DIR__ . '/nt/core/view.php' );
$view = \nt\query_recent_posts( [ 'count' => 2, 'base_url' => './topic/' ] );
header('Content-Type: text/html;charset=utf-8');
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
		<div class="section section-recent-posts">
			<?php \nt\begin(); ?>
				<ul id="list-item-post">
{{#posts}}
					<li class="{{status}}">
						<a href="{{url}}">
							{{#taxonomy.category}}
							<span class="category">{{label}}</span>
							{{/taxonomy.category}}
							{{#taxonomy.$category.event}}
							<span class="event-date">Event Date: {{meta.date_bgn}} to {{meta.date_end}}</span>
							{{/taxonomy.$category.event}}
							<div class="title">{{title}}</div>
							<div class="excerpt">{{excerpt}}</div>
							<div class="date">{{date}}</div>
						</a>
					</li>
{{/posts}}
				</ul>
			<?php \nt\end( $view ); ?>
		</div>
		<nav>
			<a href="topic/">Show More...</a>
		</nav>
	</main>
</body>
</html>
