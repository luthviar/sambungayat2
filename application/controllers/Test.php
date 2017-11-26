<?php 
   class Test extends CI_Controller {  
		
	 function __construct()
	  {
	    parent::__construct();
	    $this->load->model('tebakkode_m');
	  }
      public function index() { 
      	$info_surat = $this->tebakkode_m->getInfoSurat(78);
      	return $info_surat;
         // echo "This is default functionsss."; 
      } 
  
      public function hello() { 
         echo "This is hello function."; 
      } 
   } 
?>