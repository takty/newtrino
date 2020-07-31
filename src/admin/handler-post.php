<?php
namespace nt;
/**
 *
 * Handler - Post
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-31
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/class-store.php' );
require_once( __DIR__ . '/../core/util/template.php' );
require_once( __DIR__ . '/../core/util/query-string.php' );
require_once( __DIR__ . '/../core/util/param.php' );

start_session( true );


function handle_query( array $q ): array {
	global $nt_config, $nt_store, $nt_session;
	$list_url = NT_URL_ADMIN . 'list.php';
	$post_url = NT_URL_ADMIN . 'post.php';

	$q_id   = $q['id']   ?? 0;
	$q_mode = $q['mode'] ?? '';
	$msg    = '';

	$lang = $nt_config['lang_admin'];
	$query = parse_query_string();
	$query = _rearrange_query( $query );

	switch ( $q_mode ) {
		case 'new':
			$t_p = $nt_store->createNewPost( $query['type'] );
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

	return [
		'list_url'    => create_canonical_url( $list_url, $query, [ 'id' => null ] ),
		'update_url'  => create_canonical_url( $post_url, $query, [ 'mode' => 'update', 'id' => $t_p->getId() ] ),
		'preview_url' => create_canonical_url( 'preview.php', $query, [ 'mode' => 'preview', 'id' => $t_p->getId() ] ),
		'media_url'   => create_canonical_url( 'media.php', [ 'id' => $t_p->getId() ] ),

		'message' => $msg,
		'lang'    => $lang,

		'editor_css'    => get_editor_css_url(),
		'editor_option' => get_editor_option( $lang ),

		'post_title'   => $t_p->getTitle(),
		'post_date'    => $t_p->getDate(),
		'post_content' => $t_p->getContent(),
		'status@select' => _create_status_select( $t_p ),

		't_p' => $t_p,
	];
}

function get_editor_css_url(): string {
	if ( is_file( NT_DIR_DATA . 'editor.min.css' ) ) {
		return NT_URL . 'data/editor.min.css';
	} else if ( is_file( NT_DIR_DATA . 'editor.css' ) ) {
		return NT_URL . 'data/editor.css';
	}
	return '';
}

function get_editor_option( string $lang ): string {
	$fn = '';
	if ( is_file( NT_DIR_DATA . "editor.$lang.json" ) ) {
		$fn = NT_DIR_DATA . "editor.$lang.json";
	} else if ( is_file( NT_DIR_DATA . 'editor.json' ) ) {
		$fn = NT_DIR_DATA . 'editor.json';
	}
	if ( empty( $fn ) ) return '';
	$json = file_get_contents( $fn );
	$opt = json_decode( $json, true );
	if ( $opt === null ) return '';
	return json_encode( $opt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
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

function _create_status_select( Post $p ): array {
	$ss = [];

	$s = [ 'slug' => 'publish', 'label' => translate( 'Published' ) ];
	if ( $p->isStatus( 'publish' ) ) $s['is_selected'] = true;
	$ss[] = $s;

	$s = [ 'slug' => 'future', 'label' => translate( 'Scheduled' ) ];
	if ( $p->isStatus( 'future' ) ) $s['is_selected'] = true;
	$ss[] = $s;

	$s = [ 'slug' => 'draft', 'label' => translate( 'Draft' ) ];
	if ( $p->isStatus( 'draft' ) ) $s['is_selected'] = true;
	$ss[] = $s;

	return $ss;
}


// -----------------------------------------------------------------------------


function echo_taxonomy_metaboxes( Post $post ): void {
	global $nt_store;
	$type = $post->getType();
	$taxes = $nt_store->type()->getTaxonomySlugAll( $type );
	foreach ( $taxes as $tax ) {
		echo_metabox_taxonomy( $tax, $post );
	}
}

function echo_metabox_taxonomy( string $tax_slug, Post $post ): void {
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
			<option value="<?= _h( $t['slug'] ) ?>"<?= $t['is_selected'] ? ' selected' : '' ?>><?= _h( $t['label'] ) ?></option>
<?php endforeach; ?>
		</select></div>
<?php else : ?>
<?php foreach( $ts as $t ): ?>
		<label>
			<input name="taxonomy:<?= $tax_slug ?>[]" type="checkbox" value="<?= _h( $t['slug'] ) ?>"<?= $t['is_selected'] ? ' checked' : '' ?>>
			<?= _h( $t['label'] ) ?>
		</label>
<?php endforeach; ?>
<?php endif; ?>
	</div>
<?php
}


// -----------------------------------------------------------------------------


function echo_meta_metaboxes( Post $post ): void {
	global $nt_store;
	$type = $post->getType();
	$ms = $nt_store->type()->getMetaAll( $type );
	echo_meta_metaboxes_internal( $post, $ms );
}

function echo_meta_metaboxes_internal( Post $post, array $ms, bool $internal = false ): void {
	foreach ( $ms as $m ) {
		$label = $m['label'] ?? '';
		if ( empty( $label ) ) continue;
		switch ( $m['type'] ) {
			case 'group':
				echo_metabox_group( $post, $label, $m['items'] );
				break;
			case 'text':
				echo_metabox_text( $post, $m['key'], $label, $internal );
				break;
			case 'date':
				echo_metabox_date( $post, $m['key'], $label, $internal );
				break;
			case 'date-range':
				echo_metabox_date_range( $post, $m['key'], $label, $internal );
				break;
		}
	}
}

function echo_metabox_group( Post $post, string $label, array $items ): void {
?>
	<div class="frame frame-sub metabox-group">
		<div class="title"><?= _ht( $label ) ?></div>
		<div class="group-inner">
<?php
	echo_meta_metaboxes_internal( $post, $items, true );
?>
		</div>
	</div>
<?php
}

function echo_metabox_text( Post $post, string $key, string $label, bool $internal ): void {
	$val = $post->getMetaValue( $key );
	if ( $val === null ) $text = '';
	else $text = $val;

	$cls = $internal ? 'metabox-text' : 'frame frame-sub metabox-text';
?>
	<div class="<?= $cls ?>">
		<div class="title"><?= _ht( $label ) ?></div>
		<div><input type="text" name="meta:<?= _h( $key ) ?>" value="<?= _h( $text ) ?>"></div>
	</div>
<?php
}

function echo_metabox_date( Post $post, string $key, string $label, bool $internal ): void {
	$val = $post->getMetaValue( $key );
	if ( $val === null ) $date = '';
	else $date = Post::parseDate( $val );

	$cls = $internal ? 'metabox-date' : 'frame frame-sub metabox-date';
?>
	<div class="<?= $cls ?>">
		<div class="title"><?= _ht( $label ) ?></div>
		<div class="flatpickr date" data-key="<?= _h( $key ) ?>">
			<input type="text" readonly="readonly" data-input>
			<a class="button delete cross" title="clear" data-clear></a>
		</div>
		<input type="hidden" name="meta:<?= _h( $key ) ?>" value="<?= _h( $date ) ?>">
	</div>
<?php
}

function echo_metabox_date_range( Post $post, string $key, string $label, bool $internal ): void {
	$val = $post->getMetaValue( $key );
	if ( $val === null ) {
		$bgn = '';
		$end = '';
	} else {
		$bgn = Post::parseDate( $val[0] );
		$end = Post::parseDate( $val[1] );
	}
	$cls = $internal ? 'metabox-date-range' : 'frame frame-sub metabox-date-range';
?>
	<div class="<?= $cls ?>">
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
