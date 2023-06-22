<?php
/**
 * List
 *
 * @author Takuto Yanagida
 * @version 2023-06-22
 */

namespace nt;

require_once( __DIR__ . '/handler-list.php' );
$view = handle_query_list();

header( 'Content-Type: text/html;charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" href="css/logo.png">
<link rel="apple-touch-icon" type="image/png" href="css/logo-180x180.png">
<link rel="stylesheet" href="css/reset.min.css">
<link rel="stylesheet" href="<?php tqs( __DIR__, 'css/style.min.css' ); ?>">
<script src="<?php tqs( __DIR__, 'js/list.min.js' ); ?>"></script>
<title><?= _ht( 'Post List' ); ?> - Newtrino</title>
</head>
<body class="list">

<div class="site">
	<header class="site-header">
		<div class="row">
			<h1 class="site-title">Newtrino</h1>

			<form action="login.php" method="post">
				<button><?= _ht( 'Log Out' ); ?></button>
				<input type="hidden" name="mode" value="logout">
			</form>
		</div>
<?php \nt\begin( $view, ! empty( $view['ntc'] ) ); ?>
		<p class="notice">{{ntc}}</p>
<?php \nt\end(); ?>
	</header>

	<main class="site-content">
		<div class="column list">
			<nav class="column-sub frame frame-compact">
<?php \nt\begin( $view, ! empty( $view['filter']['type'] ) ); ?>
				<div>
					<div class="heading"><?= _ht( 'Post Type' ); ?></div>
					<label class="select">
						<select class="do-navigate">
{{#filter.type}}
							<option value="{{url}}"{{#is_selected}} selected{{/is_selected}}>{{label}}</option>
{{/filter.type}}
						</select>
					</label>
				</div>
<?php \nt\end(); ?>

<?php \nt\begin( $view ); ?>
				<div>
					<div class="heading"><?= _ht( 'Display Month' ); ?></div>
{{#filter.date}}
					<label class="select">
						<select class="do-navigate">
{{#month}}
							<option value="{{url}}"{{#is_selected}} selected{{/is_selected}}>{{label}}</option>
{{/month}}
						</select>
					</label>
{{/filter.date}}
				</div>

				<div>
					<div class="heading"><?= _ht( 'View Count' ); ?></div>
					<label class="select">
						<select class="do-navigate">
{{#filter.per_page}}
							<option value="{{url}}"{{#is_selected}} selected{{/is_selected}}>{{label}}</option>
{{/filter.per_page}}
						</select>
					</label>
				</div>

				<hr>

				<div class="button-row grow end">
					<label class="select accent">
						<select id="sel-new-post" class="accent do-navigate nc">
							<option value="#"><?= _ht( "New Post" ); ?></option>
{{#filter.new}}
							<option value="{{url}}">{{label}}</option>
{{/filter.new}}
						</select>
					</label>
				</div>
<?php \nt\end(); ?>
			</nav>

			<div class="column-main frame">
<?php \nt\begin( $view, isset( $view['taxonomy@cancels'] ) ); ?>
				<ul class="list-taxonomy-cancel">
{{#taxonomy@cancels}}
					<li><button type="button" class="tag cross do-navigate" data-href="{{url}}">{{label}}</button></li>
{{/taxonomy@cancels}}
				</ul>
<?php \nt\end(); ?>
<?php \nt\begin( $view ); ?>
				<table class="list-item">
					<thead>
						<tr>
							<th><?= _ht( 'Status' ); ?></th>
							<th class="title"><?= _ht( 'Title' ); ?></th>
{{#meta@cols}}
							<th>{{label}}</th>
{{/meta@cols}}
{{#taxonomy@cols}}
							<th>{{label}}</th>
{{/taxonomy@cols}}
							<th><?= _ht( 'Date' ); ?></th>
							<th></th>
						</tr>
					</thead>

					<tbody>
{{#posts}}
						<tr>
							<td>
{{#trash}}
								<button type="button" class="restore do-restore-post" data-href="{{restore}}"><?= _ht( 'Restore' ); ?></button>
{{/trash}}
{{^trash}}
								<label class="select">
									<select class="post-status" data-id="{{id}}">
{{#status@select}}
										<option value="{{slug}}"{{#is_selected}} selected{{/is_selected}}>{{label}}</option>
{{/status@select}}
									</select>
								</label>
{{/trash}}
							</td>
							<td class="title"><a data-href="{{url}}" class="title do-navigate nc">{{title}}</a></td>
{{#meta@cols}}
							<td class="meta meta-type-{{type}}"><div>{{{_label}}}</div></td>
{{/meta@cols}}
{{#taxonomy@cols}}
							<td class="taxonomy {{taxonomy}}">
								<div>
{{#terms}}
									<a href="{{url}}">{{label}}</a>
{{/terms}}
								</div>
							</td>
{{/taxonomy@cols}}
							<td class="date"><span>{{date@sep.0}}</span><span>{{date@sep.1}}</span></td>
{{#trash}}
							<td><button class="delete mini cross do-remove-post" data-href="{{url_remove}}"></button></td>
{{/trash}}
{{^trash}}
							<td><button class="trash mini cross do-remove-post" data-href="{{url_remove}}"></button></td>
{{/trash}}
						</tr>
{{/posts}}
					</tbody>
				</table>
<?php \nt\end(); ?>

<?php \nt\begin( $view ); ?>
{{#pagination}}
				<div class="pagination">
					<button type="button"{{#previous}} data-href="{{.}}"{{/previous}}{{^previous}} disabled{{/previous}} class="do-navigate"><?= _ht( 'New' ); ?></button>
					<label class="select">
						<select class="do-navigate">
							{{#pages}}<option value="{{url}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>{{/pages}}
						</select>
					</label>
					<button type="button"{{#next}} data-href="{{.}}"{{/next}}{{^next}} disabled{{/next}} class="do-navigate"><?= _ht( 'Old' ); ?></button>
				</div>
{{/pagination}}
				<div class="button-row">
					<button type="button" data-href="{{list_all}}" class="tag do-navigate"><?= _ht( "All" ); ?></button>
					<button type="button" data-href="{{list_trash}}" class="tag do-navigate"><?= _ht( "Trash" ); ?></button>
					<button type="button" data-href="{{empty_trash}}" class="tag delete" id="do-empty-trash"{{^posts}} disabled{{/posts}}><?= _ht( "Empty Trash" ); ?></button>
				</div>
<?php \nt\end(); ?>
			</div>
		</div>
	</main>
</div>

<?php \nt\begin( $view ); ?>
<input type="hidden" id="nonce" value="{{nonce}}">
<input type="hidden" id="ntc-trash" value="<?= _ht( 'Do you want to move the post to trash?' ); ?>">
<input type="hidden" id="ntc-del-per" value="<?= _ht( 'Do you want to delete the post permanently?' ); ?>">
<input type="hidden" id="ntc-empty-trash" value="<?= _ht( 'Do you want to empty trash?' ); ?>">
<?php \nt\end(); ?>

</body>
</html>
