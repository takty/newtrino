<?php
namespace nt;
/**
 *
 * Preview
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-14
 *
 */


require_once( __DIR__ . '/index.php' );


$t_title   = $nt_q['post_title'];
$t_date    = $nt_q['post_date'];
$t_content = $nt_q['post_content'];

global $nt_store;
$taxes = $nt_store->taxonomy()->getTaxonomyAll();
$tax_labels = [];
foreach ( $taxes as $tax => $data ) {
	if ( ! isset( $nt_q["taxonomy:$tax"] ) ) continue;
	$ts = is_array( $nt_q["taxonomy:$tax"] ) ? $nt_q["taxonomy:$tax"] : [ $nt_q["taxonomy:$tax"] ];
	$ls = [];
	foreach ( $ts as $t ) {
		$l = $nt_store->taxonomy()->getTermLabel( $tax, $t );
		if ( ! empty( $l ) ) $ls[] = _h( $l );
	}
	if ( ! empty( $ls ) ) $tax_labels[ $tax ] = '<li>' . implode( '</li><li>', $ls ) . '</li>';
}


header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= _ht( 'Preview' ) ?> - Newtrino</title>
<link rel="stylesheet" media="all" href="../data/preview.css" />
</head>
<body class="preview">
<div class="container">
	<header class="entry-header">
		<h1><?= _h( $t_title ) ?></h1>
		<div class="date"><?= _h( $t_date ) ?></div>
<?php foreach ( $tax_labels as $tax => $lis ) : ?>
		<ul class="taxonomy-<?= _h( $tax ) ?>"><?= $lis ?></ul>
<?php endforeach; ?>
	</header>
	<main class="entry-content">
		<?= $t_content ?>
	</main>
</div>
</body>
</html>
