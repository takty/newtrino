<?php
/**
 * Preview
 *
 * @author Takuto Yanagida
 * @version 2023-06-22
 */

namespace nt;

require_once( __DIR__ . '/handler-preview.php' );
$view = handle_query_preview( $_REQUEST );

header( 'Content-Type: text/html;charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php \nt\begin( $view ); ?>
{{#css}}<link rel="stylesheet" media="all" href="{{.}}" />{{/css}}
<script src="<?php tqs( __DIR__, 'js/preview.min.js' ); ?>"></script>
{{#js}}<script src="{{.}}"></script>{{/js}}
<?php \nt\end(); ?>
<title><?= _ht( 'Preview' ); ?> - Newtrino</title>
</head>
<body class="preview">

<main>
<?php \nt\begin( $view ); ?>
	<header class="entry-header">
		<h1 class="entry-title">{{title}}</h1>
		<div class="date">{{date}}</div>
{{#taxonomies}}
		<ul class="taxonomy-{{taxonomy}}">
{{#term_labels}}
			<li>{{.}}</li>
{{/term_labels}}
		</ul>
{{/taxonomies}}
	</header>

	<div class="entry-content">
		{{{content}}}
	</div>
<?php \nt\end(); ?>
</main>

</body>
</html>
