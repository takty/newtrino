<?php
namespace nt;
/**
 *
 * Metabox
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-18
 *
 */


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
		<select form="form-post" name="taxonomy:<?= $tax_slug ?>[]" id="taxonomy:<?= $tax_slug ?>">
<?php foreach( $ts as $t ): ?>
			<option value="<?= _h( $t['slug'] ) ?>"<?php if ( $t['is_selected'] ) _eh( ' selected' ); ?>><?= _h( $t['label'] ) ?></option>
<?php endforeach; ?>
		</select>
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
