<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Errors extends MY_Controller {

	public function __construct() {
		parent::__construct();
	}

	public function show404() {
	    $data = self::common_data();

        $data['header'] = $this -> load-> view('front/header.html',$data,true);
        $data['footer'] = $this -> load-> view('front/footer.html',$data,true);
		$this -> load -> view('front/404.html',$data);
	}



}

/* End of file errors.php */
/* Location: ./application/controllers/errors.php */
