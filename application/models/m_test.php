<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
/**
 *model to Facilities entities
 */
//use application\models\Entities\Facilities;

class M_Test extends MY_Model {
	var $facility;

	function __construct() {
		parent::__construct();
	}

	
	public function getFacility($id){
		$fac = $this->getfac_name($id);
		var_dump($fac);
	}
	

	
	
}//end of class M_Autocomplete 
