<?php
/**
 * Handler - Post
 *
 * @author Takuto Yanagida
 * @version 2024-03-25
 */

namespace nt;

require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/class-store.php' );
require_once( __DIR__ . '/../core/util/date-format.php' );
require_once( __DIR__ . '/../core/util/param.php' );
require_once( __DIR__ . '/../core/util/query-string.php' );
require_once( __DIR__ . '/../core/util/template.php' );

start_session( true );

/**
 * Handles the query post.
 *
 * @global array<string, mixed> $nt_config  The NT configuration array.
 * @global Store                $nt_store   The NT store object.
 * @global Session              $nt_session The NT session object.
 *
 * @param array<string, mixed> $q The query array.
 * @return array<string, mixed> An array containing the processed post and other related information.
 */
function handle_query_post( array $q ): array {
	global $nt_config, $nt_store, $nt_session;
	$list_url = NT_URL_ADMIN . 'list.php';
	$post_url = NT_URL_ADMIN . 'post.php';

	$q_id   = $q['id']   ?? null;
	$q_mode = $q['mode'] ?? '';
	$msg    = '';

	$lang = $nt_config['lang_admin'];
	$query = \nt\parse_query_string();
	$query = _rearrange_query( $query );

	if ( $q_id && ! $nt_session->lock( $q_id ) ) {
		header( 'Location: ' . \nt\create_canonical_url( $list_url, $query, [ 'id' => null, 'error' => 'lock' ] ) );
		exit;
	}
	if ( $nt_session->checkNonce() ) {
		switch ( $q_mode ) {
			case 'new':
				$t_p = $nt_store->createNewPost( $query['type'] );
				if ( ! $t_p ) break;
				$nt_session->addTemporaryDirectory( $nt_store->getPostDir( $t_p->getId(), null ) );
				$t_p->setDate();
				break;
			case 'update':
				$p = $nt_store->getPost( $q_id );
				if ( ! $p ) break;
				$p->assign( $q );
				$t_p = $nt_store->writePost( $p );
				$msg = _ht( 'Update Complete' );
				break;
			default:
				$t_p = $nt_store->getPost( $q_id );
				break;
		}
	}
	$query['nonce'] = $nt_session->getNonce();

	if ( ! isset( $t_p ) ) {
		header( 'Location: ' . \nt\create_canonical_url( $list_url, $query, [ 'id' => null, 'error' => ( empty( $q_mode ) ? 'view' : $q_mode ) ] ) );
		exit;
	}
	return [
		'list_url'    => \nt\create_canonical_url( $list_url, $query, [ 'id' => null ] ),
		'update_url'  => \nt\create_canonical_url( $post_url, $query, [ 'mode' => 'update', 'id' => $t_p->getId() ] ),
		'preview_url' => \nt\create_canonical_url( 'preview.php', $query, [ 'mode' => 'preview', 'id' => $t_p->getId() ] ),
		'media_url'   => \nt\create_canonical_url( 'media.php', [ 'id' => $t_p->getId() ] ),

		'ntc'  => $msg,
		'lang' => $lang,

		'editor_js'     => get_asset_url( [ "editor.$lang.min.js", "editor.$lang.js", 'editor.min.js', 'editor.js' ] ),
		'editor_css'    => get_asset_url( [ 'editor.min.css', 'editor.css' ] ),
		'editor_option' => get_editor_option( $lang ),
		'assets_url'    => NT_URL . 'data/assets/',

		'post_title'    => $t_p->getTitle(),
		'post_date'     => $t_p->getDate(),
		'post_content'  => $t_p->getContent(),
		'status@select' => _create_status_select( $t_p ),

		't_p' => $t_p,
	];
}

/**
 * Gets the editor option.
 *
 * @param string $lang The language string.
 * @return string The editor option string.
 */
