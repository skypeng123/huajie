<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_FTP extends CI_FTP {

	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Create a directory
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function mkdir($path = '', $permissions = NULL)
	{		
		$directory = str_replace(array( "\n", "\r", '..'), '', $path);
		$epath = explode('/', $directory);
		$dir = '';$comma = '';
		
		foreach($epath as $path) {
			$dir .= $comma.$path;
			$comma = '/';
			$result = @ftp_mkdir($this->conn_id, $dir);
			
			if ($result && ! is_null($permissions))
				$this->chmod($dir,$permissions);
		}
		return $result;
	}
	
	/**
	 * Upload a file to the server
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	function upload($locpath, $rempath, $mode = 'auto', $permissions = NULL)
	{
		if ( ! $this->_is_conn())
		{
			return FALSE;
		}
	
		if ( ! file_exists($locpath))
		{
			$this->_error('ftp_no_source_file');
			return FALSE;
		}
	
		// Set the mode if not specified
		if ($mode == 'auto')
		{
			// Get the file extension so we can set the upload type
			$ext = $this->_getext($locpath);
			$mode = $this->_settype($ext);
		}
	
		$mode = ($mode == 'ascii') ? FTP_ASCII : FTP_BINARY;
		
		
		/*创建目录*/
		$dirname = ltrim(dirname($rempath),'/');
		$filename = basename($rempath);	
		
		if(!$this->changedir($dirname)) {			
			if($this->mkdir($dirname)) {
				$this->chmod($dirname,$permissions);
				if(!$this->changedir($dirname)) {
					$this->_error('ftp_unable_to_upload');
				}
			}
		}
	
		$result = @ftp_put($this->conn_id, $rempath, $locpath, $mode);
	
		if ($result === FALSE)
		{
			if ($this->debug == TRUE)
			{
				$this->_error('ftp_unable_to_upload');
			}
			return FALSE;
		}
	
		// Set file permissions if needed
		if ( ! is_null($permissions))
		{
			$this->chmod($rempath, (int)$permissions);
		}
	
		return TRUE;
	}
}
// END FTP Class

/* End of file Ftp.php */
/* Location: ./system/libraries/Ftp.php */