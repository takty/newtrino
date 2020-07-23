<?php
namespace nt;
/**
 *
 * Media Manager
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-22
 *
 */


require_once( __DIR__ . '/../core/class-store.php' );


class Media {

	const MODE_DIR  = 0755;
	const MODE_FILE = 0644;

	private $_id;
	private $_dir;
	private $_url;
	private $_meta;
	private $_data;
	private $_ntUrl;

	public function __construct( $id, $mediaDirName, $ntUrl ) {
		global $nt_store;
		$this->_id   = $id;
		$this->_dir  = $nt_store->getPostDir( $id, null ) . $mediaDirName . '/';
		$this->_url  = $nt_store->getPostUrl( $id, null ) . $mediaDirName . '/';
		$this->_meta = $nt_store->getPostDir( $id, null ) . 'media.json';

		$this->_ntUrl = $ntUrl;
	}


	// -------------------------------------------------------------------------


	public function getItemList() {
		$meta = $this->_loadMeta();

		$list = [];
		foreach ( $meta as $m ) {
			$item = [];
			$ext = $m['extension'];
			if ( in_array( $ext, [ 'png', 'jpeg', 'jpg' ], true ) ) {
				$item['is_image']   = true;
				$item['sizes_json'] = json_encode( $this->_createSizesWithUrl( $m['sizes'] ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

				if ( isset( $m['sizes']['small'] ) ) {
					$item['url@min'] = $this->_url . rawurlencode( $m['sizes']['small']['file_name'] );
				} else {
					$item['url@min'] = $this->_url . rawurlencode( $m['sizes']['full']['file_name'] );
				}
			}
			$item['file_name'] = $m['file_name'];
			$item['url']       = $this->_mediaUrl( $m['file_name'] );
			$item['ext']       = $ext;
			$list[] = $item;
		}
		return $list;
	}

	private function _createSizesWithUrl( array $sizes ): array {
		$ret = [];
		foreach ( $sizes as $key => $vals ) {
			$fn = $vals['file_name'];
			$url = $this->_mediaUrl( $fn );
			$vals['url'] = $url;
			$ret[ $key ] = $vals;
		}
		return $ret;
	}

	private function _mediaUrl( $fileName ) {
		return $this->_ntUrl . '?' . rawurlencode( $this->_id ) . '=' . rawurlencode( $fileName );
	}

	public function upload( $file ) {
		$tmpFile      = $file['tmp_name'];
		$origFileName = $file['name'];

		$fileName = $this->getUniqueFileName( $origFileName );
		if ( empty( $fileName ) ) return false;

		$path = $this->_dir . $fileName;
		if ( is_uploaded_file( $tmpFile ) ) {
			if ( ! file_exists( $this->_dir ) ) {
				mkdir( $this->_dir, self::MODE_DIR );
			}
			if ( move_uploaded_file( $tmpFile, $path ) ) {
				chmod( $path, self::MODE_FILE );
				$this->_addMeta( $fileName );
				return true;
			}
		}
		return false;
	}

	public function remove( $fileName ) {
		$path = $this->_dir . $fileName;
		@unlink( $path );
		$this->_removeMeta( $fileName );
	}

	private function getUniqueFileName( $fileName, $postFix = '' ) {
		$pi   = pathinfo( $fileName );
		$ext  = '.' . $pi['extension'];
		$name = $pi['filename'] . $postFix;

		$nfn = "$name$ext";
		if ( ! $this->_isFileExist( $nfn ) ) return $nfn;

		for ( $num = 1; $num <= 256; $num += 1 ) {
			$nfn = $name . '[' . $num . ']' . $ext;
			if ( ! $this->_isFileExist( $nfn ) ) return $nfn;
		}
		return '';
	}

	private function _isFileExist( $fileName ) {
		return is_dir( $this->_dir ) && is_file( $this->_dir . $fileName );
	}


	// -------------------------------------------------------------------------


	private function _loadMeta(): array {
		if ( $this->_data ) return $this->_data;

		$path = $this->_meta;
		if ( is_file( $path ) ) {
			$json = file_get_contents( $path );
			if ( $json === false ) {
				Logger::output( "Error (Media::_loadMeta file_get_contents) [$path]" );
				return [];
			}
			return $this->_data = json_decode( $json, true );
		} else {
			return $this->_data = [];
		}
	}

	private function _saveMeta( array $data ): void {
		$this->_data = $data;
		$json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		$path = $this->_meta;
		$res = file_put_contents( $path, $json, LOCK_EX );
		if ( $res === false ) {
			Logger::output( "Error (Media::_loadMeta file_put_contents) [$out_path]" );
			return;
		}
	}

	private function _addMeta( string $fileName ) {
		$meta = $this->_loadMeta();

		$ext = mb_strtolower( pathinfo( $fileName, PATHINFO_EXTENSION ) );
		if ( in_array( $ext, [ 'png', 'jpeg', 'jpg' ], true ) ) {
			list( $width, $height ) = getimagesize( $this->_dir . $fileName );
			$meta[] = [
				'file_name' => $fileName,
				'extension' => $ext,
				'sizes'     => $this->_resizeImage( $fileName, $ext )
			];
		} else {
			$meta[] = [
				'file_name' => $fileName,
				'extension' => $ext
			];
		}
		$this->_saveMeta( $meta );
	}

	private function _removeMeta( $fileName ) {
		$meta = $this->_loadMeta();
		$idx = -1;
		$sizes = null;
		foreach ( $meta as $i => $m ) {
			if ( $m['file_name'] === $fileName ) {
				$idx = $i;
				if ( isset( $m['sizes'] ) ) $sizes = $m['sizes'];
				break;
			}
		}
		if ( $idx !== -1 ) {
			if ( $sizes !== null ) {
				foreach ( $sizes as $s => $d ) {
					if ( ! isset( $d['file_name'] ) ) continue;
					$path = $this->_dir . $d['file_name'];
					if ( is_file( $path ) ) @unlink( $path );
				}
			}
			array_splice( $meta, $idx, 1 );
		}
		$this->_saveMeta( $meta );
	}


	// -------------------------------------------------------------------------


	private function _resizeImage( $fileName, $ext ) {
		global $nt_config;
		$sizes = [];
		list( $width, $height ) = getimagesize( $this->_dir . $fileName );
		$sizes['full'] = [ 'file_name' => $fileName, 'width' => $width, 'height' => $height ];

		$min = intval( $nt_config['image_sizes']['small'] );
		if ( $min < $width ) {
			$img = $this->_loadImage( $fileName, $ext );
			foreach ( $nt_config['image_sizes'] as $key => $px ) {
				$key = str_replace( '_', '-', $key );
				if ( intval( $px ) < $width ) {
					list( $fn, $w, $h ) = $this->_scaleImage( $img, $fileName, $ext, intval( $px ) );
					$sizes[ $key ] = [ 'file_name' => $fn, 'width' => $w, 'height' => $h ];
				}
			}
		}
		return $sizes;
	}

	private function _scaleImage( $img, $fn, $ext, $size ) {
		$newImg = imagescale( $img, $size, -1, IMG_BICUBIC );

		$mat = [ [ -1.2, -1, -1.2 ], [ -1.0, 20, -1.0 ], [ -1.2, -1, -1.2 ] ];
		$div = 11.2;
		imageconvolution( $newImg, $mat, $div, 0 );

		$newFn = $this->getUniqueFileName( $fn, "@$size" );
		$this->_saveImage( $newFn, $ext, $newImg );
		return [ $newFn, imagesx( $newImg ), imagesy( $newImg ) ];
	}

	private function _loadImage( $fn, $ext ) {
		switch ( $ext ) {
			case 'jpg':
			case 'jpeg':
				return imagecreatefromjpeg( $this->_dir . $fn );
			case 'png':
				return imagecreatefrompng( $this->_dir . $fn );
		}
		return null;
	}

	private function _saveImage( $fn, $ext, $img ) {
		switch ( $ext ) {
			case 'jpg':
			case 'jpeg':
				imagejpeg( $img, $this->_dir . $fn, 80 );
				break;
			case 'png':
				imagepng( $img, $this->_dir . $fn );
				break;
		}
	}

}