function get_editor_option( string $lang ): string {
	$fn = '';
	foreach ( [ "editor.$lang.json", 'editor.json' ] as $f ) {
		if ( is_file( NT_DIR_DATA . $f ) && is_readable( NT_DIR_DATA . $f ) ) {
			$fn = NT_DIR_DATA . $f;
			break;
		}
	}
	if ( empty( $fn ) ) return '';
	$json = file_get_contents( $fn );
	if ( $json === false ) return '';
	$opt = json_decode( $json, true );
	if ( $opt === null ) return '';
	$ret = json_encode( $opt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( is_string( $ret ) ) {
		return $ret;
	}
	return '';
}


// -----------------------------------------------------------------------------


/**
 * Rearranges the query.
 *
 * @param array<string, mixed> $query The original query array.
 * @return array<string, mixed> The rearranged query array.
 */
function _rearrange_query( array $query ): array {
	return \nt\get_query_vars( $query, [
		'id'       => 'int',
		'page'     => 'int',
		'per_page' => 'int',
		'date'     => 'slug',
		'type'     => 'slug',
	], 'taxonomy' );
}

/**
 * Creates the status select.
 *
 * @param Post $p The post object.
 * @return array<string, mixed>[] The status select array.
 */
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


/**
 * Echoes the taxonomy metaboxes.
 *
 * @global Store $nt_store The NT store object.
 *
 * @param Post $post The post object.
 */
function echo_taxonomy_metaboxes( Post $post ): void {
	global $nt_store;
	$type = $post->getType();
	$taxes = $nt_store->type()->getTaxonomySlugAll( $type );
	foreach ( $taxes as $tax ) {
		echo_metabox_taxonomy( $tax, $post );
	}
}

/**
 * Echoes the metabox taxonomy.
 *
 * @global Store $nt_store The NT store object.
 *
 * @param string $tax_slug The taxonomy slug.
 * @param Post   $post     The post object.
 */
function echo_metabox_taxonomy( string $tax_slug, Post $post ): void {
	global $nt_store;
	$tax = $nt_store->taxonomy()->getTaxonomy( $tax_slug );
	$is_exclusive = isset( $tax['is_exclusive'] ) && $tax['is_exclusive'] === true;
	$tss = $post->getTermSlugs( $tax_slug );
	$ts  = $nt_store->taxonomy()->getTermAll( $tax_slug, $tss );
?>
	<div class="metabox-taxonomy frame frame-box">
		<div class="title"><?= _h( $tax['label'] ); ?></div>
<?php if ( $is_exclusive ) : ?>
		<div>
			<label class="select">
				<select form="form-post" name="taxonomy:<?= $tax_slug ?>[]" id="taxonomy:<?= $tax_slug ?>">
<?php foreach( $ts as $t ): ?>
					<option value="<?= _h( $t['slug'] ); ?>"<?= $t['is_selected'] ? ' selected' : '' ?>><?= _h( $t['label'] ); ?></option>
<?php endforeach; ?>
				</select>
			</label>
		</div>
<?php else : ?>
<?php foreach( $ts as $t ): ?>
		<label class="checkbox">
			<input name="taxonomy:<?= $tax_slug ?>[]" type="checkbox" value="<?= _h( $t['slug'] ); ?>"<?= $t['is_selected'] ? ' checked' : '' ?>>
			<?= _h( $t['label'] ); ?>
		</label>
<?php endforeach; ?>
<?php endif; ?>
	</div>
<?php
}


// -----------------------------------------------------------------------------


/**
 * Echoes the meta metaboxes.
 *
 * @global Store $nt_store The store object.
 *
 * @param Post $post The post object.
 */
function echo_meta_metaboxes( Post $post ): void {
	global $nt_store;
	$type = $post->getType();
	$ms = $nt_store->type()->getMetaAll( $type );
	echo_meta_metaboxes_internal( $post, $ms );
}

/**
 * Echoes the meta metaboxes internally.
 *
 * @param Post                   $post     The post object.
 * @param array<string, mixed>[] $ms       The meta data array.
 * @param bool                   $internal The internal boolean.
 */
function echo_meta_metaboxes_internal( Post $post, array $ms, bool $internal = false ): void {
	foreach ( $ms as $m ) {
		$label = $m['label'] ?? '';
		if ( 'group' !== $m['type'] && empty( $label ) ) {
			continue;
		}
		switch ( $m['type'] ) {
			case 'group':
				echo_metabox_group( $post, $m, $label );
				break;
			case 'text':
				echo_metabox_text( $post, $m, $label, $internal );
				break;
			case 'checkbox':
				echo_metabox_checkbox( $post, $m, $label, $internal );
				break;
			case 'date':
				echo_metabox_date( $post, $m, $label, $internal );
				break;
			case 'date_range':
				echo_metabox_date_range( $post, $m, $label, $internal );
				break;
			case 'media':
				echo_metabox_media( $post, $m, $label, $internal );
				break;
			case 'media_image':
				echo_metabox_media_image( $post, $m, $label, $internal );
				break;
		}
	}
}

/**
 * Echoes the metabox group.
 *
 * @param Post                 $post  The post object.
 * @param array<string, mixed> $m     The meta data array.
 * @param string               $label The label string.
 */
function echo_metabox_group( Post $post, array $m, string $label ): void {
	$items = $m['items'];
?>
	<div class="frame frame-box metabox-group">
<?php if ( ! empty( $label ) ) : ?>
		<div class="title"><?= _ht( $label ); ?></div>
<?php endif; ?>
		<div class="group-inner">
<?php
	echo_meta_metaboxes_internal( $post, $items, true );
?>
		</div>
	</div>
<?php
}

/**
 * Echoes the metabox text.
 *
 * @param Post                 $post     The post object.
 * @param array<string, mixed> $m        The meta data array.
 * @param string               $label    The label string.
 * @param bool                 $internal The internal boolean.
 */
function echo_metabox_text( Post $post, array $m, string $label, bool $internal ): void {
	$key  = $m['key'];
	$val  = $post->getMetaValue( $key );
	$text = ( $val === null ) ? '' : $val;

	$cls = $internal ? '' : ' frame frame-box';
?>
	<div class="metabox-text<?= $cls ?>">
		<div class="title"><?= _ht( $label ); ?></div>
		<div><input type="text" name="meta:<?= _h( $key ); ?>" value="<?= _h( $text ); ?>"></div>
	</div>
<?php
}

/**
 * Echoes the metabox checkbox.
 *
 * @param Post                 $post     The post object.
 * @param array<string, mixed> $m        The meta data array.
 * @param string               $label    The label string.
 * @param bool                 $internal The internal boolean.
 */
function echo_metabox_checkbox( Post $post, array $m, string $label, bool $internal ): void {
	$key   = $m['key'];
	$val   = $post->getMetaValue( $key );
	$state = ( $val === null ) ? '' : ' checked';

	$cls = $internal ? '' : ' frame frame-box';
?>
	<div class="metabox-checkbox<?= $cls ?>">
		<label class="checkbox">
			<input type="checkbox" name="meta:<?= _h( $key ); ?>"<?= _h( $state ); ?>>
			<?= _ht( $label ); ?>
		</label>
	</div>
<?php
}

/**
 * Echoes the metabox date.
 *
 * @param Post                 $post     The post object.
 * @param array<string, mixed> $m        The meta data array.
 * @param string               $label    The label string.
 * @param bool                 $internal The internal boolean.
 */
function echo_metabox_date( Post $post, array $m, string $label, bool $internal ): void {
	$key  = $m['key'];
	$val  = $post->getMetaValue( $key );
	$date = ( $val === null ) ? '' : \nt\parse_date( $val );

	$cls = $internal ? '' : ' frame frame-box';
?>
	<div class="metabox-date<?= $cls ?>">
		<div class="title"><?= _ht( $label ); ?></div>
		<div class="flatpickr date" data-key="<?= _h( $key ); ?>">
			<input type="text" readonly="readonly" data-input>
			<a class="button delete cross" title="clear" data-clear></a>
		</div>
		<input type="hidden" name="meta:<?= _h( $key ); ?>" value="<?= _h( $date ); ?>">
	</div>
<?php
}

/**
 * Echoes the metabox date range.
 *
 * @param Post                 $post     The post object.
 * @param array<string, mixed> $m        The meta data array.
 * @param string               $label    The label string.
 * @param bool                 $internal The internal boolean.
 */
function echo_metabox_date_range( Post $post, array $m, string $label, bool $internal ): void {
	$key  = $m['key'];
	$mv   = $post->getMetaValue( $key );
	$date = '';
	if ( $mv && isset( $mv['from'] ) && isset( $mv['to'] ) ) {
		$mv   = [ 'from' => \nt\parse_date( $mv['from'] ), 'to' => \nt\parse_date( $mv['to'] ) ];
		$json = json_encode( $mv );
		$date = is_string( $json ) ? $json : '';
	}

	$cls = $internal ? '' : ' frame frame-box';
?>
	<div class="metabox-date-range<?= $cls ?>">
		<div class="title"><?= _ht( $label ); ?></div>
		<div class="flatpickr date-range" data-key="<?= _h( $key ); ?>">
			<input type="text" readonly="readonly" data-input>
			<a class="button delete cross" title="clear" data-clear></a>
		</div>
		<input type="hidden" name="meta:<?= _h( $key ); ?>" value="<?= _h( $date ); ?>">
	</div>
<?php
}

/**
 * Echoes the metabox media.
 *
 * @param Post                 $post     The post object.
 * @param array<string, mixed> $m        The meta data array.
 * @param string               $label    The label string.
 * @param bool                 $internal The internal boolean.
 */
function echo_metabox_media( Post $post, array $m, string $label, bool $internal ): void {
	$key  = $m['key'];
	$mv   = $post->getMetaValue( $key );
	$json = ( $mv ) ? json_encode( $mv ) : '';
	$json = is_string( $json ) ? $json : '';
	$name = ( $mv && isset( $mv['name'] ) ) ? $mv['name'] : '';

	$md   = \nt\create_canonical_url( 'media.php', [ 'id' => $post->getId(), 'target' => "metabox:$key" ] );
	$cls  = $internal ? '' : ' frame frame-box';
	$attr = empty( $name ) ? ' disabled' : '';
?>
	<div class="metabox-media<?= $cls ?>" id="metabox:<?= _h( $key ); ?>">
		<div class="title"><?= _ht( $label ); ?></div>
		<div class="metabox-container">
			<a class="button open-media-dialog" data-src="<?= _h( $md ); ?>"><?= _ht( 'Select' ); ?></a>
			<input type="text" readonly="readonly" class="media-name" value="<?= _h( $name ); ?>">
			<button type="button" class="delete cross right"<?= $attr ?>></button>
		</div>
		<input type="hidden" class="media-json" name="meta:<?= _h( $key ); ?>" value="<?= _h( $json ); ?>">
	</div>
<?php
}

/**
 * Echoes the metabox media image.
 *
 * @param Post                 $post     he post object.
 * @param array<string, mixed> $m        he meta data array.
 * @param string               $label    The label string.
 * @param bool                 $internal The internal boolean.
 */
function echo_metabox_media_image( Post $post, array $m, string $label, bool $internal ): void {
	$key  = $m['key'];
	$size = $m['option']['size'] ?? 'medium';
	$mv   = $post->getMetaValue( $key );
	$json = ( $mv ) ? json_encode( $mv ) : '';
	$json = is_string( $json ) ? $json : '';
	$name = ( $mv && isset( $mv['name'] ) ) ? $mv['name'] : '';
	$bgi  = ( $mv && isset( $mv['minUrl'] ) ) ? ('background-image:url("' . $mv['minUrl'] . '")') : '';

	$md   = \nt\create_canonical_url( 'media.php', [ 'id' => $post->getId(), 'target' => "metabox:$key", 'filter' => 'image', 'size' => $size ] );
	$cls  = $internal ? '' : ' frame frame-box';
	$attr = empty( $name ) ? ' disabled' : '';
?>
	<div class="metabox-media-image<?= $cls ?>" id="metabox:<?= _h( $key ); ?>">
		<div class="title"><?= _ht( $label ); ?></div>
		<div class="metabox-container">
			<a class="image open-media-dialog" data-src="<?= _h( $md ); ?>" title="<?= _ht( 'Select' ); ?>">
				<div style="<?= _h( $bgi ); ?>"></div>
			</a>
			<div>
				<input type="text" readonly="readonly" class="media-name" value="<?= _h( $name ); ?>">
				<button type="button" class="delete cross right"<?= $attr ?>></button>
			</div>
		</div>
		<input type="hidden" class="media-json" name="meta:<?= _h( $key ); ?>" value="<?= _h( $json ); ?>">
	</div>
<?php
}
