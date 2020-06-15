<?php

class Downloader extends PluginAbstract
{
	/**
	 * @var string Name of plugin
	 */
	public $name = 'Downloader';

	/**
	 * @var string Description of plugin
	 */
	public $description = 'Allow users to download their original media files.';

	/**
	 * @var string Name of plugin author
	 */
	public $author = 'Justin Henry';

	/**
	 * @var string URL to plugin's website
	 */
	public $url = 'https://uvm.edu/~jhenry/';

	/**
	 * @var string Current version of plugin
	 */
	public $version = '0.1.0';

	/**
	 * Attaches plugin methods to hooks in code base
	 */
	public function load()
	{
		Plugin::attachEvent('watch.share', array(__CLASS__, 'display_download_button'));
		Plugin::attachEvent('watch.start', array(__CLASS__, 'download_file'));
	}

	/**
	 * Show a button/link to allow users to download the original media file.
	 * 
	 */
	public function display_download_button()
	{
		
	}

	/**
	 * Download the original media file.
	 * 
	 */
	public function download_file()
	{
		if( isset($_GET['download']) ){
			$videoId = $_GET['download'];
			$videoMapper = new VideoMapper();
			$video = $videoMapper->getVideoById($videoId);	

			$file = UPLOAD_PATH . '/temp/' . $video->filename . '.' . $video->originalExtension;
			$slug = Functions::createSlug($video->title);
			$filename = $slug ?? $video->filename;
			$filename = $filename . '.' . $video->originalExtension;


			if (file_exists($file)) {
				header('Content-Description: File Download');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'. $filename .'"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));
				readfile($file);
				exit;
			}	

		}
		
	}
}
