<?php
namespace nt;
/**
 *
 * List
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-08
 *
 */


require_once(__DIR__ . '/admin.php');


if ( $nt_q['mode'] === 'delete' ) $nt_store->delete($nt_q['id']);

$ppp = $nt_q['posts_per_page'];
$args = [
	'page' => $nt_q['page'],
	'posts_per_page' => $ppp,
	'status' => null,
];
if ( $nt_q['date'] ) $args['date_query'] = [ [ 'date' => $nt_q['date'] ] ];
if ( ! empty( $nt_q['cat'] ) ) {
	$tq = [];
	$tq[] = [ 'taxonomy' => 'category', 'terms' => [ $nt_q['cat'] ] ];
	if ( ! empty( $tq ) ) $args['tax_query'] = $tq;
}
$ret = $nt_store->getPosts( $args );

$t_posts = $ret['posts'];
$page = $ret['page'];

$t_ppp      = $nt_q['posts_per_page'];
$t_cat      = $nt_q['cat'];
$t_date     = empty( $nt_q['date'] ) ? '' : preg_replace( '/(\d{4})(\d{2})/', '$1-$2', $nt_q['date'] );;
// $t_cats = $nt_store->taxonomy()->getTermAll( 'category', [ $nt_q['cat'] ] );
$t_page = $page;

$types = $nt_store->type()->getTypeAll();
$cur_type = '';
// var_dump( $types );
$taxLabs = [];
$taxes = $nt_store->type()->getTaxonomySlugAll( current( $types )['slug'] );
foreach ( $taxes as $tax ) {
	$taxLabs[] = $nt_store->taxonomy()->getTaxonomy( $tax )['label'];
}

function echo_post_tr( $p ) {
	global $nt_store;
	$pid = $p->getId();
	$date = implode( '', array_map( function ( $e ) { return '<span>' . _h( $e ) . '</span> '; }, explode( ' ', $p->getDate() ) ) );

	$terms = [];
	$type = $p->getType();
	$taxes = $nt_store->type()->getTaxonomySlugAll( $type );
	foreach ( $taxes as $tax ) {
		$ss = $p->getTermSlugs( $tax );
		$ls = array_map( function ( $e ) use ( $tax ) { global $nt_store; return $nt_store->taxonomy()->getTermLabel( $tax, $e ); }, $ss );
		$terms[ $tax ] = implode( ', ', $ls );
	}

?>
	<tr>
		<td>
			<select onchange="setPostStatus(<?= _h( $pid ) ?>, this.value);">
<?php if ( $p->canPublished() ): ?>
				<option value="published"<?php if ( $p->isStatus( Post::STATUS_PUBLISHED ) ) _eh( ' selected' ); ?>><?= _ht( 'Published' ) ?></option>
<?php else: ?>
				<option value="reserved"<?php if ( $p->isStatus( Post::STATUS_RESERVED ) ) _eh( ' selected' ); ?>><?= _ht( 'Reserved' ) ?></option>
<?php endif ?>
				<option value="draft"<?php if ( $p->isStatus( Post::STATUS_DRAFT ) ) _eh( ' selected' ); ?>><?= _ht( 'Draft' ) ?></option>
			</select>
		</td>
		<td><a href="#" onclick="editPost(<?= _h( $pid ) ?>);"><?= _h( $p->getTitle() ) ?></a></td>
<?php foreach ( $terms as $tax => $t ) : ?>
		<td class="taxonomy <?= _h( $tax ) ?>"><div><?= _h( $t ) ?></div></td>
<?php endforeach; ?>
		<td class="date"><?= $date ?></td>
		<td><a class="button delete mini cross" href="#" onClick="deletePost(<?= _h( $pid ) ?>, '<?= _h($p->getDate()) ?>','<?= _h($p->getTitle(true)) ?>');" title="<?= _ht('Delete') ?>"></a></td>
	</tr>
<?php
}

