<?php
namespace nt;
/**
 *
 * Preview
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-12
 *
 */


require_once( __DIR__ . '/index.php' );


$t_title   = $nt_q['post_title'];
$t_pdate   = $nt_q['post_date'];
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
		if ( ! empty( $l ) ) $ls[] = $l;
	}
	if ( ! empty( $ls ) ) $tax_labels[ $tax ] = implode( ', ', $ls );
}


header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= _ht('Preview') ?> - Newtrino</title>
<link rel="stylesheet" media="all" href="css/style.min.css" />
</head>
<body class="preview">
<div class="container">
	<h1>Newtrino</h1>
	<h2><?= _ht('Preview') ?></h2>
	<main class="topic-post">
		<header>
			<h3><?= _h($t_title) ?></h3>
			<span class="date"><?= _h($t_pdate) ?></span>
<?php foreach ( $tax_labels as $tax => $labels ) : ?>
			<span class="taxonomy-<?= _h( $tax ) ?>"> [<?= _h( $labels  ) ?>]</span>
<?php endforeach; ?>
		</header>
		<div class="post-content"><?= $t_content ?></div>
	</main>
</div>
</body>
</html>
