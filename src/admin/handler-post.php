<?php
namespace nt;
/**
 *
 * Handler - Post
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-25
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/class-store.php' );
require_once( __DIR__ . '/../core/util/template.php' );
require_once( __DIR__ . '/../core/util/query-string.php' );
require_once( __DIR__ . '/../core/util/param.php' );

start_session( true );


function handle_query( $q ) {
	global $nt_config, $nt_store;
	$list_url = NT_URL_ADMIN . 'list.php';
	$post_url = NT_URL_ADMIN . 'post.php';

	$q_id   = $q['id']   ?? 0;
	$q_mode = $q['mode'] ?? '';
	$msg    = '';

	$lang = $nt_config['lang_admin'];

	switch ( $q_mode ) {
		case 'new':
			$t_p = $nt_store->createNewPost();
			$nt_session->addTempDir( $nt_store->getPostDir( $t_p->getId(), null ) );
			$t_p->setDate();
			break;
		case 'update':
			$p = $nt_store->getPost( $q_id );
			$p->assign( $q, NT_URL_ADMIN );
			$t_p = $nt_store->writePost( $p );
			$msg = _ht( 'Update Complete' );
			break;
		default:
			$t_p = $nt_store->getPost( $q_id );
			break;
	}

	$query = parse_query_string();
	$query = _rearrange_query( $query );
	return [
		'list_url'    => create_canonical_url( $list_url, $query ),
		'update_url'  => create_canonical_url( $post_url, $query, [ 'mode' => 'update' ] ),
		'preview_url' => create_canonical_url( 'preview.php', $query, [ 'mode' => 'preview' ] ),
		'media_url'   => create_canonical_url( 'media.php', [ 'id' => $query['id'] ] ),

		'message' => $msg,
		'lang'    => $lang,

		'post_title'   => $t_p->getTitle(),
		'post_date'    => $t_p->getDate(),
		'post_content' => $t_p->getContent(),

		't_p' => $t_p,
	];
}


// -----------------------------------------------------------------------------


function _rearrange_query( array $query ): array {
	return get_query_vars( $query, [
		'id'       => 'int',
		'page'     => 'int',
		'per_page' => 'int',
		'date'     => 'slug',
		'type'     => 'slug',
	], 'taxonomy' );
}


// -----------------------------------------------------------------------------


function echo_status_select( $post ) {
	$s = $post->getStatus();
?>
	<select form="form-post" name="post_status" id="post-status">
		<option id="post-status-publish" value="publish"<?php if ( $s === 'publish' ) _eh( ' selected' ); ?>><?= _ht( 'Published' ) ?></option>
		<option id="post-status-future" value="future"<?php if ( $s === 'future' ) _eh( ' selected' ); ?>><?= _ht( 'Scheduled' ) ?></option>
		<option id="post-status-draft" value="draft"<?php if ( $s === 'draft' ) _eh( ' selected' ); ?>><?= _ht( 'Draft' ) ?></option>
	</select>
<?php
}


// -----------------------------------------------------------------------------


function echo_taxonomy_metaboxes( $post ) {
	global $nt_store;
	$type = $post->getType();
	$taxes = $nt_store->type()->getTaxonomySlugAll( $type );
	foreach ( $taxes as $tax ) {
		echo_metabox_taxonomy( $tax, $post );
	}
}

function echo_metabox_taxonomy( $tax_slug, $post ) {
	global $nt_store;
	$tax = $nt_store->taxonomy()->getTaxonomy( $tax_slug );
	$is_exclusive = isset( $tax['is_exclusive'] ) && $tax['is_exclusive'] === true;
	$tss = $post->getTermSlugs( $tax_slug );
	$ts  = $nt_store->taxonomy()->getTermAll( $tax_slug, $tss );
?>
	<div class="frame frame-sub">
		<div class="title"><?= _h( $tax['label'] ) ?></div>
<?php if ( $is_exclusive ) : ?>
		<div><select form="form-post" name="taxonomy:<?= $tax_slug ?>[]" id="taxonomy:<?= $tax_slug ?>">
<?php foreach( $ts as $t ): ?>
			<option value="<?= _h( $t['slug'] ) ?>"<?php if ( $t['is_selected'] ) _eh( ' selected' ); ?>><?= _h( $t['label'] ) ?></option>
<?php endforeach; ?>
		</select></div>
<?php else : ?>
<?php foreach( $ts as $t ): ?>
		<label>
			<input name="taxonomy:<?= $tax_slug ?>[]" type="checkbox" value="<?= _h( $t['slug'] ) ?>"<?php if ( $t['is_selected'] ) _eh( ' checked' ); ?>>
			<?= _h( $t['label'] ) ?>
		</label>
<?php endforeach; ?>
<?php endif; ?>
	</div>
<?php
}


// -----------------------------------------------------------------------------


function echo_meta_metaboxes( $post ) {
	global $nt_store;
	$type = $post->getType();
	$ms = $nt_store->type()->getMetaAll( $type );
	foreach ( $ms as $m ) {
		$label = $m['label'] ?? '';
		if ( empty( $label ) ) continue;
		switch ( $m['type'] ) {
			case 'date':
				echo_metabox_date( $post, $m['key'], $label );
				break;
			case 'date-range':
				echo_metabox_date_range( $post, $m['key'], $label );
				break;
		}
	}
}

function echo_metabox_date( $post, $key, $label ) {
	$val = $post->getMetaValue( $key );
	if ( $val === null ) return;
	$date = Post::parseDate( $val );
?>
	<div class="frame frame-sub metabox-date">
		<div class="title"><?= _ht( $label ) ?></div>
		<div class="flatpickr date" data-key="<?= _h( $key ) ?>">
			<input type="text" readonly="readonly" data-input>
			<a class="button delete cross" title="clear" data-clear></a>
		</div>
		<input type="hidden" name="meta:<?= _h( $key ) ?>[]" value="<?= _h( $date ) ?>">
	</div>
<?php
}

function echo_metabox_date_range( $post, $key, $label ) {
	$val = $post->getMetaValue( $key );
	if ( $val === null ) return;
	$bgn = Post::parseDate( $val[0] );
	$end = Post::parseDate( $val[1] );
?>
	<div class="frame frame-sub metabox-date-range">
		<div class="title"><?= _ht( $label ) ?></div>
		<div class="flatpickr date-range" data-key="<?= _h( $key ) ?>">
			<input type="text" readonly="readonly" data-input>
			<a class="button delete cross" title="clear" data-clear></a>
		</div>
		<input type="hidden" name="meta:<?= _h( $key ) ?>[]" value="<?= _h( $bgn ) ?>">
		<input type="hidden" name="meta:<?= _h( $key ) ?>[]" value="<?= _h( $end ) ?>">
	</div>
<?php
}