function echo_pagination( $size, $ppp, $page ) {
	$t_pgs = [];
	$t_pg_prev = false;
	$t_pg_next = false;
	if ( $ppp < $size ) {
		$maxPage = ceil( $size / $ppp );
		for ( $i = 1; $i <= $maxPage; $i += 1 ) {
			$t_pgs[] = [ 'page' => $i, 'index' => ( $i === $page ) ? false : $i ];
		}
		if ( $page > 1 ) $t_pg_prev = $page - 1;
		if ( $page < $maxPage ) $t_pg_next = $page + 1;
	}
?>
	<nav>
		<ul class="pagination">
<?php if ( $t_pg_prev ): ?>
			<li><a href="#" onClick="submitPage(<?= _h( $t_pg_prev ) ?>);"><?= _ht( 'New' ) ?></a></li>
<?php endif ?>
<?php foreach( $t_pgs as $pg ):
	$bgn = $pg['index'] ? ( '<a href="#" onclick="submitPage(' . $pg['page'] . ')">' ) : '<span>';
	$end = $pg['index'] ?  '</a>' : '</span>';
?>
			<li><?= $bgn . $pg['page'] . $end ?></li>
<?php endforeach ?>
<?php if ( $t_pg_next ): ?>
			<li><a href="#" onClick="submitPage(<?= _h( $t_pg_next ) ?>);"><?= _ht( 'Old' ) ?></a></li>
<?php endif ?>
		</ul>
	</nav>
<?php
}


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
	<a href="login.php" class="button"><?= _ht('Log Out') ?></a>
</header>

<div class="container">
	<nav class="frame frame-filter">
		<div>
			<h3><?= _ht('Post Type') ?></h3>
			<select id="select-type">
<?php foreach ( $types as $slug => $d ) : ?>
				<option value="<?= _h( $slug ) ?>"<?php if ( $slug === $cur_type ) _eh(' selected') ?>><?= _h( $d['label'] ) ?></option>
<?php endforeach; ?>
			</select>
		</div>
		<div>
			<h3><?= _ht('Display Month') ?></h3>
			<div class="filter-month-wrapper">
				<input id="filter-month" type="month" value="<?= $t_date ?>">
				<button id="filter-month-reset" class="cross"></button>
			</div>
		</div>
		<div>
			<h3><?= _ht('View Count') ?></h3>
			<select id="ppp" onchange="changePpp(this.value);">
				<option value="10">10</option>
				<option value="20">20</option>
				<option value="50">50</option>
				<option value="100">100</option>
			</select>
		</div>
		<div class="button-row">
			<a class="button accent" href="#" onclick="newPost();"><?= _ht("New Post") ?></a>
		</div>
	</nav>

	<div class="frame frame-main">
		<table class="list-item">
			<tr>
				<th><?= _ht( 'Status' ) ?></th>
				<th><?= _ht( 'Title' ) ?></th>
<?php foreach ( $taxLabs as $taxLab ) : ?>
				<th><?= _ht( $taxLab ) ?></th>
<?php endforeach; ?>
				<th><?= _ht( 'Date' ) ?></th>
				<th></th>
			</tr>
			<?php foreach($t_posts as $p): echo_post_tr( $p ); endforeach ?>
		</table>
		<?php echo_pagination( $ret['size'], $t_ppp, $t_page ); ?>
	</div>

	<form name="form" id="form" action="" method="post">
		<input type="hidden" name="mode" id="mode" value="">
		<input type="hidden" name="id" id="id" value="">
		<input type="hidden" name="page" id="page" value="<?= _h($t_page) ?>">
		<input type="hidden" name="posts_per_page" id="posts_per_page" value="<?= _h($t_ppp) ?>">
		<input type="hidden" name="cat" id="cat" value="<?= _h($t_cat) ?>">
		<input type="hidden" name="date" id="date" value="<?= _h($t_date) ?>">
	</form>
</div>
</body>
</html>
