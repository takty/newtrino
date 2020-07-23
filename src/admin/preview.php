<?php
namespace nt;
/**
 *
 * Preview
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-24
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/class-store.php' );

start_session( true );

$q = $_REQUEST;
$q_title   = $q['post_title']   ?? '';
$q_date    = $q['post_date']    ?? '';
$q_content = $q['post_content'] ?? '';

$taxes = array_keys( $nt_store->taxonomy()->getTaxonomyAll() );
$tax_lis = [];
foreach ( $taxes as $tax ) {
	if ( ! isset( $q["taxonomy:$tax"] ) ) continue;
	$ts = is_array( $q["taxonomy:$tax"] ) ? $q["taxonomy:$tax"] : [ $q["taxonomy:$tax"] ];
	$ls = [];
	foreach ( $ts as $t ) {
		$ls[] = _h( $nt_store->taxonomy()->getTermLabel( $tax, $t ) );
	}
	if ( ! empty( $ls ) ) $tax_lis[ $tax ] = '<li>' . implode( '</li><li>', $ls ) . '</li>';
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
		<h1><?= _h( $q_title ) ?></h1>
		<div class="date"><?= _h( $q_date ) ?></div>
<?php foreach ( $tax_lis as $tax => $lis ) : ?>
		<ul class="taxonomy-<?= _h( $tax ) ?>"><?= $lis ?></ul>
<?php endforeach; ?>
	</header>
	<main class="entry-content">
		<?= $q_content ?>
	</main>
</div>
</body>
</html>
