<?php
namespace nt;
/*
 * Media Manager
 * 2017-01-27
 *
 */

class Media {

	const MODE_DIR = 0755;
	const MODE_FILE = 0644;
	const MEDIA_DIR_NAME = 'media';

	function __construct($postPath, $postUrl, $id) {
		$this->_id = $id;
		$this->_mediaPath = $postPath . $id . '/' . self::MEDIA_DIR_NAME . '/';
		$this->_mediaUrl = $postUrl . $id . '/' . self::MEDIA_DIR_NAME . '/';
	}

	function upload($file) {
		$tmpFile = $file['tmp_name'];
		$origFileName = $file['name'];

		$fileName = $this->ensureValidFileName($origFileName);
		if ($fileName === '') return false;
		return $this->uploadActually($tmpFile, $fileName);
	}

	private function ensureValidFileName($fileName) {
		if (!$this->isFileExist($fileName)) {
			return $fileName;
		}
		$pi = pathinfo($fileName);
		$ext = '.' . $pi['extension'];
		$name = $pi['filename'];

		for ($num = 1; $num <= 256; $num += 1) {
			$nfn = $name . '[' . $num . ']' . $ext;
			if (!$this->isFileExist($nfn)) {
				return $nfn;
			}
		}
		return '';
	}

	private function isFileExist($fileName) {
		if (!file_exists($this->_mediaPath)) return false;
		$path = $this->_mediaPath . $fileName;
		return file_exists($path);
	}

	private function uploadActually($temp, $fileName) {
		$path = $this->_mediaPath . $fileName;
		if (is_uploaded_file($temp)) {
			if (!file_exists($this->_mediaPath)) {
				mkdir($this->_mediaPath, self::MODE_DIR);
			}
			if (move_uploaded_file($temp, $path)) {
				chmod($path, self::MODE_FILE);
				return true;
			}
		}
		return false;
	}

	function getItemList() {
		$fileList = $this->getFileList();
		$list = [];
		foreach ($fileList as $fn) {
			$item = [];
			$item['caption'] = $fn;
			$item['file'] = $fn;
			$item['url'] = $this->_mediaUrl . _u($fn);
			$ext = mb_strtolower(pathinfo($fn, PATHINFO_EXTENSION));
			if ($ext === 'png' || $ext === 'jpeg' || $ext === 'jpg') {
				$item['img'] = true;
				list($width, $height) = getimagesize($this->_mediaPath . $fn);
			} else {
				$width = 0;
				$height = 0;
			}
			$item['ext'] = $ext;
			$item['width'] = $width;
			$item['height'] = $height;
			$list[] = $item;
		}
		return $list;
	}

	private function getFileList() {
		if (!file_exists($this->_mediaPath)) {
			return [];
		}
		$files = [];
		if ($dir = opendir($this->_mediaPath)) {
			while (($fn = readdir($dir)) !== false) {
				if (strpos($fn, '.') !== 0) {
					$files[] = $fn;
				}
			}
			closedir($dir);
		}
		sort($files, SORT_STRING);
		return $files;
	}

	public function remove($fileName) {
		$path = $this->_mediaPath . $fileName;
		@unlink($path);
	}

}
