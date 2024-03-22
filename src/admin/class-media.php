<?php
/**
 * Media Manager
 *
 * @author Takuto Yanagida
 * @version 2024-03-22
 */

namespace nt;

require_once( __DIR__ . '/../core/class-store.php' );
require_once( __DIR__ . '/util/file.php' );

class Media {

	private const SIZE_NAME_MIN  = 'small';
	private const SIZE_NAME_ORIG = 'full';

	private const IMG_EXTS  = [ 'png', 'jpeg', 'jpg' ];
	private const JSON_OPTS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

	private const FILTER_MAT = [ [ 0, -1, 0 ], [ -1, 12, -1 ], [ 0, -1, 0 ] ];
	private const FILTER_DIV = 8;

	private $_id;
	private $_dir;
	private $_url;
	private $_meta;
	private $_data;
	private $_ntUrl;
	private $_sizes;

	public function __construct( string $id, string $mediaDirName, string $ntUrl ) {
		global $nt_store, $nt_config;

		$this->_id    = $id;
		$this->_dir   = $nt_store->getPostDir( $id, null ) . $mediaDirName . '/';
		$this->_url   = $nt_store->getPostUrl( $id, null ) . $mediaDirName . '/';
		$this->_meta  = $nt_store->getPostDir( $id, null ) . 'media.json';
		$this->_ntUrl = $ntUrl;
		$this->_sizes = $nt_config['image_sizes'];
	}


	// -------------------------------------------------------------------------


	private function _loadMeta(): array {
		if ( $this->_data ) {
			return $this->_data;
		}
		$path = $this->_meta;
		if ( is_file( $path ) && is_readable( $path ) ) {
			$json = file_get_contents( $path );
			if ( false === $json ) {
				Logger::error( __METHOD__, 'Cannot read the meta data', $path );
				return [];
			}
			$data = json_decode( $json, true );
			if ( null === $data ) {
				Logger::error( __METHOD__, 'The meta data is invalid', $path );
			}
			return $this->_data = $data ?? [];
		}
		return $this->_data = [];
	}

	private function _saveMeta( array $data ): void {
		$this->_data = $data;

		$json = json_encode( $data, self::JSON_OPTS );
		$path = $this->_meta;
		$res  = file_put_contents( $path, $json, LOCK_EX );
		if ( false === $res ) {
			Logger::error( __METHOD__, 'Cannot write the meta data', $path );
			return;
		}
		@chmod( $path, NT_MODE_FILE );
	}


	// -------------------------------------------------------------------------


	public function getItemList( ?string $filter = null ): array {
		$meta = $this->_loadMeta();

		$list = [];
		foreach ( $meta as $m ) {
			$is_image = in_array( $m['extension'], self::IMG_EXTS, true );
			if ( 'image' === $filter && ! $is_image ) {
				continue;
			}
			$it = [
				'file_name' => $m['file_name'],
				'ext'       => strtolower( $m['extension'] ),  // Lowercasing for just in case.
				'url'       => $this->_mediaUrl( $m['file_name'] ),
			];
			if ( $is_image ) {
				$sn = isset( $m['sizes'][ self::SIZE_NAME_MIN ] ) ? self::SIZE_NAME_MIN : self::SIZE_NAME_ORIG;

				$it['is_image']   = true;
				$it['sizes_json'] = json_encode( $this->_createSizesWithUrl( $m['sizes'] ), self::JSON_OPTS );
				$it['url@min']    = $this->_mediaUrl( $m['sizes'][ $sn ]['file_name'] );
			}
			$list[] = $it;
		}
		return $list;
	}

	private function _createSizesWithUrl( array $sizes ): array {
		$ret = [];
		foreach ( $sizes as $key => $vals ) {
			$vals['url'] = $this->_mediaUrl( $vals['file_name'] );
			// Replace underscores with hyphens for styles.
			$ret[ str_replace( '_', '-', $key ) ] = $vals;
		}
		uasort(
			$ret,
			function ( $a, $b ) {
				return $a['width'] <=> $b['width'];
			}
		);
		return $ret;
	}

	private function _mediaUrl( string $fileName ): string {
		return $this->_ntUrl . '?' . rawurlencode( $this->_id ) . '=' . rawurlencode( $fileName );
	}


	// -------------------------------------------------------------------------


	public function remove( string $fileName ): void {
		$fns = $this->_removeMeta( $fileName );

		foreach ( $fns as $fn ) {
			$path = $this->_dir . $fn;
			if ( is_file( $path ) ) {
				$res = unlink( $path );
				if ( false === $res ) {
					Logger::error( __METHOD__, 'Cannot remove the file', $path );
				}
			}
		}
	}

	private function _removeMeta( string $fileName ): array {
		$meta    = $this->_loadMeta();
		$removed = [ $fileName ];
		$idx     = -1;

		foreach ( $meta as $i => $m ) {
			if ( $m['file_name'] !== $fileName ) {
				continue;
			}
			$idx = $i;
			if ( isset( $m['sizes'] ) ) {
				foreach ( $m['sizes'] as $s => $d ) {
					if ( isset( $d['file_name'] ) ) {
						$removed[] = $d['file_name'];
					}
				}
			}
			break;
		}
		if ( $idx !== -1 ) {
			array_splice( $meta, $idx, 1 );
			$this->_saveMeta( $meta );
		}
		return $removed;
	}

