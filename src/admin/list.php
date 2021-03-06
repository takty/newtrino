<?php
namespace nt;
/**
 *
 * List
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-08-04
 *
 */


require_once( __DIR__ . '/handler-list.php' );
$view = handle_query();


header( 'Content-Type: text/html;charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="css/reset.min.css">
<link rel="stylesheet" href="css/style.min.css">
<script src="js/list.min.js"></script>
<title><?= _ht( 'Post List' ) ?> - Newtrino</title>
</head>
<body class='list'>
<header class="header">
	<div class="inner">
		<h1>Newtrino</h1>
		<span class="spacer"></span>
		<a href="login.php" class="button"><?= _ht( 'Log Out' ) ?></a>
	</div>
</header>

<div class="container">
	<nav class="container-sub frame frame-compact">
<?php \nt\begin(); ?>
		<div>
			<div class="heading"><?= _ht( 'Post Type' ) ?></div>
			<select onchange="document.location.href = this.value;">
{{#filter.type}}
				<option value="{{url}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>
{{/filter.type}}
			</select>
		</div>
<?php \nt\end( $view, ! empty( $view['filter']['type'] ) ); ?>
<?php \nt\begin(); ?>
		<div>
			<div class="heading"><?= _ht( 'Display Month' ) ?></div>
{{#filter.date}}
			<select onchange="document.location.href = this.value;">
{{#month}}
				<option value="{{url}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>
{{/month}}
			</select>
{{/filter.date}}
		</div>
		<div>
			<div class="heading"><?= _ht( 'View Count' ) ?></div>
			<select onchange="document.location.href = this.value;">
{{#filter.per_page}}
				<option value="{{url}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>
{{/filter.per_page}}
			</select>
		</div>
		<div class="button-row right">
			<select id="sel-new-post" class="accent" onchange="document.location.href = this.value;">
				<option value="#"><?= _ht( "New Post" ) ?></option>
{{#filter.new}}
				<option value="{{url}}">{{label}}</option>
{{/filter.new}}
			</select>
		</div>
<?php \nt\end( $view ); ?>
	</nav>

	<div class="container-main frame">
<?php \nt\begin(); ?>
		<ul class="list-taxonomy-cancel">
{{#taxonomy@cancels}}
			<li><a class="button tag cross" href="{{url}}">{{label}}</a></li>
{{/taxonomy@cancels}}
		</ul>
<?php \nt\end( $view, isset( $view['taxonomy@cancels'] ) ); ?>
<?php \nt\begin(); ?>
		<table class="list-item">
			<tr>
				<th><?= _ht( 'Status' ) ?></th>
				<th class="title"><?= _ht( 'Title' ) ?></th>
{{#meta@cols}}
				<th>{{label}}</th>
{{/meta@cols}}
{{#taxonomy@cols}}
				<th>{{label}}</th>
{{/taxonomy@cols}}
				<th><?= _ht( 'Date' ) ?></th>
				<th></th>
			</tr>
{{#posts}}
			<tr data-id="{{id}}">
				<td>
{{#trash}}
					<button class="restore restore-post" data-href="{{restore}}"><?= _ht( 'Restore' ) ?></button>
{{/trash}}
{{^trash}}
					<select class="post-status">
{{#status@select}}
						<option value="{{slug}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>
{{/status@select}}
					</select>
{{/trash}}
				</td>
				<td class="title"><a href="{{url}}" class="title">{{title}}</a></td>
{{#meta@cols}}
				<td class="meta meta-type-{{type}}"><div>{{{_label}}}</div></td>
{{/meta@cols}}
{{#taxonomy@cols}}
				<td class="taxonomy {{taxonomy}}"><div>
{{#terms}}
				<a href="{{url}}">{{label}}</a>
{{/terms}}
				</div></td>
{{/taxonomy@cols}}
				<td class="date"><span>{{date@sep.0}}</span><span>{{date@sep.1}}</span></td>
{{#trash}}
				<td><button class="delper mini cross remove-post" data-href="{{url_remove}}"></button></td>
{{/trash}}
{{^trash}}
				<td><button class="trash mini cross remove-post" data-href="{{url_remove}}"></button></td>
{{/trash}}
			</tr>
{{/posts}}
		</table>
<?php \nt\end( $view ); ?>

<?php \nt\begin(); ?>
{{#pagination}}
		<div class="pagination">
			{{#previous}}<a class="button" href="{{.}}"><?= _ht( 'New' ) ?></a>{{/previous}}
			<select onchange="document.location.href = this.value;">
				{{#pages}}<option value="{{url}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>{{/pages}}
			</select>
			{{#next}}<a class="button" href="{{.}}"><?= _ht( 'Old' ) ?></a>{{/next}}
		</div>
{{/pagination}}
		<div class="button-row buttom">
			<a href="{{list_all}}" class="button tag"><?= _ht( "All" ) ?></a>
			<a href="{{list_trash}}" class="button tag"><?= _ht( "Trash" ) ?></a>
			<a id="btn-empty-trash" data-href="{{empty_trash}}" class="button tag right delete"><?= _ht( "Empty Trash" ) ?></a>
		</div>
<?php \nt\end( $view ); ?>
	</div>

	<input id="message-trash" type="hidden" value="<?= _ht( 'Do you want to move the post to trash?' ) ?>">
	<input id="message-delete-permanently" type="hidden" value="<?= _ht( 'Do you want to delete the post permanently?' ) ?>">
	<input id="message-empty-trash" type="hidden" value="<?= _ht( 'Do you want to empty trash?' ) ?>">

</div>
</body>
</html>
