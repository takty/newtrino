<?php
namespace nt;
/**
 *
 * Media Manager
 *
 * @author Takuto Yanagida
 * @version 2021-06-10
 *
 */


require_once( __DIR__ . '/../core/class-store.php' );


class Media {

	private $_id;
	private $_dir;
	private $_url;
	private $_meta;
	private $_data;
	private $_ntUrl;

	public function __construct( string $id, string $mediaDirName, string $ntUrl ) {
		global $nt_store;
		$this->_id   = $id;
		$this->_dir  = $nt_store->getPostDir( $id, null ) . $mediaDirName . '/';
		$this->_url  = $nt_store->getPostUrl( $id, null ) . $mediaDirName . '/';
		$this->_meta = $nt_store->getPostDir( $id, null ) . 'media.json';

		$this->_ntUrl = $ntUrl;
	}


	// -------------------------------------------------------------------------


	public function getItemList( ?string $filter = null ): array {
		$meta = $this->_loadMeta();

		$list = [];
		foreach ( $meta as $m ) {
			$item = [];
			$ext = $m['extension'];
			if ( in_array( $ext, [ 'png', 'jpeg', 'jpg' ], true ) ) {
				$item['is_image']   = true;
				$item['sizes_json'] = json_encode( $this->_createSizesWithUrl( $m['sizes'] ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

				if ( isset( $m['sizes']['small'] ) ) {
					$item['url@min'] = $this->_mediaUrl( $m['sizes']['small']['file_name'] );
				} else {
					$item['url@min'] = $this->_mediaUrl( $m['sizes']['full']['file_name'] );
				}
			} else if ( $filter === 'image' ) continue;
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
		uasort( $ret, function ( $a, $b ) { return $a['width'] <=> $b['width']; } );
		return $ret;
	}

	private function _mediaUrl( string $fileName ): string {
		return $this->_ntUrl . '?' . rawurlencode( $this->_id ) . '=' . rawurlencode( $fileName );
	}

	public function upload( array $file ): bool {
		$tmpFile      = $file['tmp_name'];
		$origFileName = $file['name'];

		$fileName = $this->_getUniqueFileName( $origFileName );
		if ( empty( $fileName ) ) {
			Logger::output( 'error', "(Media::upload) File uploading failed [$fileName]" );
			return false;
		}
		$path = $this->_dir . $fileName;
		if ( is_uploaded_file( $tmpFile ) ) {
			if ( ! is_dir( $this->_dir ) ) mkdir( $this->_dir, NT_MODE_DIR );
			if ( is_dir( $this->_dir ) ) @chmod( $this->_dir, NT_MODE_DIR );

			if ( move_uploaded_file( $tmpFile, $path ) ) {
				@chmod( $path, NT_MODE_FILE );
				$this->_addMeta( $fileName );
				Logger::output( 'info', "(Media::upload) File uploading succeeded [$fileName]" );
				return true;
			}
		}
		Logger::output( 'error', "(Media::upload) File uploading failed [$fileName]" );
		return false;
	}

	public function remove( string $fileName ): void {
		$fileNames = $this->_removeMeta( $fileName );

		foreach ( $fileNames as $fn ) {
			$path = $this->_dir . $fn;
			if ( ! is_file( $path ) ) continue;
			$res = unlink( $path );
			if ( $res === false ) {
				Logger::output( 'error', "(Media::remove) Cannot remove the file [$path]" );
			}
		}
	}

	private function _getUniqueFileName( string $fileName, string $postFix = '' ): string {
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

	private function _isFileExist( string $fileName ): bool {
		return is_dir( $this->_dir ) && is_file( $this->_dir . $fileName );
	}


	// -------------------------------------------------------------------------


	private function _loadMeta(): array {
		if ( $this->_data ) return $this->_data;

		$path = $this->_meta;
		if ( is_file( $path ) && is_readable( $path ) ) {
			$json = file_get_contents( $path );
			if ( $json === false ) {
				Logger::output( 'error', "(Media::_loadMeta) Cannot read the meta data [$path]" );
				return [];
			}
			$data = json_decode( $json, true );
			if ( $data === null ) {
				Logger::output( 'error', "(Media::_loadMeta) The meta data is invalid [$path]" );
			}
			return $this->_data = $data ?? [];
		}
		return $this->_data = [];
	}

	private function _saveMeta( array $data ): void {
		$this->_data = $data;
		$json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		$path = $this->_meta;
		$res = file_put_contents( $path, $json, LOCK_EX );
		if ( $res === false ) {
			Logger::output( 'error', "(Media::_saveMeta) Cannot write the meta data [$path]" );
			return;
		}
		@chmod( $path, NT_MODE_FILE );
	}

	private function _addMeta( string $fileName ): void {
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

	private function _removeMeta( string $fileName ): array {
		$meta = $this->_loadMeta();
		$removed = [ $fileName ];
		$idx = -1;

		foreach ( $meta as $i => $m ) {
			if ( $m['file_name'] !== $fileName ) continue;
			$idx = $i;
			if ( isset( $m['sizes'] ) ) {
				foreach ( $m['sizes'] as $s => $d ) {
					if ( isset( $d['file_name'] ) ) $removed[] = $d['file_name'];
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


	// -------------------------------------------------------------------------


	private function _resizeImage( string $fileName, string $ext ): array {
		list( $width, $height ) = getimagesize( $this->_dir . $fileName );
		$mime = mime_content_type( $this->_dir . $fileName );

		global $nt_config;
		$sizes = [];
		$sizes['full'] = [ 'file_name' => $fileName, 'width' => $width, 'height' => $height ];

		$min = intval( $nt_config['image_sizes']['small']['width'] );
		if ( $min < $width ) {
			$img = $this->_loadImage( $fileName, $mime );
			foreach ( $nt_config['image_sizes'] as $key => $d ) {
				$key = str_replace( '_', '-', $key );
				$px = intval( $d['width'] );
				if ( $px < $width ) {
					list( $fn, $w, $h ) = $this->_saveScaledImage( $img, $fileName, $mime, $px );
					$sizes[ $key ] = [ 'file_name' => $fn, 'width' => $w, 'height' => $h ];
				}
			}
			imagedestroy( $img );
		}
		return $sizes;
	}

	private function _saveScaledImage( $img, string $fn, string $mime, int $size ): array {
		$newImg = $this->_scaleImage( $img, $size, $mime );

		$mat = [ [ 0, -1, 0 ], [ -1, 12, -1 ], [ 0, -1, 0 ] ];
		$div = 8;
		imageconvolution( $newImg, $mat, $div, 0 );

		$newFn = $this->_getUniqueFileName( $fn, "-$size" );
		$this->_saveImage( $newFn, $mime, $newImg );
		$ret = [ $newFn, imagesx( $newImg ), imagesy( $newImg ) ];
		imagedestroy( $newImg );
		return $ret;
	}

	private function _scaleImage( $img, int $newW, string $mime ) {
		$w = imagesx( $img );
		$h = imagesy( $img );
		$newH = $h * $newW / $w;
		$newImg = imagecreatetruecolor( $newW, $newH );

		if ( $mime === 'image/png' || $mime === 'image/x-png' ) {
			imagealphablending( $newImg, false );
			imagefill( $newImg, 0, 0, imagecolorallocatealpha( $newImg, 0, 0, 0, 127 ) );
			imagesavealpha( $newImg, true );
		} else if ( $mime === 'image/gif' ) {
			$idx = imagecolortransparent( $img );
			if ( 0 <= $idx ) {
				$c = imagecolorsforindex( $img, $idx );
				$newIdx = imagecolorallocate( $newImg, $c['red'], $c['green'], $c['blue'] );
				imagefill( $newImg, 0, 0, $newIdx );
				imagecolortransparent( $newImg, $newIdx );
			}
		}
		imagecopyresampled( $newImg, $img, 0, 0, 0, 0, $newW, $newH, $w, $h );
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
