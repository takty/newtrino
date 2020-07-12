<?php
namespace nt;
/**
 *
 * List
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-12
 *
 */


require_once(__DIR__ . '/view-admin.php');
$view = query();


header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= _ht('Post List') ?> - Newtrino</title>
<link rel="stylesheet" href="css/style.min.css">
<script src="js/list.min.js"></script>
</head>
<body class='list'>
<header class="header">
	<h1>Newtrino</h1>
	<a href="login.php" class="button"><?= _ht( 'Log Out' ) ?></a>
</header>

<div class="container">
	<nav class="frame frame-filter">
<?php \nt\begin(); ?>
		<div>
			<h3><?= _ht( 'Post Type' ) ?></h3>
			<select onchange="document.location.href = this.value;">
{{#filter.type}}
				<option value="{{url}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>
{{/filter.type}}
			</select>
		</div>
<?php \nt\end( $view, ! empty( $view['filter']['type'] ) ); ?>
<?php \nt\begin(); ?>
		<div>
			<h3><?= _ht( 'Display Month' ) ?></h3>
			<div class="filter-month-wrapper">
{{#filter.date}}
				<select onchange="document.location.href = this.value;">
{{#month}}
					<option value="{{url}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>
{{/month}}
				</select>
{{/filter.date}}
			</div>
		</div>
		<div>
			<h3><?= _ht( 'View Count' ) ?></h3>
			<select onchange="document.location.href = this.value;">
{{#filter.per_page}}
				<option value="{{url}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>
{{/filter.per_page}}
			</select>
		</div>
		<div class="button-row">
			<select class="accent" onchange="document.location.href = this.value;">
				<option value="#"><?= _ht( "New Post" ) ?></option>
{{#filter.new}}
				<option value="{{url}}">{{label}}</option>
{{/filter.new}}
			</select>
		</div>
<?php \nt\end( $view ); ?>
	</nav>

	<div class="frame frame-main">
<?php \nt\begin(); ?>
		<ul class="list-taxonomy-cancel">
{{#taxonomy@cancels}}
			<li><a href="{{url}}">{{label}}</a></li>
{{/taxonomy@cancels}}
		</ul>
<?php \nt\end( $view, isset( $view['taxonomy@cancels'] ) ); ?>
<?php \nt\begin(); ?>
		<table class="list-item">
			<tr>
				<th><?= _ht( 'Status' ) ?></th>
				<th><?= _ht( 'Title' ) ?></th>
{{#taxonomy@cols}}
				<th>{{label}}</th>
{{/taxonomy@cols}}
				<th><?= _ht( 'Date' ) ?></th>
				<th></th>
			</tr>
{{#posts}}
			<tr data-id="{{id}}">
				<td>
					<select class="post-status">
{{#status@select}}
						<option value="{{slug}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>
{{/status@select}}
					</select>
				</td>
				<td><a href="{{url}}" class="title">{{title}}</a></td>
{{#taxonomy@cols}}
				<td class="taxonomy {{taxonomy}}"><div>
{{#terms}}
				<a href="{{url}}">{{label}}</a>
{{/terms}}
				</div></td>
{{/taxonomy@cols}}
				<td class="date">{{date}}</td>
				<td><button class="delete mini cross delete-post" data-href="{{delete}}"></button></td>
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
<?php \nt\end( $view ); ?>
	</div>

	<input id="del-msg" type="hidden" value="<?= _ht( 'Do you want to delete the post?' ) ?>">

</div>
</body>
</html>
