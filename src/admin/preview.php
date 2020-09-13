<?php
namespace nt;
/**
 *
 * Preview
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-31
 *
 */


require_once( __DIR__ . '/handler-preview.php' );
$view = handle_query( $_REQUEST );


header( 'Content-Type: text/html;charset=utf-8' );
?>
<?php \nt\begin(); ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
{{#css}}<link rel="stylesheet" media="all" href="{{.}}" />{{/css}}
{{#js}}<script src="{{.}}"></script>{{/js}}
<title><?= _ht( 'Preview' ) ?> - Newtrino</title>
</head>
<body class="preview">

<div class="container">
	<header class="entry-header">
		<h1>{{title}}</h1>
		<div class="date">{{date}}</div>
{{#taxonomies}}
		<ul class="taxonomy-{{taxonomy}}">
			{{#term_labels}}<li>{{.}}</li>{{/term_labels}}
		</ul>
{{/taxonomies}}
	</header>
	<main class="entry-content">
		{{{content}}}
	</main>
</div>

</body>
</html>
<?php \nt\end( $view ); ?>