	public function upload( array $file ): bool {
		$tempFn = $file['tmp_name'];
		$origFn = $file['name'];

		$fn = \nt\get_unique_file_name( $this->_dir, $origFn );
		if ( empty( $fn ) ) {
			Logger::error( __METHOD__, 'File uploading failed', $fn );
			return false;
		}
		$path = $this->_dir . $fn;
		if ( is_uploaded_file( $tempFn ) ) {
			if ( ! is_dir( $this->_dir ) ) {
				mkdir( $this->_dir, NT_MODE_DIR );
			}
			if ( is_dir( $this->_dir ) ) {
				@chmod( $this->_dir, NT_MODE_DIR );
			}
			if ( move_uploaded_file( $tempFn, $path ) ) {
				@chmod( $path, NT_MODE_FILE );
				$this->_addMeta( $fn );
				Logger::info( __METHOD__, 'File uploading succeeded', $fn );
				return true;
			}
		}
		Logger::error( __METHOD__, 'File uploading failed', $fn );
		return false;
	}

	private function _addMeta( string $fileName ): void {
		$ext = mb_strtolower( pathinfo( $fileName, PATHINFO_EXTENSION ) );
		$m   = [
			'file_name' => $fileName,
			'extension' => $ext
		];
		if ( in_array( $ext, self::IMG_EXTS, true ) ) {
			$sizes = $this->_resizeImage( $fileName, $ext );
			if ( $sizes ) {
				$m['sizes'] = $sizes;
			}
		}
		$meta   = $this->_loadMeta();
		$meta[] = $m;
		$this->_saveMeta( $meta );
	}


	// -------------------------------------------------------------------------


	private function _resizeImage( string $fileName, string $ext ): ?array {
		$info = getimagesize( $this->_dir . $fileName );
		if ( ! is_array( $info ) ) {
			return null;
		}
		list( $width, $height ) = $info;
		$sizes = [
			self::SIZE_NAME_ORIG => [ 'file_name' => $fileName, 'width' => $width, 'height' => $height ],
		];

		$min = intval( $this->_sizes[ self::SIZE_NAME_MIN ]['width'] );
		if ( $min < $width ) {
			$mime = mime_content_type( $this->_dir . $fileName );
			$img  = $this->_loadImage( $fileName, $mime );
			if ( null === $img ) {
				return $sizes;
			}
			foreach ( $this->_sizes as $key => $d ) {
				$px = intval( $d['width'] );
				if ( $px < $width ) {
					// Replace hyphens with underscores for JSON.
					$key = str_replace( '-', '_', $key );

					list( $fn, $w, $h ) = $this->_saveScaledImage( $img, $fileName, $mime, $px );
					$sizes[ $key ]      = [ 'file_name' => $fn, 'width' => $w, 'height' => $h ];
				}
			}
			imagedestroy( $img );
		}
		return $sizes;
	}

	private function _saveScaledImage( $img, string $fn, string $mime, int $size ): array {
		$newImg = $this->_scaleImage( $img, $size, $mime );
		$newFn  = \nt\get_unique_file_name( $this->_dir, $fn, "-$size" );
		$this->_saveImage( $newFn, $mime, $newImg );

		$ret = [ $newFn, imagesx( $newImg ), imagesy( $newImg ) ];
		imagedestroy( $newImg );
		return $ret;
	}

	private function _scaleImage( $img, int $newW, string $mime ) {
		$w      = imagesx( $img );
		$h      = imagesy( $img );
		$newH   = $h * $newW / $w;
		$newImg = imagecreatetruecolor( (int) $newW, (int) $newH );

		if ( 'image/png' === $mime || 'image/x-png' === $mime ) {
			imagealphablending( $newImg, false );
			imagefill( $newImg, 0, 0, imagecolorallocatealpha( $newImg, 0, 0, 0, 127 ) );
			imagesavealpha( $newImg, true );
		} elseif ( 'image/gif' === $mime ) {
			$idx = imagecolortransparent( $img );
			if ( 0 <= $idx ) {
				$c      = imagecolorsforindex( $img, $idx );
				$newIdx = imagecolorallocate( $newImg, $c['red'], $c['green'], $c['blue'] );
				imagefill( $newImg, 0, 0, $newIdx );
				imagecolortransparent( $newImg, $newIdx );
			}
		}
		imagecopyresampled( $newImg, $img, 0, 0, 0, 0, $newW, $newH, $w, $h );
		imageconvolution( $newImg, self::FILTER_MAT, self::FILTER_DIV, 0 );
		return $newImg;
	}

	private function _loadImage( string $fn, string $mime ) {
		$path = $this->_dir . $fn;
		switch ( $mime ) {
			case 'image/jpeg':
			case 'image/jpg':
			case 'image/pjpeg':
				return imagecreatefromjpeg( $path );
			case 'image/png':
			case 'image/x-png':
				return imagecreatefrompng( $path );
			case 'image/gif':
				return imagecreatefromgif( $path );
		}
		return null;
	}

	private function _saveImage( string $fn, string $mime, $img ): void {
		$path = $this->_dir . $fn;
		switch ( $mime ) {
			case 'image/jpeg':
			case 'image/jpg':
			case 'image/pjpeg':
				imagejpeg( $img, $path, 80 );
				break;
			case 'image/png':
			case 'image/x-png':
				imagepng( $img, $path );
				break;
			case 'image/gif':
				imagegif( $img, $path );
				break;
		}
		@chmod( $path, NT_MODE_FILE );
	}

}
