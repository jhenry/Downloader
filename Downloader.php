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
         * Performs install operations for plugin. Called when user clicks install
         * plugin in admin panel.
         *
         */
        public function install()
        {
		$roles = array('admin', 'mod');
		Settings::set('downloader_roles', json_encode($roles));
        }

        /**
         * Performs uninstall operations for plugin. Called when user clicks
         * uninstall plugin in admin panel and prior to files being removed.
         *
         */
        public function uninstall()
        {
                Settings::remove('downloader_roles');
        }

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
	public static function display_download_button($video)
	{
		if (Downloader::check_permissions($video)) {
			$videoService = new VideoService();
			$file = Downloader::get_file_path($video);
			$filesize = Functions::formatBytes(filesize($file));
			include(dirname(__FILE__) . '/download-button.php');	
		}
	}

	/**
	 * Download the original media file.
	 * 
	 */
	public static function download_file()
	{
		if( isset($_GET['download']) ){
			$videoId = $_GET['download'];
			$videoMapper = new VideoMapper();
			$video = $videoMapper->getVideoById($videoId);	

			if( Downloader::check_permissions($video) ){
				$file = Downloader::get_file_path($video);
				$filename = Downloader::get_file_name($video);
				Downloader::send_file($file, $filename);
			}
		}
		
	}

	
	/**
	 * Send headers to begin download.
	 * 
	 * @param string $file The full path to the original media file, including the filename..
	 * @param string $filename The name the file will be saved as when downloaded.
	 */
	private function send_file($file, $filename)
	{
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
	/**
	 * Build a file path and extension
	 * 
	 * @param Video $video Object represtenting the media.  
	 * @return string $path The path to the media file.
	 */
	private function get_file_path($video)
	{
		$directory = '/temp/';
		if (class_exists('Wowza')) {
			$wowza_root = Settings::get('wowza_upload_dir');
			$uploads_base = $wowza_root . Wowza::get_video_owner_homedir($video->videoId);
		} 
		else {
			$uploads_base = UPLOAD_PATH;
		}
		$path = $uploads_base . $directory . $video->filename . '.' . $video->originalExtension;
		return $path;

	}

	/**
	 * Set a pretty filename for the download.  
	 * 
	 * @param Video $video Object containing video info. 
	 * @return string $filname The new filename for the media that is being downloaded.
	 */
	private function get_file_name($video)
	{
		$slug = Functions::createSlug($video->title);
		$filename = $slug ?? $video->filename;
		$filename = $filename . '.' . $video->originalExtension;
		return $filename;

	}


	/**
	 * Confirm that current user has proper permissions on this video.
	 * 
	 * @param Video $video Object containing video information to compare ownership. 
	 */
	private static function check_permissions($video)
	{
		$authService = new AuthService();
		$user = $authService->getAuthUser();
		$allowed_roles = json_decode( Settings::get('downloader_roles') );
	
		// If there is a logged in session
		if ($user) {
			// If the logged in user matches the owner of the video.
			if($video->userId == $user->userId) {
				return true;
			}
			// Alternatively, if they belong to an allowed role set
			if (in_array($user->role, $allowed_roles)) {
				return true;
			} 
		}
		return false;
	}

}
