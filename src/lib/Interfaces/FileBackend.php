<?php
namespace Repositorium\Interfaces;

interface FileBackend
{
	public function __construct($container);
	public function fileExists($file);
	public function isDirectory($file);
	public function isBinary($file);
	public function versionIsBinary($file, $version);
	public function getDirectoryFiles($directory);
	public function getFileMtime($file);
	public function getVersionMtime($file, $version);
	public function getFileContent($file);
	public function getFileVersion($file, $version);
	public function getFileHistory($file);
	public function getFileDiff($file, $range);
	public function getFileMimetype($file);
	public function getFileVersionMimetype($file, $version);
	public function storeFile($file, $content, $commitmsg);
	public function commitUploadedFile($file, $target, $commitmsg);
	public function restoreFileVersion($file, $version);
	public function moveFile($file, $target);
	public function deleteFile($file);
	public function getStreamInterface($file);
	public function getVersionStreamInterface($file, $version);
}
