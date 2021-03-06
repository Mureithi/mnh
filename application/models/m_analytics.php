<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
/**
 * @description Model contains query definitions to extract data for analytical purposes
 * @param entities
 * @author John Adamsy
 * @modified June 14th 2013
 * */

//for the query builder
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class M_Analytics extends MY_Model {
	/*user variables*/
	var $dataSet, $final_data_set, $query, $rsm, $districtName, $countyFacilities;

	/*constructor*/
	function __construct() {
		parent::__construct();
		//var initialization
		$this -> dataSet = $this -> query = null;

	}

	public function get_facility_reporting_summary($survey) {

		/*using CI Database Active Record*/
		try {
			$query = "SELECT fac_mfl,fac_name,fac_district,fac_county,fac_incharge_contact_person,fac_incharge_email,fac_updated 
	                 FROM facilities   ORDER BY fac_name ASC";
			$this -> dataSet = $this -> db -> query($query, array($survey));
			$this -> dataSet = $this -> dataSet -> result_array();

			if ($this -> dataSet) {
				return $this -> dataSet;
			} else {
				return $this -> dataSet = false;
			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

	}

	/**
	 * Community Strategy
	 */
	public function getCommunityStrategy($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		//$data=array();
		$data = '';

	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		$query = "SELECT 
    cs.strategy_code AS strategy,
    SUM(cs.cs_response) AS strategy_number
FROM
    community_strategies cs
WHERE
    cs.strategy_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = 'cms')
        AND cs.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
AND cs.cs_response!=-1
GROUP BY cs.strategy_code ASC;";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$i = 1;
				$size = count($this -> dataSet);
				foreach ($this->dataSet as $value) {
					switch($this -> getCommunityStrategyName($value['strategy'])) {
						case 'Total number of Community Units established and functional' :
							$strategy = 'CU established and functional';
							break;
						case 'Total number of Community Units regularly supervised and provided feedback' :
							$strategy = 'CU regularly supervised';
							break;
						case 'Total number of CHWs and CHEWs trained on Integrated Community Case Management (ICCM)' :
							$strategy = 'CHWs & CHEWs on ICCM';
							break;
						case 'Total number of Community Units supported by incentives for CHWs' :
							$strategy = 'CU supported by incentives for CHW';
							break;
						case 'Total number of cases treated with Zinc and ORS co-pack under Community Case Management of diarrhoea' :
							$strategy = 'Cases treated with Zinc & ORS';
							break;
					}
					if ($i == $size) {
						$data[] = array($strategy, $value['strategy_number']);
					} else {
						$data[] = array($strategy, $value['strategy_number']);
						$i++;
					}
				}
				$this -> dataSet = $data;
				return $this -> dataSet;
			} else {
				return $this -> dataSet = null;
			}
			//die(var_dump($this->dataSet));
		} catch(Exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

	}

	/*
	 * Guidelines Availability
	 */
	public function getGuidelinesAvailability($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = array();
		$data_prefix_y = '';
		$data_prefix_n = '';
		$data_y = $data_n = $data_categories = array();

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		
				$query = "SELECT 
    COUNT(lq.fac_mfl) AS total_facilities,
    lq.question_code AS guideline,
    lq.lq_response AS availability
FROM
    log_questions lq
WHERE
    lq.question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = 'gp')
        AND lq.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
        JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = '".$survey."')
".$criteria_condition.")
GROUP BY lq.lq_response , lq.question_code
ORDER BY lq.lq_response ASC";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();
					if ($this -> dataSet !== NULL) {
						//prep data for the pie chart format
						$size = count($this -> dataSet);
						$i = 0;

						//get a set of the 4 guidelines
						$data['categories'] = array('2012 IMCI', 'ORT Corner', 'ICCM', 'Paediatric Protocol');

						//$data['categories'] = json_encode($data_categories);

						foreach ($this->dataSet as $value) {
							switch($this -> getTrainingGuidelineName($value['guideline'])) {
								case 'Does the facility have updated 2012 IMCI guidelines?' :
									$guideline = '2012 IMCI';
									break;
								case 'Does the facility have updated ORT Corner guidelines?' :
									$guideline = 'ORT Corner';
									break;
								case 'Does the facility have an updated Paediatric Protocol?' :
									$guideline = 'Paediatric Protocol';
									break;
								case 'Does the facility have updated ICCM guidelines?' :
									$guideline = 'ICCM';
									break;
							}

							if ($value['availability'] == 'Yes') {
								$data_y[] = array($guideline => (int)$value['total_facilities']);
							} else {
								$data_n[] = array($guideline => (int)$value['total_facilities']);
							}
							//$data['categories'][]=$guideline;
						}

						$data['yes_values'] = $data_y;
						$data['no_values'] = $data_n;

						$this -> dataSet = $data;

						//var_dump($this->dataSet);die;

					} else {
						return $this -> dataSet = null;
					}
					//die(var_dump($this->dataSet));
				} catch(exception $ex) {
					//ignore
					//die($ex->getMessage());//exit;
				}
				
		return $this -> dataSet;
	}

	/*
	 * Trained Staff
	 */
	public function getTrainedStaff($criteria, $value,  $survey) {
		$value = urldecode($value);
		/*using CI Database Active Record*/
		$data = array();
		$data_prefix_y = '';
		//"name:'Trained (Last 2 years)',data:";
		$data_prefix_n = '';
		//"name:'Trained & Working in CH',data:";
		$data_t = $data_w = $data_categories = array();

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		$query = "SELECT 
    COUNT(gt.fac_mfl) AS facilities,
    gt.guide_code AS training,
    sum(gt.tg_trained_before_2010) AS trained,
    sum(gt.tg_working) AS working
FROM
    training_guidelines gt
WHERE
    gt.guide_code IN (SELECT 
            guide_code
        FROM
            guidelines
        WHERE
            guide_for = '".$survey."')
        AND gt.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
         JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = '".$survey."')".$criteria_condition.")
GROUP BY gt.guide_code
ORDER BY gt.guide_code ASC";

		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);
				$i = 0;
//var_dump($this->dataSet);die;
				foreach ($this->dataSet as $value) {
					//if(isset($value['trained'])){
					$data_t[$this -> getStaffTrainingGuidelineById($value['training'])] = (int)($value['trained']);
					//}else if(isset($value['working'])){
					$data_w[$this -> getStaffTrainingGuidelineById($value['training'])] = (int)($value['working']);
					//}

					//get a set of the 3 staff trainings
					//$data_categories[] = $this -> getStaffTrainingGuidelineById($value['training']);
				}

				$data['categories'] = json_encode($data_categories);

				$data['trained_values'] = $data_t;
				$data['working_values'] = $data_w;

				$this -> dataSet = $data;

				//var_dump($this->dataSet);die;
				return $this -> dataSet;
			} else {
				return $this -> dataSet = null;
			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

	}

	/*
	 * Commodity Availability
	 */
	public function getCommodityAvailability($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */
	switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		/*--------------------begin commodities availability by frequency----------------------------------------------*/
		$query = "SELECT count(ca.ac_Availability) AS total_response,ca.comm_code as commodities,ca.ac_Availability AS frequency,c.comm_unit as unit FROM available_commodities ca,commodities c
					WHERE ca.comm_code=c.comm_code AND ca.fac_mfl IN (SELECT fac_mfl FROM facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.") 
					AND ca.comm_code IN (SELECT comm_code FROM commodities WHERE comm_for='".$survey."')
					GROUP BY ca.comm_code,ca.ac_Availability
					ORDER BY ca.comm_code";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();
			// echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);
				$data_set['Sometimes Available'] = $data_set['Available'] = $data_set['Never Available'] = array();
				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getCommodityNameById($value_['commodities']) . '[' . $value_['unit'] . ']';
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					if ($value_['frequency'] == 'Some Available') {//a hardcore fix...for Nairobi County data only--> (there was a typo in the naming 'Sometimes Available', so Nairobi data has it as 'Some Available')

						$frequency = 'Sometimes Available';
					} else {
						$frequency = $value_['frequency'];
					}
					$analytic_var[] = $frequency;
					//includes duplicates--so we'll array_unique outside the foreach()
					#Declare Arrays

					//collect the data_sets for the 3 analytic variables under availability
					if ($frequency == 'Available') {
						$data_set['Available'][$this -> getCommodityNameById($value_['commodities']) . '[' . $value_['unit'] . ']'][] = intval($value_['total_response']);
					} else if ($frequency == 'Sometimes Available') {
						$data_set['Sometimes Available'][$this -> getCommodityNameById($value_['commodities']) . '[' . $value_['unit'] . ']'][] = intval($value_['total_response']);
					} else if ($frequency == 'Never Available') {
						$data_set['Never Available'][$this -> getCommodityNameById($value_['commodities']) . '[' . $value_['unit'] . ']'][] = intval($value_['total_response']);
					}

				}

				//var_dump($data_set);die;

				//make cat array unique if we got duplicates then json_encode and set to $data array
				$data['categories'] = (array_values(array_unique($data_categories)));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array_unique($analytic_var);
				//expected to be 3 in this particular context
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 3 analytic variables: Available | Sometimes Available | Never Available

				$this -> final_data_set['frequency'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				//unset the arrays for reuse in the next query
				$data = $data_set = $data_series = $analytic_var = $data_categories = array();

				//return $this -> final_data_set;
				//var_dump($this -> final_data_set);die;

			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end commodities availability by frequency----------------------------------------------*/

		/*--------------------begin commodities reason for unavailability----------------------------------------------*/
		$this -> dataSet = array();
		$query = "SELECT count(ca.ac_reason_unavailable) AS total_response,ca.comm_code as commodities,ca.ac_reason_unavailable AS reason, c.comm_unit as unit FROM available_commodities ca,commodities c
					WHERE ca.comm_code=c.comm_code AND ca.fac_mfl IN (SELECT fac_mfl FROM facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.") 
					AND ca.comm_code IN (SELECT comm_code FROM commodities WHERE comm_for='".$survey."')
					AND ca.ac_reason_unavailable !='Not Applicable'
					GROUP BY ca.comm_code,ca.ac_reason_unavailable
					ORDER BY ca.comm_code,reason ASC";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getCommodityNameById($value_['commodities']) . '[' . $value_['unit'] . ']';
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					$analytic_var[] = $value_['reason'];
					//includes duplicates--so we'll array_unique outside the foreach()

					//collect the data_sets
					if ($value_['reason'] == 'All Used') {
						$data_set[$value_['reason']][] = intval($value_['total_response']);
					} else if ($value_['reason'] == 'Expired') {
						$data_set[$value_['reason']][] = intval($value_['total_response']);
					} else if ($value_['reason'] == 'Not Ordered') {
						$data_set[$value_['reason']][] = intval($value_['total_response']);
					} else if ($value_['reason'] == 'Ordered but not yet Received') {
						$data_set[$value_['reason']][] = intval($value_['total_response']);
					}

				}

				//var_dump($data_set);die;

				//make cat array unique if we got duplicates then json_encode and set to $data array
				$data['categories'] = (array_values(array_unique($data_categories)));

				//get a unique set of analytic variables
				$analytic_var = array_unique($analytic_var);
				//expected to be 3 in this particular context
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				$this -> final_data_set['unavailability'] = array();
				$this -> final_data_set['unavailability'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				//unset the arrays for reuse in the next query
				$data = $data_set = $data_series = $analytic_var = $data_categories = array();

				//return $this -> final_data_set;
				//var_dump($this -> final_data_set);die;

			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end commodities reason for unavailability----------------------------------------------*/

		/*--------------------begin commodities location of availability----------------------------------------------*/
		$query = "SELECT 
    count(ca.ac_location) AS total_response,
    ca.comm_code as commodities,
    ca.ac_location AS location,
    commodities.comm_unit as unit
FROM
    available_commodities ca,
    commodities
WHERE
    ca.comm_code = commodities.comm_code
        AND ca.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.") 
        AND ca.comm_code IN (SELECT 
            comm_code
        FROM
            commodities
        WHERE
            comm_for = '$survey')
        AND ca.ac_location NOT LIKE '%Not Applicable%'
GROUP BY ca.comm_code , ca.ac_location
ORDER BY ca.comm_code,location ASC";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getCommodityNameById($value_['commodities']);
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					$analytic_var[] = $value_['location'];
					//includes duplicates--so we'll array_unique outside the foreach()

					switch($survey) {
						case 'mnh' :
							//collect the data_sets
							//collect the data_sets from the coma separated responses
							if (strpos($value_['location'], 'Delivery Room') !== FALSE) {
								$data_set['DeliveryRoom'][$this -> getCommodityNameById($value_['commodities'])][] = intval($value_['total_response']);
							}
							if (strpos($value_['location'], 'Pharmacy') !== FALSE) {
								$data_set['Pharmacy'][$this -> getCommodityNameById($value_['commodities'])][] = intval($value_['total_response']);
							}
							if (strpos($value_['location'], 'Store') !== FALSE) {
								$data_set['Store'][$this -> getCommodityNameById($value_['commodities'])][] = intval($value_['total_response']);
							}
							if (strpos($value_['location'], 'Other') !== FALSE) {
								$data_set['Other'][$this -> getCommodityNameById($value_['commodities'])][] = intval($value_['total_response']);
							}

							break;
						case 'mch' :
							//collect the data_sets
							if (strpos($value_['location'], 'OPD') !== FALSE) {
								$data_set['OPD'][$this -> getCommodityNameById($value_['commodities'])][] = intval($value_['total_response']);
							}
							if (strpos($value_['location'], 'MCH') !== FALSE) {
								$data_set['MCH'][$this -> getCommodityNameById($value_['commodities'])][] = intval($value_['total_response']);
							}
							if (strpos($value_['location'], 'U5 Clinic') !== FALSE) {
								$data_set['U5 Clinic'][$this -> getCommodityNameById($value_['commodities'])][] = intval($value_['total_response']);
							}
							if (strpos($value_['location'], 'Ward') !== FALSE) {
								$data_set['Ward'][$this -> getCommodityNameById($value_['commodities'])][] = intval($value_['total_response']);
							}
							if (strpos($value_['location'], 'Other') !== FALSE) {
								$data_set['Other'][$this -> getCommodityNameById($value_['commodities'])][] = intval($value_['total_response']);
							}
							break;
					}

				}

				//var_dump($data_set[2]);die;

				//make cat array unique if we got duplicates then json_encode and set to $data array
				$data['categories'] = array_values(array_unique($data_categories));
				//expected 5

				//get a unique set of analytic variables
				$analytic_var = array('OPD', 'MCH', 'U5 Clinic', 'Ward', 'Other');
				//we know of these 5 in this particular context

				//get the data sets
				$data['responses'] = $data_set;

				$this -> final_data_set['location'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				//unset the arrays for reuse in the next query
				$data = $data_set = $data_series = $analytic_var = $data_categories = array();

				//return $this -> final_data_set;
				//var_dump($this -> final_data_set);die;

			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end commodities location of availability----------------------------------------------*/

		/*--------------------begin commodities availability by quantity----------------------------------------------*/
		$query = "SELECT 
    SUM(ca.ac_quantity) AS total_quantity,
    ca.comm_code as commodities,commodities.comm_unit AS unit
FROM
    available_commodities ca,commodities
WHERE
commodities.comm_code=ca.comm_code AND
    ca.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.") 
        AND ca.comm_code IN (SELECT 
            comm_code
        FROM
            commodities
        WHERE
            comm_for = '$survey')
        AND ca.ac_quantity != - 1
GROUP BY ca.comm_code
ORDER BY ca.comm_code";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getCommodityNameById($value_['commodities']) . '[' . $value_['unit'] . ']';
					//includes duplicates--so we'll array_unique outside the foreach()

					//2. collect the analytic variables
					$analytic_var[] = $this -> getCommodityNameById($value_['commodities']) . '[' . $value_['unit'] . ']';
					//includes duplicates--so we'll array_unique outside the foreach()

					//collect the data_sets by commodities
					$data_set[$this -> getCommodityNameById($value_['commodities']) . '[' . $value_['unit'] . ']'] = intval($value_['total_quantity']);

				}
				//var_dump($data_categories);die;
				//var_dump($analytic_var);die;

				//make cat array unique if we got duplicates then json_encode and set to $data array
				$data['categories'] = array_values(array_unique($data_categories));
				//expected 5

				//get a unique set of analytic variables
				$analytic_var = array_unique($analytic_var);

				//get the data sets
				$data['responses'] = $data_set;

				$this -> final_data_set['quantities'] = $data;
				$data = $data_set = $data_series = $analytic_var = $data_categories = array();
				//unset the arrays for reuse

				/*--------------------end commodities availability by quantity----------------------------------------------*/

				return $this -> final_data_set;
			} else {
				return $this -> final_data_set = null;
			}
			//die(var_dump($this->final_data_set));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
	}

	/*
	 * Availability, Location and Functionality of Equipment at ORT Corner
	 */
	public function getORTCornerEquipmement($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */

	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				$value = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		/*--------------------begin ort equipment availability by frequency----------------------------------------------*/
		$query = "SELECT 
    count(ea.ae_availability) AS total_response,
    ea.eq_code as equipment,
    ea.ae_availability AS frequency
FROM
    available_equipments ea
WHERE
    ea.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')".$criteria_condition.")
        AND ea.eq_code IN (SELECT 
            eq_code
        FROM
            equipments
        WHERE
            eq_for = 'ort')
GROUP BY ea.eq_code , ea.ae_availability
ORDER BY ea.eq_code ASC";
		try {

			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getCHEquipmentName($value_['equipment']);
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					if ($value_['frequency'] == 'Some Available') {//a hardcore fix...for Nairobi County data only--> (there was a typo in the naming 'Sometimes Available', so Nairobi data has it as 'Some Available')

						$frequency = 'Sometimes Available';
					} else {
						$frequency = $value_['frequency'];
					}
					$analytic_var[] = $frequency;
					//includes duplicates--so we'll array_unique outside the foreach()

					//collect the data_sets for the 3 analytic variables under availability
					if ($frequency == 'Available') {
						$data_set['Available'][] = intval($value_['total_response']);
					} else if ($frequency == 'Sometimes Available') {
						$data_set['Sometimes Available'][] = intval($value_['total_response']);
					} else if ($frequency == 'Never Available') {
						$data_set['Never Available'][] = intval($value_['total_response']);
					}

				}

				//var_dump($data_set);die;

				//make cat array unique if we got duplicates then json_encode and set to $data array
				$data['categories'] = (array_values(array_unique($data_categories)));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array_unique($analytic_var);
				//expected to be 3 in this particular context
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 3 analytic variables: Available | Sometimes Available | Never Available

				$this -> final_data_set['frequency'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				//unset the arrays for reuse in the next query
				$data = $data_set = $data_series = $analytic_var = $data_categories = array();

				//return $this -> final_data_set;

			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end ort equipment availability by frequency----------------------------------------------*/

		/*--------------------begin ort equipment location of availability----------------------------------------------*/
		$query = "SELECT 
    count(ea.ae_location) AS total_response,
    ea.eq_code as equipment,
    ea.ae_location AS location
FROM
    available_equipments ea
WHERE
    ea.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')".$criteria_condition.")
        AND ea.eq_code IN (SELECT 
            eq_code
        FROM
            equipments
        WHERE
            eq_for = 'ort')
        AND ea.ae_location NOT LIKE '%Not Applicable%'
GROUP BY ea.eq_code , ea.ae_location
ORDER BY ea.eq_code ASC";

		try {
			//echo $query;die;
			//die(print $status.$value);
			$this -> dataSet = $this -> db -> query($query, array($value));
			//var_dump($this->dataSet);die;
			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);
				$count_instances = array('MCH' => 0, 'OPD' => 0, 'U5 Clinic' => 0, 'Ward' => 0, 'Other' => 0);
				//to hold the location instances
				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getCHEquipmentName($value_['equipment']);
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					//$analytic_var[] = $value['location'];-->hard fix outside the loop as values are coma separated...good fix..have v-look up in the db

					//collect the data_sets from the coma separated responses
					if (strpos($value_['location'], 'OPD') !== FALSE) {
						$count_instances['OPD'] += intval($value_['total_response']);
						$data_set[$this -> getCHEquipmentName($value_['equipment'])]['OPD'] = $count_instances['OPD'];
					}
					if (strpos($value_['location'], 'MCH') !== FALSE) {
						$count_instances['MCH'] += intval($value_['total_response']);
						$data_set[$this -> getCHEquipmentName($value_['equipment'])]['MCH'] = $count_instances['MCH'];
					}
					if (strpos($value_['location'], 'U5 Clinic') !== FALSE) {
						$count_instances['U5 Clinic'] += intval($value_['total_response']);
						$data_set[$this -> getCHEquipmentName($value_['equipment'])]['U5 Clinic'] = $count_instances['U5 Clinic'];
					}
					if (strpos($value_['location'], 'Ward') !== FALSE) {
						$count_instances['Ward'] += intval($value_['total_response']);
						$data_set[$this -> getCHEquipmentName($value_['equipment'])]['Ward'] = $count_instances['Ward'];
					}
					if (strpos($value_['location'], 'Other') !== FALSE) {
						$count_instances['Other'] += intval($value_['total_response']);
						$data_set[$this -> getCHEquipmentName($value_['equipment'])]['Other'] = $count_instances['Other'];
					}

				}
				//var_dump($count_instances);die;
				//var_dump($data_set);die;

				//make array unique if we got duplicates and set to $data array
				$data['categories'] = array_values(array_unique($data_categories));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array('OPD', 'MCH', 'U5 Clinic', 'Ward', 'Other');
				//expected to be 5 in this particular context, again we know they r just these 5 :)
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 5 analytic variables: 'OPD', 'MCH', 'U5 Clinic', 'Ward', 'Other'

				$this -> final_data_set['location'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				$data = $data_set = $data_series = $analytic_var = $data_categories = array();
				//unset the arrays for reuse

				//return $this -> final_data_set;
			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end ort equipment location of availability----------------------------------------------*/

		/*--------------------begin ort equipment availability by functionality----------------------------------------------*/
		$query = "SELECT 
    ea.eq_code as equipment,
    SUM(ea.ae_fully_functional) AS total_functional,
    SUM(ea.ae_non_functional) AS total_non_functional
FROM
    available_equipments ea
WHERE
    ea.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')".$criteria_condition.")
        AND ea.eq_code IN (SELECT 
            eq_code
        FROM
            equipments
        WHERE
            eq_for = 'ort')
        AND ea.ae_fully_functional != - 1
        AND ea.ae_non_functional != - 1
GROUP BY ea.eq_code
ORDER BY ea.eq_code ASC";
		//echo $query; die;
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {
					if ($this -> getCHEquipmentName($value_['equipment']) == 'Table spoons' || $this -> getCHEquipmentName($value_['equipment']) == 'Wall Clock/Timing device' || $this -> getCHEquipmentName($value_['equipment']) == 'Weighing scale' || $this -> getCHEquipmentName($value_['equipment']) == 'Thermometer') {

						//1. collect the categories
						$data_categories[] = $this -> getCHEquipmentName($value_['equipment']);
						//includes duplicates--so we'll array_unique outside the foreach()

						//data set by each equipment
						$data_set[$this -> getCHEquipmentName($value_['equipment'])][] = array('Fully-functional' => intval($value_['total_functional']), 'Non-functional' => intval($value_['total_non_functional']));
					}
				}

				//var_dump($analytic_var);die;

				//make cat array unique if we got duplicates and set to $data array
				$data['categories'] = array_values(array_unique($data_categories));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array('Fully-functional', 'Non-functional');
				//expected to be 2 in this particular context

				//assign data set to $data
				$data['responses'] = $data_set;

				//assign $data to $final_data_set
				$this -> final_data_set['quantities'] = $data;
				$data = $data_set = $data_series = $analytic_var = $data_categories = array();
				//unset the arrays for reuse

				/*--------------------end ort equipment availability by quantity----------------------------------------------*/

				// /var_dump($this -> final_data_set['quantities']);die;

				return $this -> final_data_set;
			} else {
				return null;
			}
			//die(var_dump($this->final_data_set));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

	}

	public function getCHCommoditySupplier($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */

		

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		/*--------------------begin equipment main supplier----------------------------------------------*/
		$query = "SELECT count(ca.supplier_code) AS total_response,ca.comm_code as commodities,ca.supplier_code AS supplier, c.comm_unit as unit FROM available_commodities ca,commodities c
				 WHERE ca.comm_code=c.comm_code AND ca.fac_mfl IN (SELECT fac_mfl FROM facilities f
        JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = '".$survey."')".$criteria_condition.")
				 AND ca.comm_code IN (SELECT comm_code FROM commodities WHERE comm_for='".$survey."')
				GROUP BY ca.comm_code,ca.supplier_code
				ORDER BY ca.comm_code";
		try {

			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $value_['supplier'];
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					$analytic_var[] = $this -> getCommodityNameById($value_['commodities']) . '[' . $value_['unit'] . ']';
					//includes duplicates--so we'll array_unique outside the foreach()

					//data set by each analytic variable
					$data_set[$value_['supplier']][] = intval($value_['total_response']);

				}

				//var_dump($data_set);die;

				//make cat array unique if we got duplicates then json_encode and set to $data array
				$data['categories'] = (array_values(array_unique($data_categories)));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array_unique($analytic_var);
				//expected to be 3 in this particular context
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 3 analytic variables: Available | Sometimes Available | Never Available

				$this -> dataSet = $data;

				return $this -> dataSet;

			} else {
				return $this -> dataSet = null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
	}

	/*
	 * Services to Children with Diarrhoea
	 */
	public function getChildrenServices($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		$data_y = array();
		$data_n = array();
		$temp = $muac = $weight = $height = $mch = array();
		$MCHY = $MCHN = $temperatureY = $temperatureN = $weightY = $weightN = $HLY = $HLN = $MUACY = $MUACN = 0;
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */

	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		
				$query = "SELECT 
    il.indicator_code AS indicator, il.li_response as response
FROM
    log_indicators il
WHERE
    il.indicator_code IN (SELECT 
            indicator_code
        FROM
            indicators
        WHERE
            indicator_for = 'svc')
        AND il.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
        JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = 'mnh')".$criteria_condition.")";

				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();
					//echo $this->db->last_query();die;
					if ($this -> dataSet !== NULL) {
						//prep data for the pie chart format
						$size = count($this -> dataSet);
						$i = 0;
						$yesCount = 0;
						$noCount = 0;

						//var_dump($this->dataSet);

						#Forced One Values
						foreach ($this->dataSet as $value) {
							switch($this->getChildHealthIndicatorName($value['indicator'])) {
								case 'Use of MCH booklet' :
									if ($value['response'] == 'Yes') {
										$MCHY++;
									} else if ($value['response'] == 'No') {
										$MCHN++;
									}
									break;

								case 'Temperature taken' :
									if ($value['response'] == 'Yes') {
										$temperatureY++;
									} else if ($value['response'] == 'No') {
										$temperatureN++;
									}
									break;

								case 'Weight taken' :
									if ($value['response'] == 'Yes') {
										$weightY++;
									} else if ($value['response'] == 'No') {
										$weightN++;
									}
									break;
								case 'Height/Length taken' :
									if ($value['response'] == 'Yes') {
										$HLY++;
									} else if ($value['response'] == 'No') {
										$HLN++;
									}
									break;
								case 'MUAC taken' :
									if ($value['response'] == 'Yes') {
										$MUACY++;
									} else if ($value['response'] == 'No') {
										$MUACN++;
									}
									break;
							}
							//echo $MCHY;
							/*if ($value['response'] == 'Yes') {
							 $data_y[] = array($this -> getChildHealthIndicatorName($value['indicator']), 1);
							 $yesCount++;
							 } else if ($value['response'] == 'No') {
							 $data_n[] = array($this -> getChildHealthIndicatorName($value['indicator']), 1);
							 $noCount++;
							 }*/

							//get a set of the 5 services offered

						}
						$data_categories = array('Use of MCH booklet', 'Temperature taken', 'Weight taken', 'Height/Length taken', 'MUAC taken');
						$data['categories'] = $data_categories;

						$data['yes_values'] = array((int)$MCHY, (int)$temperatureY, (int)$weightY, (int)$HLY, (int)$MUACY);
						$data['no_values'] = array((int)$MCHN, (int)$temperatureN, (int)$weightN, (int)$HLN, (int)$MUACN);

						$this -> dataSet = $data;

						//var_dump($this->dataSet);die;
						return $this -> dataSet;
					} else {
						return $this -> dataSet = null;
					}
					//die(var_dump($this->dataSet));
				} catch(exception $ex) {
					//ignore
					//die($ex->getMessage());//exit;
				}
				
		return $this -> dataSet;
	}

	/*
	 * Danger Signs assessed in Ongoing Sessions
	 */
	public function getDangerSigns($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		$data_y = array();
		$data_n = array();
		$lethargy = $breastfeed = array();
		$breastFeedY = $breastFeedN = $lethargyY = $lethargyN = 0;

		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */

	
		// echo $criteria;die;
		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		
				$query = "SELECT 
    il.indicator_code AS indicator, il.li_response as response
FROM
    log_indicators il
WHERE
    il.indicator_code IN (SELECT 
            indicator_code
        FROM
            indicators
        WHERE
            indicator_for = 'sgn')
        AND il.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
        JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = 'mnh')".$criteria_condition.")";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();
					//echo $this->db->last_query();die;
					if ($this -> dataSet !== NULL) {
						//prep data for the pie chart format
						$size = count($this -> dataSet);
						$i = 0;
						//var_dump($this -> dataSet);
						$data_categories = array('Inability to drink or breastfeed', 'Lethargy and unconsciousness');
						foreach ($this->dataSet as $value) {

							switch($this->getChildHealthIndicatorName($value['indicator'])) {
								case 'Inability to drink or breastfeed' :
									if ($value['response'] == 'Yes') {
										$breastFeedY++;
									} else if ($value['response'] == 'No') {
										$breastFeedN++;
									}
									break;
								case 'Lethargy and unconsciousness' :
									if ($value['response'] == 'Yes') {
										$lethargyY++;
									} else if ($value['response'] == 'No') {
										$lethargyN++;
									}
									break;
							}

							/*if ($value['response'] == 'Yes') {
							 $data_y[] = array($this -> getChildHealthIndicatorName($value['indicator']), 1);
							 } else if ($value['response'] == 'No') {
							 $data_n[] = array($this -> getChildHealthIndicatorName($value['indicator']), 1);
							 }
							 */
							//get a set of the 5 services offered
							//$data_categories[] = $this -> getChildHealthIndicatorName($value['indicator']);
						}

						$data['categories'] = $data_categories;

						$data['yes_values'] = array((int)$breastFeedY, (int)$lethargyY);
						$data['no_values'] = array((int)$breastFeedN, (int)$lethargyN); ;

						$this -> dataSet = $data;

						//var_dump($this->dataSet);die;
						return $this -> dataSet;
					} else {
						return $this -> dataSet = null;
					}
					//die(var_dump($this->dataSet));
				} catch(exception $ex) {
					//ignore
					//die($ex->getMessage());//exit;
				}
		return $this -> dataSet;
	}

	/*
	 * Tasks performed in Ongoing Sessions
	 */
	public function getActionsPerformed($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		$data_y = array();
		$data_n = array();
		$diarrhoea = $blood = $sunken = $fluid = $pinch = $dehydration = array();
		$diarrhoeaY = $diarrhoeaN = $bloodY = $bloodN = $sunkenY = $sunkenN = $fluidY = $fluidN = $pinchY = $pinchN = $dehydrationY = $dehydrationN = 0;
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */

	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		
				$query = "SELECT 
    il.indicator_code AS indicator, il.li_response as response
FROM
    log_indicators il
WHERE
    il.indicator_code IN (SELECT 
            indicator_code
        FROM
            indicators
        WHERE
            indicator_for = 'dgn')
        AND il.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
        JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = 'mnh')".$criteria_condition.")";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();
					if ($this -> dataSet !== NULL) {
						//prep data for the pie chart format
						$size = count($this -> dataSet);
						$i = 0;
						//var_dump($this -> dataSet);
						foreach ($this->dataSet as $value) {
							switch($this->getChildHealthIndicatorName($value['indicator'])) {
								case 'Ask about the duration of diarrhoea' :
									if ($value['response'] == 'Yes') {
										$diarrhoeaY++;
									} else if ($value['response'] == 'No') {
										$diarrhoeaN++;
									}
									break;
								case 'Ask about the presence of Blood in stool' :
									if ($value['response'] == 'Yes') {
										$bloodY++;
									} else if ($value['response'] == 'No') {
										$bloodN++;
									}
									break;
								case 'Look for sunken eyes' :
									if ($value['response'] == 'Yes') {
										$sunkenY++;
									} else if ($value['response'] == 'No') {
										$sunkenN++;
									}
									break;
								case 'Offer the child fluid to drink' :
									if ($value['response'] == 'Yes') {
										$fluidY++;
									} else if ($value['response'] == 'No') {
										$fluidN++;
									}
									break;
								case 'Perform skin pinch' :
									if ($value['response'] == 'Yes') {
										$pinchY++;
									} else if ($value['response'] == 'No') {
										$pinchN++;
									}
									break;
								case 'Correctly assess and classify diarrhoea and dehydration' :
									if ($value['response'] == 'Yes') {
										$dehydrationY++;
									} else if ($value['response'] == 'No') {
										$dehydrationN++;
									}
									break;
							}
							/*if ($value['response'] == 'Yes') {
							 $data_y[] = array($this -> getChildHealthIndicatorName($value['indicator']), 1);
							 } else if ($value['response'] == 'No') {
							 $data_n[] = array($this -> getChildHealthIndicatorName($value['indicator']), 1);
							 }*/

						}
						$data_categories = array('Ask about the duration of diarrhoea', 'Ask about the presence of Blood in stool', 'Look for sunken eyes', 'Offer the child fluid to drink', 'Perform skin pinch', 'Correctly assess and classify diarrhoea and dehydration');
						$data['categories'] = $data_categories;

						$data['yes_values'] = array($diarrhoeaY, $bloodY, $sunkenY, $fluidY, $pinchY, $dehydrationY);
						$data['no_values'] = array($diarrhoeaN, $bloodN, $sunkenN, $fluidN, $pinchN, $dehydrationN);

						$this -> dataSet = $data;

						//var_dump($this->dataSet);die;
						return $this -> dataSet;
					} else {
						return $this -> dataSet = null;
					}
					//die(var_dump($this->dataSet));
				} catch(exception $ex) {
					//ignore
					//die($ex->getMessage());//exit;
				}
		return $this -> dataSet;

	}

	/*
	 * Counsel on Ongoing Sessions
	 */
	public function getCounselGiven($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		$data_y = array();
		$data_n = array();
		$extraY = $extraN = $homeY = $homeN = $followY = $followN = 0;
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */

	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		
				$query = "SELECT 
    il.indicator_code AS indicator, il.li_response as response
FROM
    log_indicators il
WHERE
    il.indicator_code IN (SELECT 
            indicator_code
        FROM
            indicators
        WHERE
            indicator_for = 'cns')
        AND il.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
        JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = 'mnh')".$criteria_condition.")";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();
					if ($this -> dataSet !== NULL) {
						//prep data for the pie chart format
						$size = count($this -> dataSet);
						$i = 0;
						//var_dump($this -> dataSet);
						foreach ($this->dataSet as $value) {
							switch($this->getChildHealthIndicatorName($value['indicator'])) {
								case 'On giving extra feeding' :
									if ($value['response'] == 'Yes') {
										$extraY++;
									} else if ($value['response'] == 'No') {
										$extraN++;
									}
									break;
								case 'On home care' :
									if ($value['response'] == 'Yes') {
										$homeY++;
									} else if ($value['response'] == 'No') {
										$homeN++;
									}
									break;
								case 'On when to return for follow up' :
									if ($value['response'] == 'Yes') {
										$followY++;
									} else if ($value['response'] == 'No') {
										$followN++;
									}
									break;
							}

						}
						$data_categories = array('On giving extra feeding', 'On home care', 'On when to return for follow up');
						$data['categories'] = $data_categories;
						$data['yes_values'] = array($extraY, $homeY, $followY);
						$data['no_values'] = array($extraN, $homeN, $followN);

						$this -> dataSet = $data;

						//var_dump($this->dataSet);die;
						return $this -> dataSet;
					} else {
						return $this -> dataSet = null;
					}
					//die(var_dump($this->dataSet));
				} catch(exception $ex) {
					//ignore
					//die($ex->getMessage());//exit;
				}
				
		return $this -> dataSet;

	}

	/*
	 * Get Tools in Units
	 */

	public function getTools($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		$data_y = array();
		$data_n = array();
		$under5Y = $under5N = $ORTY = $ORTN = $bookY = $bookN = 0;
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		
				$query = "SELECT 
    il.indicator_code AS indicator, il.li_response as response
FROM
    log_indicators il
WHERE
    il.indicator_code IN (SELECT 
            indicator_code
        FROM
            indicators
        WHERE
            indicator_for = 'ror')
        AND il.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
        JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = '$survey')".$criteria_condition.")";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();
					if ($this -> dataSet !== NULL) {
						//prep data for the pie chart format
						$size = count($this -> dataSet);
						$i = 0;
						//var_dump($this -> dataSet);die;
						foreach ($this->dataSet as $value) {
							switch($this->getChildHealthIndicatorName($value['indicator'])) {
								case 'Under 5 register' :
									if ($value['response'] == 'Yes') {
										$under5Y++;
									} else if ($value['response'] == 'No') {
										$under5N++;
									}
									break;
								case 'ORT Corner register (improvised)' :
									if ($value['response'] == 'Yes') {
										$ORTY++;
									} else if ($value['response'] == 'No') {
										$ORTN++;
									}
									break;
								case 'Mother Child Booklet' :
									if ($value['response'] == 'Yes') {
										$bookY++;
									} else if ($value['response'] == 'No') {
										$bookN++;
									}
									break;
							}

							//echo $value['indicator'];
						}
						$data_categories = array('Under 5 register', 'ORT Corner register (improvised)', 'Mother Child Booklet');
						$data['categories'] = $data_categories;
						$data['yes_values'] = array('Under 5 register' => $under5Y, 'ORT Corner register (improvised)' => $ORTY, 'Mother Child Booklet' => $bookY);
						$data['no_values'] = array('Under 5 register' => $under5N, 'ORT Corner register (improvised)' => $ORTN, 'Mother Child Booklet' => $bookN);

						$this -> dataSet = $data;

						//var_dump($this->dataSet);die;
						return $this -> dataSet;
					} else {
						return $this -> dataSet = null;
					}
					//die(var_dump($this->dataSet));
				} catch(exception $ex) {
					//ignore
					//die($ex->getMessage());//exit;
				}
				
		return $this -> dataSet;

	}

	/*
	 * Diarrhoea case numbers per Month
	 */
	public function getDiarrhoeaCaseNumbers($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */

	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		$query = "SELECT SUM(d.jan13) AS jan, SUM(d.feb13) AS feb, SUM(d.mar13) AS mar, SUM(d.apr13) AS apr,
SUM(d.may13) AS may, SUM(d.june13) AS june, SUM(d.july13) AS july, SUM(d.aug13) AS aug,
SUM(d.sept13) AS sept, SUM(d.oct13) AS oct, SUM(d.nov13) AS nov, SUM(d.dec13) AS december
FROM morbidity_data_log d WHERE d.fac_mfl IN (SELECT fac_mfl FROM facilities
WHERE " . $status_condition . "  " . $criteria_condition . ")";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			//echo $this->db->last_query();die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);
				$i = 0;

				foreach ($this->dataSet as $value => $key) {
					$data['num_of_diarrhoea_cases'][] = $key;
				}

				//fixed set of 12 months in a year..not an option but to hard code.. :)
				$data_categories = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

				$data['categories'] = $data_categories;

				$this -> dataSet = $data;

				//var_dump($this->dataSet);die;
				return $this -> dataSet;
			} else {
				return $this -> dataSet = null;
			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

	}

	/*
	 * Diarrhoea case treatments
	 */

	public function getDiarrhoeaCaseTreatment($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */

	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		$query = "SELECT tl.treatmentID AS treatment,SUM(tl.severeDehydrationNo) AS severe_dehydration, SUM(tl.someDehydrationNo) AS some_dehydration,
SUM(tl.noDehydrationNo) AS no_dehydration, SUM(tl.dysentryNo) AS dysentry, SUM(tl.noClassificationNo) AS no_classification
FROM log_treatment tl WHERE tl.treatmentID IN (SELECT treatmentCode FROM mch_treatments
WHERE treatmentFor='dia') AND tl.fac_mfl IN (SELECT fac_mfl FROM facilities WHERE " . $status_condition . "  " . $criteria_condition . ")
GROUP BY tl.treatmentID ORDER BY tl.treatmentID ASC";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			//echo $this->db->last_query();die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);
				$i = 0;

				foreach ($this->dataSet as $value) {
					//get a set of the 5 diarrhoea treatment types available
					$data_categories[] = $this -> getChildHealthTreatmentName($value['treatment']);

					//get the responses by classification per given treatment type
					$data[$this -> getChildHealthTreatmentName($value['treatment'])] = array('severe_dehydration' => intval($value['severe_dehydration']), 'some_dehydration' => intval($value['some_dehydration']), 'no_dehydration' => intval($value['no_dehydration']), 'dysentry' => intval($value['dysentry']), 'no_classification' => intval($value['no_classification']));
				}

				$data['categories'] = $data_categories;

				$this -> dataSet = $data;

				//var_dump($this->dataSet);die;
				return $this -> dataSet;
			} else {
				return $this -> dataSet = null;
			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
	}

	/*
	 * ORT Corner Assessment
	 */
	public function getORTCornerAssessment($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		$data_y = array();
		$data_n = array();
		$functionalTotalY = $functionalTotalN = $rehydrationTotalY = $rehydrationTotalN = $locationY = $locationN = 0;

		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */

	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		$query = "SELECT 
    oa.question_code AS assessment_item, oa.lq_response as response
FROM
    log_questions oa
WHERE
    oa.question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = 'ort')
        AND oa.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = 'ch')".$criteria_condition.")
ORDER BY oa.question_code ASC";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			//echo $this->db->last_query();
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);
				$i = 0;

				foreach ($this->dataSet as $value) {
					switch($this->getQuestionName($value['assessment_item'])) {
						case 'Is the ORT Corner functional?' :
							if ($value['response'] == 'Yes') {
								$functionalTotalY++;
							} else if ($value['response'] == 'No') {
								$functionalTotalN++;
							}
							break;

						case 'Does this Facility have a designated location for oral rehydration?' :
							if ($value['response'] == 'Yes') {
								$rehydrationTotalY++;
							} else if ($value['response'] == 'No') {
								$rehydrationTotalN++;
							}
							break;

						case 'Where is the designated location of the ORT Corner?' :
							if ($value['response'] == 'Yes') {
								$locationY++;
							} else if ($value['response'] == 'No') {
								$locationN++;
							}
							break;
					}

					/*if ($value['response'] == 'Yes') {
					 $data_y[] = array($this -> getChildHealthQuestionName($value['assessment_item']), 1);
					 } else if ($value['response'] == 'No') {
					 $data_n[] = array($this -> getChildHealthQuestionName($value['assessment_item']), 1);
					 }*/

					//get a set of the 3 items for ORT assessment

				}
				$data['categories'] = array('Is the ORT Corner functional?', 'Does this Facility have a designated location for oral rehydration?');
				$data['yes_values'] = array($functionalTotalY, $rehydrationTotalY);
				$data['no_values'] = array($functionalTotalN, $rehydrationTotalN);

				$this -> dataSet = $data;

				//var_dump($this->dataSet);die;
				return $this -> dataSet;
			} else {
				return $this -> dataSet = null;
			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
	}

	/*
	 * Availability, Location and Functionality of Equipment at Equipments Corner
	 */
	public function getMnhEquipment($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */
		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				$value = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		/*--------------------begin mnh equipment availability by frequency----------------------------------------------*/
		$query = "SELECT count(ea.ae_availability) AS total_response,ea.eq_code as equipment,ea.ae_availability AS frequency FROM available_equipments ea WHERE ea.fac_mfl IN (SELECT fac_mfl FROM  
			facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')".$criteria_condition.") AND ea.eq_code IN (SELECT eq_code FROM equipments WHERE eq_for='mnh')
GROUP BY ea.eq_code,ea.ae_availability ORDER BY ea.eq_code ASC";
		try {

			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				$data_set['Available']=$data_set['Sometimes Available']=$data_set['Never Available']=array();
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getCHEquipmentName($value_['equipment']);
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					if ($value_['frequency'] == 'Some Available') {//a hardcore fix...for Nairobi County data only--> (there was a typo in the naming 'Sometimes Available', so Nairobi data has it as 'Some Available')

						$frequency = 'Sometimes Available';
					} else {
						$frequency = $value_['frequency'];
					}
					$analytic_var[] = $frequency;
					//includes duplicates--so we'll array_unique outside the foreach()

					//collect the data_sets for the 3 analytic variables under availability
					if ($frequency == 'Available') {
						$data_set['Available'][] = intval($value_['total_response']);
					} else if ($frequency == 'Sometimes Available') {
						$data_set['Sometimes Available'][] = intval($value_['total_response']);
					} else if ($frequency == 'Never Available') {
						$data_set['Never Available'][] = intval($value_['total_response']);
					}

				}

				//var_dump($data_set);die;

				//make cat array unique if we got duplicates then json_encode and set to $data array
				$data['categories'] = (array_values(array_unique($data_categories)));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array_unique($analytic_var);
				//expected to be 3 in this particular context
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 3 analytic variables: Available | Sometimes Available | Never Available

				$this -> final_data_set['frequency'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				//unset the arrays for reuse in the next query
				$data = $data_set = $data_series = $analytic_var = $data_categories = array();

				//return $this -> final_data_set;

			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end mnh equipment availability by frequency----------------------------------------------*/

		/*--------------------begin mnh equipment location of availability----------------------------------------------*/
		$query = "SELECT count(ea.ae_location) AS total_response,ea.eq_code as equipment,ea.ae_location AS location
FROM available_equipments ea WHERE ea.fac_mfl IN (SELECT fac_mfl FROM 
facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')".$criteria_condition.")
                 AND ea.eq_code IN
(SELECT eq_code FROM equipments WHERE eq_for='mnh')
AND ea.ae_location NOT LIKE '%Not Applicable%' GROUP BY ea.eq_code,ea.ae_location ORDER BY ea.eq_code ASC";

		try {
			//echo $query;die;
			//die(print $status.$value);
			$this -> dataSet = $this -> db -> query($query, array($value));
			//var_dump($this->dataSet);die;
			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);
				$count_instances = array('Delivery room' => 0, 'Pharmacy' => 0, 'Store' => 0, 'Other' => 0);
				//to hold the location instances
				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getCHEquipmentName($value_['equipment']);
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					//$analytic_var[] = $value['location'];-->hard fix outside the loop as values are coma separated...good fix..have v-look up in the db

					//collect the data_sets from the coma separated responses
					if (strpos($value_['location'], 'Delivery room') !== FALSE) {
						$count_instances['Delivery room'] += intval($value_['total_response']);
						$data_set[$this -> getCHEquipmentName($value_['equipment'])]['Delivery room'] = $count_instances['Delivery room'];
					}
					if (strpos($value_['location'], 'Pharmacy') !== FALSE) {
						$count_instances['Pharmacy'] += intval($value_['total_response']);
						$data_set[$this -> getCHEquipmentName($value_['equipment'])]['Pharmacy'] = $count_instances['Pharmacy'];
					}
					if (strpos($value_['location'], 'Store') !== FALSE) {
						$count_instances['Store'] += intval($value_['total_response']);
						$data_set[$this -> getCHEquipmentName($value_['equipment'])]['Store'] = $count_instances['Store'];
					}
					if (strpos($value_['location'], 'Other') !== FALSE) {
						$count_instances['Other'] += intval($value_['total_response']);
						$data_set[$this -> getCHEquipmentName($value_['equipment'])]['Other'] = $count_instances['Other'];
					}

				}
				//var_dump($count_instances);die;
				//var_dump($data_set);die;

				//make array unique if we got duplicates and set to $data array
				$data['categories'] = array_values(array_unique($data_categories));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array('OPD', 'MCH', 'U5 Clinic', 'Ward', 'Other');
				//expected to be 5 in this particular context, again we know they r just these 5 :)
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 5 analytic variables: 'OPD', 'MCH', 'U5 Clinic', 'Ward', 'Other'

				$this -> final_data_set['location'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				$data = $data_set = $data_series = $analytic_var = $data_categories = array();
				//unset the arrays for reuse

				//return $this -> final_data_set;
			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end mnh equipment location of availability----------------------------------------------*/

		/*--------------------begin mnh equipment availability by functionality----------------------------------------------*/
		$query = "SELECT ea.eq_code as equipment,SUM(ea.ae_fully_functional) AS total_functional,SUM(ea.ae_non_functional) AS total_non_functional FROM available_equipments ea
WHERE ea.fac_mfl IN (SELECT fac_mfl FROM facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')".$criteria_condition.")
AND ea.eq_code IN (SELECT eq_code FROM equipments WHERE eq_for='mnh')
AND ea.ae_fully_functional !=-1 AND ea.ae_non_functional !=-1
GROUP BY ea.eq_code
ORDER BY ea.eq_code ASC";
		//echo $query; die;
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getCHEquipmentName($value_['equipment']);
					//includes duplicates--so we'll array_unique outside the foreach()

					//data set by each equipment
					$data_set[$this -> getCHEquipmentName($value_['equipment'])][] = array('Fully-functional' => intval($value_['total_functional']), 'Non-functional' => intval($value_['total_non_functional']));

				}

				//var_dump($analytic_var);die;

				//make cat array unique if we got duplicates and set to $data array
				$data['categories'] = array_values(array_unique($data_categories));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array('Fully-functional', 'Non-functional');
				//expected to be 2 in this particular context

				//assign data set to $data
				$data['responses'] = $data_set;

				//assign $data to $final_data_set
				$this -> final_data_set['functionality'] = $data;
				$data = $data_set = $data_series = $analytic_var = $data_categories = array();
				//unset the arrays for reuse

				/*--------------------end mnh equipment availability by quantity----------------------------------------------*/

				// /var_dump($this -> final_data_set['quantities']);die;

				return $this -> final_data_set;
			} else {
				return null;
			}
			//die(var_dump($this->final_data_set));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

	}

	public function getSupplies($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */
	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				$value = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		/*--------------------begin supplies availability by frequency----------------------------------------------*/

		$query = "SELECT 
    count(sq.as_availability) AS total_response,
    sq.supply_code as supplies,
    sq.as_availability AS frequency
FROM
    available_supplies sq,
    supplies s
WHERE
    sq.supply_code = s.supply_code
        AND sq.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')" . $criteria_condition . ")
        AND sq.supply_code IN (SELECT 
            supply_code
        FROM
            supplies
        WHERE
            supply_for = '".$survey."')
GROUP BY sq.supply_code , sq.as_availability
ORDER BY sq.supply_code;";
		try {

			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();

			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				$data_set['Available'] = $data_set['Sometimes Available'] = $data_set['Never Available'] = array();
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getSupplyName($value_['supplies'], $survey);
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					if ($value_['frequency'] == 'Some Available') {//a hardcore fix...for Nairobi County data only--> (there was a typo in the naming 'Sometimes Available', so Nairobi data has it as 'Some Available')

						$frequency = 'Sometimes Available';
					} else {
						$frequency = $value_['frequency'];
					}
					$analytic_var[] = $frequency;
					//includes duplicates--so we'll array_unique outside the foreach()

					//collect the data_sets for the 3 analytic variables under availability
					if ($frequency == 'Available') {
						$data_set['Available'][] = intval($value_['total_response']);
					} else if ($frequency == 'Sometimes Available') {
						$data_set['Sometimes Available'][] = intval($value_['total_response']);
					} else if ($frequency == 'Never Available') {
						$data_set['Never Available'][] = intval($value_['total_response']);
					}

				}

				//var_dump($data_set);die;

				//make cat array unique if we got duplicates then json_encode and set to $data array
				$data['categories'] = (array_values(array_unique($data_categories)));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array_unique($analytic_var);
				//expected to be 3 in this particular context
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 3 analytic variables: Available | Sometimes Available | Never Available

				$this -> final_data_set['frequency'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				//unset the arrays for reuse in the next query
				$data = $data_set = $data_series = $analytic_var = $data_categories = array();

				//return $this -> final_data_set;

			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end supplies equipment availability by frequency----------------------------------------------*/

		/*--------------------begin supplies equipment location of availability----------------------------------------------*/
		$query = "SELECT 
    count(sq.as_location) AS total_response,
    sq.supply_code as supplies,
    sq.as_location AS location
FROM
    available_supplies sq
WHERE
    sq.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
        AND sq.supply_code IN (SELECT 
            supply_code
        FROM
            supplies WHERE supply_for='mnh')
        AND sq.as_location NOT LIKE '%Not Applicable%'
GROUP BY sq.supply_code , sq.as_location
ORDER BY sq.supply_code ASC";

		try {
			//echo $query;die;
			//die(print $status.$value);
			$this -> dataSet = $this -> db -> query($query, array($value));
			//var_dump($this->dataSet);die;
			$this -> dataSet = $this -> dataSet -> result_array();
			//echo '<pre>';	print_r($this -> dataSet);echo '</pre>';die;
			//echo($this -> db -> last_query());
			//die ;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);
				$count_instances = array('MCH' => 0, 'OPD' => 0, 'U5 Clinic' => 0, 'Ward' => 0, 'Other' => 0, 'Delivery room' => 0, 'Pharmacy' => 0, 'Store' => 0);
				//to hold the location instances
				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getSupplyName($value_['supplies'], $survey);
					//incase of duplicates--do an array_unique outside the foreach()
					switch($survey) {
						case 'ch' :
							//collect the data_sets from the coma separated responses
							if (strpos($value_['location'], 'OPD') !== FALSE) {
								$count_instances['OPD'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['OPD'] = $count_instances['OPD'];
							}
							if (strpos($value_['location'], 'MCH') !== FALSE) {
								$count_instances['MCH'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['MCH'] = $count_instances['MCH'];
							}
							if (strpos($value_['location'], 'U5 Clinic') !== FALSE) {
								$count_instances['U5 Clinic'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['U5 Clinic'] = $count_instances['U5 Clinic'];
							}
							if (strpos($value_['location'], 'Ward') !== FALSE) {
								$count_instances['Ward'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['Ward'] = $count_instances['Ward'];
							}
							if (strpos($value_['location'], 'Other') !== FALSE) {
								$count_instances['Other'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['Other'] = $count_instances['Other'];
							}

							break;
						case 'mnh' :
							//collect the data_sets from the coma separated responses
							if (strpos($value_['location'], 'Delivery room') !== FALSE) {
								$count_instances['Delivery room'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['Delivery room'] = $count_instances['Delivery room'];
							}
							if (strpos($value_['location'], 'Pharmacy') !== FALSE) {
								$count_instances['Pharmacy'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['Pharmacy'] = $count_instances['Pharmacy'];
							}
							if (strpos($value_['location'], 'Store') !== FALSE) {
								$count_instances['Store'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['Store'] = $count_instances['Store'];
							}
							if (strpos($value_['location'], 'Other') !== FALSE) {
								$count_instances['Other'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['Other'] = $count_instances['Other'];
							}
							break;
					}
					//2. collect the analytic variables
					//$analytic_var[] = $value['location'];-->hard fix outside the loop as values are coma separated...good fix..have v-look up in the db

				}
				//var_dump($count_instances);die;
				//var_dump($data_set);die;

				//make array unique if we got duplicates and set to $data array
				$data['categories'] = array_values(array_unique($data_categories));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array('OPD', 'MCH', 'U5 Clinic', 'Ward', 'Other');
				//expected to be 5 in this particular context, again we know they r just these 5 :)
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 5 analytic variables: 'OPD', 'MCH', 'U5 Clinic', 'Ward', 'Other'

				$this -> final_data_set['location'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				$data = $data_set = $data_series = $analytic_var = $data_categories = array();
				//unset the arrays for reuse

				return $this -> final_data_set;
			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end supplies equipment location of availability----------------------------------------------*/

	}
	public function getRunningWater($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */
	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				$value = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		/*--------------------begin supplies availability by frequency----------------------------------------------*/

		$query = "SELECT 
    count(sq.as_availability) AS total_response,
    sq.supply_code as supplies,
    sq.as_availability AS frequency
FROM
    available_supplies sq,
    supplies s
WHERE
    sq.supply_code = s.supply_code
        AND sq.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')" . $criteria_condition . ")
        AND sq.supply_code IN (SELECT 
            supply_code
        FROM
            supplies
        WHERE
            supply_for = 'mh')
GROUP BY sq.supply_code , sq.as_availability
ORDER BY sq.supply_code;";
		try {

			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();

			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				$data_set['Available'] = $data_set['Sometimes Available'] = $data_set['Never Available'] = array();
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getSupplyName($value_['supplies'], 'mh');
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					if ($value_['frequency'] == 'Some Available') {//a hardcore fix...for Nairobi County data only--> (there was a typo in the naming 'Sometimes Available', so Nairobi data has it as 'Some Available')

						$frequency = 'Sometimes Available';
					} else {
						$frequency = $value_['frequency'];
					}
					$analytic_var[] = $frequency;
					//includes duplicates--so we'll array_unique outside the foreach()

					//collect the data_sets for the 3 analytic variables under availability
					if ($frequency == 'Available') {
						$data_set['Available'][] = intval($value_['total_response']);
					} else if ($frequency == 'Sometimes Available') {
						$data_set['Sometimes Available'][] = intval($value_['total_response']);
					} else if ($frequency == 'Never Available') {
						$data_set['Never Available'][] = intval($value_['total_response']);
					}

				}

				//var_dump($data_set);die;

				//make cat array unique if we got duplicates then json_encode and set to $data array
				$data['categories'] = (array_values(array_unique($data_categories)));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array_unique($analytic_var);
				//expected to be 3 in this particular context
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 3 analytic variables: Available | Sometimes Available | Never Available

				$this -> final_data_set['frequency'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				//unset the arrays for reuse in the next query
				$data = $data_set = $data_series = $analytic_var = $data_categories = array();

				//return $this -> final_data_set;

			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end supplies equipment availability by frequency----------------------------------------------*/

		/*--------------------begin supplies equipment location of availability----------------------------------------------*/
		$query = "SELECT 
    count(sq.as_location) AS total_response,
    sq.supply_code as supplies,
    sq.as_location AS location
FROM
    available_supplies sq
WHERE
    sq.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
        AND sq.supply_code IN (SELECT 
            supply_code
        FROM
            supplies WHERE supply_for='mh')
        AND sq.as_location NOT LIKE '%Not Applicable%'
GROUP BY sq.supply_code , sq.as_location
ORDER BY sq.supply_code ASC";

		try {
			//echo $query;die;
			//die(print $status.$value);
			$this -> dataSet = $this -> db -> query($query, array($value));
			//var_dump($this->dataSet);die;
			$this -> dataSet = $this -> dataSet -> result_array();
			//echo '<pre>';	print_r($this -> dataSet);echo '</pre>';die;
			//echo($this -> db -> last_query());
			//die ;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);
				$count_instances = array('MCH' => 0, 'OPD' => 0, 'U5 Clinic' => 0, 'Other' => 0,'Maternity' => 0);
				//to hold the location instances
				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getSupplyName($value_['supplies'], 'mh');
					//var_dump($data_categories);die;
					//incase of duplicates--do an array_unique outside the foreach()
					
							//collect the data_sets from the coma separated responses
							if (strpos($value_['location'], 'OPD') !== FALSE) {
								$count_instances['OPD'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['OPD'] = $count_instances['OPD'];
							}
							if (strpos($value_['location'], 'Maternity') !== FALSE) {
								$count_instances['Maternity'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['Maternity'] = $count_instances['Maternity'];
							}
							if (strpos($value_['location'], 'MCH') !== FALSE) {
								$count_instances['MCH'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['MCH'] = $count_instances['MCH'];
							}
							if (strpos($value_['location'], 'U5 Clinic') !== FALSE) {
								$count_instances['U5 Clinic'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['U5 Clinic'] = $count_instances['U5 Clinic'];
							}
							if (strpos($value_['location'], 'Other') !== FALSE) {
								$count_instances['Other'] += intval($value_['total_response']);
								$data_set[$this -> getSupplyName($value_['supplies'], $survey)]['Other'] = $count_instances['Other'];
							}

					//2. collect the analytic variables
					//$analytic_var[] = $value['location'];-->hard fix outside the loop as values are coma separated...good fix..have v-look up in the db

				}
				//var_dump($count_instances);die;
				//var_dump($data_set);die;

				//make array unique if we got duplicates and set to $data array
				$data['categories'] = array_values(array_unique($data_categories));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array('OPD', 'Maternity','MCH', 'U5 Clinic', 'Other');
				//expected to be 5 in this particular context, again we know they r just these 5 :)
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 5 analytic variables: 'OPD', 'MCH', 'U5 Clinic', 'Ward', 'Other'

				$this -> final_data_set['location'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				$data = $data_set = $data_series = $analytic_var = $data_categories = array();
				//unset the arrays for reuse

				return $this -> final_data_set;
			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end supplies equipment location of availability----------------------------------------------*/

	}

	/*
	 * Availability, Location and Functionality of Supplies at ORT Corner
	 */
	public function getCHSuppliesSupplier($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */

	switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		/*--------------------begin equipment main supplier----------------------------------------------*/
		$query = "SELECT 
    count(sq.supply_code)/2 AS total_response,
    sq.supply_code as supplies,
    sq.supplier_code AS supplier
FROM
    available_supplies sq,
    supplies c
WHERE
    sq.supply_code = c.supply_code
        AND sq.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
        AND sq.supply_code IN (SELECT 
            supply_code
        FROM
            supplies
        WHERE
            supply_for = '$survey')
GROUP BY sq.supply_code , sq.supply_code
ORDER BY sq.supply_code
LIMIT 0 , 1000
";
		try {

			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $value_['supplier'];
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					$analytic_var[] = $this -> getSupplyName($value_['supplies'], $survey);
					//includes duplicates--so we'll array_unique outside the foreach()

					//data set by each analytic variable
					$data_set[$value_['supplier']][] = intval($value_['total_response']);

				}

				//var_dump($data_set);die;

				//make cat array unique if we got duplicates then json_encode and set to $data array
				$data['categories'] = (array_values(array_unique($data_categories)));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array_unique($analytic_var);
				//expected to be 3 in this particular context
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 3 analytic variables: Available | Sometimes Available | Never Available

				$this -> dataSet = $data;

				return $this -> dataSet;

			} else {
				return $this -> dataSet = null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
	}

	/*
	 *  Availability, Location and Functionality of Electricity and Hardware Resources
	 */
	public function getResources($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$data = $data_set = $data_series = $analytic_var = $data_categories = array();
		//data to hold the final data to relayed to the view,data_set to hold sets of data, analytic_var to hold the analytic variables to be used in the data_series,data_series to hold the title and the json encoded sets of the data_set

		/**
		 * something of this kind:
		 * $data_series[0]="name: '.$value['analytic_variable'].',data:".json_encode($data_set[0])
		 */

	switch($survey){
		case 'mnh':
		$eq='mhw';
		break;
		case 'ch':
		$eq='hwr';
		break;
	}

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				$value = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		/*--------------------begin ort equipment availability by frequency----------------------------------------------*/
		$query = "SELECT 
    count(ra.ar_availability) AS total_response,
    ra.eq_code as equipment,
    ra.ar_availability AS frequency
FROM
    available_resources ra
WHERE
    ra.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
        AND ra.eq_code IN (SELECT 
            eq_code
        FROM
            equipments
        WHERE
            eq_for = '".$eq."')
GROUP BY ra.eq_code , ra.ar_availability
ORDER BY ra.eq_code ASC";
		try {

			$this -> dataSet = $this -> db -> query($query, array($value));

			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				$data_set['Available'] = $data_set['Sometimes Available'] = $data_set['Never Available'] = array();
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getCHResourcesName($value_['equipment']);
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					if ($value_['frequency'] == 'Some Available') {//a hardcore fix...for Nairobi County data only--> (there was a typo in the naming 'Sometimes Available', so Nairobi data has it as 'Some Available')

						$frequency = 'Sometimes Available';
					} else {
						$frequency = $value_['frequency'];
					}
					$analytic_var[] = $frequency;
					//includes duplicates--so we'll array_unique outside the foreach()

					//collect the data_sets for the 3 analytic variables under availability
					if ($frequency == 'Available') {
						$data_set['Available'][] = intval($value_['total_response']);
					} else if ($frequency == 'Sometimes Available') {
						$data_set['Sometimes Available'][] = intval($value_['total_response']);
					} else if ($frequency == 'Never Available') {
						$data_set['Never Available'][] = intval($value_['total_response']);
					}

				}

				//var_dump($data_set);die;

				//make cat array unique if we got duplicates then json_encode and set to $data array
				$data['categories'] = (array_values(array_unique($data_categories)));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array_unique($analytic_var);
				//expected to be 3 in this particular context
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 3 analytic variables: Available | Sometimes Available | Never Available

				$this -> final_data_set['frequency'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				//unset the arrays for reuse in the next query
				$data = $data_set = $data_series = $analytic_var = $data_categories = array();

				//return $this -> final_data_set;

			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end ort equipment availability by frequency----------------------------------------------*/

		/*--------------------begin ort equipment location of availability----------------------------------------------*/
		$query = "SELECT 
    count(ra.ar_location) AS total_response,
    ra.eq_code as equipment,
    ra.ar_location AS location
FROM
    available_resources ra
WHERE
    ra.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
        AND ra.eq_code IN (SELECT 
            eq_code
        FROM
            equipments
        WHERE
            eq_for = '".$eq."')
        AND ra.ar_location NOT LIKE '%Not Applicable%'
GROUP BY ra.eq_code , ra.ar_location
ORDER BY ra.eq_code ASC";

		try {
			//echo $query;die;
			//die(print $status.$value);
			$this -> dataSet = $this -> db -> query($query, array($value));
			//var_dump($this->dataSet);die;
			$this -> dataSet = $this -> dataSet -> result_array();
			//echo($this->db->last_query());die;
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);
				$count_instances = array('MCH' => 0, 'OPD' => 0, 'U5 Clinic' => 0, 'Ward' => 0, 'Other' => 0);
				//to hold the location instances
				foreach ($this->dataSet as $value_) {

					//1. collect the categories
					$data_categories[] = $this -> getCHResourcesName($value_['equipment']);
					//incase of duplicates--do an array_unique outside the foreach()

					//2. collect the analytic variables
					//$analytic_var[] = $value['location'];-->hard fix outside the loop as values are coma separated...good fix..have v-look up in the db

					//collect the data_sets from the coma separated responses
					if (strpos($value_['location'], 'OPD') !== FALSE) {
						$count_instances['OPD'] += intval($value_['total_response']);
						$data_set[$this -> getCHResourcesName($value_['equipment'])]['OPD'] = $count_instances['OPD'];
					}
					if (strpos($value_['location'], 'MCH') !== FALSE) {
						$count_instances['MCH'] += intval($value_['total_response']);
						$data_set[$this -> getCHResourcesName($value_['equipment'])]['MCH'] = $count_instances['MCH'];
					}
					if (strpos($value_['location'], 'U5 Clinic') !== FALSE) {
						$count_instances['U5 Clinic'] += intval($value_['total_response']);
						$data_set[$this -> getCHResourcesName($value_['equipment'])]['U5 Clinic'] = $count_instances['U5 Clinic'];
					}
					if (strpos($value_['location'], 'Ward') !== FALSE) {
						$count_instances['Ward'] += intval($value_['total_response']);
						$data_set[$this -> getCHResourcesName($value_['equipment'])]['Ward'] = $count_instances['Ward'];
					}
					if (strpos($value_['location'], 'Other') !== FALSE) {
						$count_instances['Other'] += intval($value_['total_response']);
						$data_set[$this -> getCHResourcesName($value_['equipment'])]['Other'] = $count_instances['Other'];
					}

				}
				//var_dump($count_instances);die;
				//var_dump($data_set);die;

				//make array unique if we got duplicates and set to $data array
				$data['categories'] = array_values(array_unique($data_categories));
				//expected 28

				//get a unique set of analytic variables
				$analytic_var = array('OPD', 'MCH', 'U5 Clinic', 'Ward', 'Other');
				//expected to be 5 in this particular context, again we know they r just these 5 :)
				$data['analytic_variables'] = $analytic_var;

				//get the data sets
				$data['responses'] = $data_set;
				//sets of the 5 analytic variables: 'OPD', 'MCH', 'U5 Clinic', 'Ward', 'Other'

				$this -> final_data_set['location'] = $data;
				#note, I've introduced $final_data_set to be used in place of $data since $data is reset and reused

				$data = $data_set = $data_series = $analytic_var = $data_categories = array();
				//unset the arrays for reuse

				return $this -> final_data_set;
			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		/*--------------------end ort equipment location of availability----------------------------------------------*/

	}

	public function get_response_count($survey) {
		try {
			/*using CI Database Active Record*/
			try {
				$query = "SELECT DISTINCT(facilityCode),trackerID,lastActivity FROM assessment_tracker WHERE survey=? AND trackerSection='section-6'
ORDER BY lastActivity DESC";
				$this -> dataSet = $this -> db -> query($query, array($survey));
				$this -> dataSet = $this -> dataSet -> result_array();
				//die(var_dump($this->dataSet));
			} catch(exception $ex) {
				//ignore
				//die($ex->getMessage());//exit;
			}
			return $this -> dataSet;

		} catch(exception $ex) {
			//ignore
			//die($ex -> getMessage());
		}

		return $this -> dataSet;
	}

	function getSpecificDistrictNames($county) {
		/*using DQL*/
		try {
			$query = $this -> em -> createQuery('SELECT DISTINCT(f.facDistrict) FROM  models\Entities\Facilities f WHERE f.facCounty = :county ORDER BY f.facDistrict ASC');
			$query -> setParameter('county', $county);
			$this -> districtName = $query -> getResult();
			//die(var_dump($this->districtName));
		} catch(exception $ex) {
			//ignore
			//$ex->getMessage();
		}
		return $this -> districtName;
	}/*end of getSpecificDistrictNames*/

	function getCountyFacilities() {
		/*using DQL*/
		try {
			$query = "SELECT COUNT(facilities.fac_name),fac_county FROM facilities GROUP BY facilities.fac_county ORDER BY COUNT(facilities.fac_name) DESC;";
			$this -> countyFacilities = $this -> db -> query($query);
			$this -> countyFacilities = $this -> countyFacilities -> result_array();
			//die(var_dump($this->districtName));
		} catch(exception $ex) {
			//ignore
			//$ex->getMessage();
		}
		return $this -> countyFacilities;
	}/*end of getSpecificDistrictNames*/

	function getCountyFacilitiesByOwner($county) {
		/*using DQL*/
		try {
			$query = "SELECT COUNT(facilityOwnedBy),facilityOwnedBy FROM facilities WHERE fac_county='Nairobi' GROUP BY facilityOwnedBy ORDER BY COUNT(facilityOwnedBy) DESC;";
			$this -> countyFacilities = $this -> db -> query($query);
			$this -> countyFacilities = $this -> countyFacilities -> result_array();
			//die(var_dump($this->districtName));
		} catch(exception $ex) {
			//ignore
			//$ex->getMessage();
		}
		return $this -> countyFacilities;
	}/*end of getSpecificDistrictNames*/

	public function getFacilitiesByDistrictOptions($district, $survey) {
		switch($survey) {
			case 'ch' :
				$search = "facilityCHSurveyStatus='complete'";
				break;

			case 'mnh' :
				$search = "ss_id='complete'";
				break;
		}
		$myOptions = '<option>Viewing All</option>';
		/*using CI Database Active Record*/
		try {
			$query = "SELECT DISTINCT
    f.fac_mfl, f.fac_name
FROM
    facilities f
        JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = '".$survey."')
WHERE
    fac_district = '".$district."'
ORDER BY fac_name;";
			$this -> dataSet = $this -> db -> query($query);
			$this -> dataSet = $this -> dataSet -> result_array();
			//die(var_dump($this->dataSet));
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {
					$myOptions .= '<option value=' . $value_['fac_mfl'] . '>' . $value_['fac_name'] . '</option>';
					//1. collect the categories
					//$data_categories[] = $this -> getCHEquipmentName($value_['equipment']);
					//incase of duplicates--do an array_unique outside the foreach()

				}

				//unset the arrays for reuse

				//return $this -> final_data_set;
			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		return $myOptions;

	}

	public function getFacilitiesByDistrictOptionsNew($district, $table) {
		$myOptions = '<option>Viewing All</option>';
		/*using CI Database Active Record*/
		try {
			$query = "SELECT DISTINCT
facilities.fac_mfl, facilities.fac_name
FROM
facility,
mch_indicator_log
WHERE
fac_district = '" . $district . "'
AND facilities.fac_mfl = " . $table . ".fac_mfl
ORDER BY fac_name;";
			$this -> dataSet = $this -> db -> query($query);
			$this -> dataSet = $this -> dataSet -> result_array();
			//die(var_dump($this->dataSet));
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {
					$myOptions .= '<option value=' . $value_['fac_mfl'] . '>' . $value_['fac_name'] . '</option>';
					//1. collect the categories
					//$data_categories[] = $this -> getCHEquipmentName($value_['equipment']);
					//incase of duplicates--do an array_unique outside the foreach()

				}

				//unset the arrays for reuse

				//return $this -> final_data_set;
			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		return $myOptions;

	}

	public function getReportingCountiesCore() {
		/*using CI Database Active Record*/
		try {
			$query = "SELECT 
    f.fac_county as county,c.countyID as countyID
FROM
    mnh_latest.assessment_tracker t,
    facilities f,county c
WHERE
    t.facilityCode = f.fac_mfl
        and t.trackerSection = 'section-6'
AND
c.countyName =  f.fac_county
GROUP BY f.fac_county
ORDER BY f.fac_county ASC;";
			$this -> dataSet = $this -> db -> query($query);
			$this -> dataSet = $this -> dataSet -> result_array();
			//die(var_dump($this->dataSet));

		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		//var_dump($myOptions);
		//var_dump($this -> dataSet);
		return $this -> dataSet;

	}

	/**
	 * List of Counties that have reported
	 */
	public function getReportingCounties($survey) {
		
		/*using CI Database Active Record*/
		try {
			$query = "SELECT 
    f.fac_county as county, c.county_id as countyID
FROM
    facilities f
        JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = '".$survey."') JOIN assessment_tracker ast ON ast.facilityCode = f.fac_mfl
        AND ast.ast_section = 'section-6' AND ast.ast_survey='".$survey."',
    counties c
WHERE
    c.county_name = f.fac_county
GROUP BY f.fac_county
ORDER BY f.fac_county ASC;";
			$this -> dataSet = $this -> db -> query($query);
			$this -> dataSet = $this -> dataSet -> result_array();
			//die(var_dump($this->dataSet));

		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		//var_dump($myOptions);
		//var_dump($this -> dataSet);
		return $this -> dataSet;

	}

	public function generateFacilityList() {
		$result;
		try {
			$query = "";
			$this -> dataSet = $this -> db -> query($query);
			$this -> dataSet = $this -> dataSet -> result_array();
			//die(var_dump($this->dataSet));
			if ($this -> dataSet !== NULL) {
				//prep data for the pie chart format
				$size = count($this -> dataSet);

				foreach ($this->dataSet as $value_) {

				}

			} else {
				return null;
			}
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		return $result;
	}

	function getAllReportingRatio($survey) {
		$reportingCounties = $this -> getReportingCounties($survey);
		for ($x = 0; $x < sizeof($reportingCounties); $x++) {
			$allData[$reportingCounties[$x]['county']] = $this -> getReportingRatio($reportingCounties[$x]['county'], $survey);

		}
		//var_dump($allData);
		return $allData;

	}

	function getReportingRatio($county,$survey) {
		/*using DQL*/

		$finalData = array();
		
		try {

			$query = 'SELECT 
    tracker.reported,
    facilityData.actual,
    round((tracker.reported / facilityData.actual) * 100,0) as percentage
FROM
(SELECT 
    COUNT(*) as reported
FROM
    facilities f
        JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = "'.$survey.'")
 JOIN assessment_tracker ast ON ast.facilityCode = f.fac_mfl
        AND ast.ast_section = "section-6" AND ast.ast_survey="'.$survey.'"
WHERE
    f.fac_county = "'.$county.'") as tracker,
(SELECT 
        COUNT(fac_mfl) as actual
    FROM
        facilities
    WHERE
        facilities.fac_county = "'.$county.'"
		AND fac_type != "Dental Clinic"
            AND fac_type != "VCT Centre (Stand-Alone)"
            AND fac_type != "Training Institution in Health (Stand-alone)"
            AND fac_type != "Funeral Home (Stand-alone)"
            AND fac_type != "Laboratory (Stand-alone)"
            AND fac_type != "Health Project"
            AND fac_type != "Eye Clinic"
			AND fac_type != "Eye Centre"
            AND fac_type != "Radiology Unit") as facilityData;';
			//echo $query;die;
			$myData = $this -> db -> query($query);
			$finalData = $myData -> result_array();

		} catch(exception $ex) {
			//ignore
			//echo($ex -> getMessage());
		}
		return $finalData;
	}

	function getFacilityOwnerPerCounty($county, $survey) {
		/*using DQL*/

		$finalData = array();
		switch($survey) {
			case 'ch' :
				$search = '';
				break;

			case 'mnh' :
				$search = '';
				break;
		}
		try {

			$query = 'SELECT 
    tracker.ownership_total, tracker.facilityOwner
FROM(SELECT 
    COUNT(fac_ownership) as ownership_total,
    (CASE
                WHEN fac_ownership = "Private Practice - General Practitioner" THEN "Private"
                WHEN fac_ownership = "Private Practice - Nurse / Midwife" THEN "Private"
                WHEN fac_ownership = "Private Enterprise (Institution)" THEN "Private"
                WHEN fac_ownership = "Private Practice - Clinical Officer" THEN "Private"
                WHEN fac_ownership = "Christian Health Association of Kenya" THEN "Faith Based Organisation"
                WHEN fac_ownership = "Other Faith Based" THEN "Faith Based Organisation"
                WHEN fac_ownership = "FBO" THEN "Faith Based Organisation"
                WHEN fac_ownership = "Kenya Episcopal Conference-Catholic Secretariat" THEN "Faith Based Organisation"
                WHEN fac_ownership = "GOK" THEN "Ministry of Health"
                ELSE fac_ownership
            END) as facilityOwner,
    fac_county as countyName
FROM
    facilities f
        JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = "'.$survey.'")
WHERE
    f.fac_county = "'.$county.'"
GROUP BY facilityOwner
ORDER BY COUNT(facilityOwner) ASC) as tracker;';

			$myData = $this -> db -> query($query);
			$finalData = $myData -> result_array();

		} catch(exception $ex) {
			//ignore
			//echo($ex -> getMessage());
		}
		return $finalData;
	}

	function getFacilityLevelPerCounty($county, $survey) {
		/*using DQL*/
		try {

			$query = 'SELECT 
    tracker.level_total, tracker.facilityLevel
FROM
    (SELECT 
        COUNT(fac_level) as level_total,
            fac_level as facilityLevel,
            fac_county as countyName
    FROM
        facilities f
    JOIN survey_status ss ON ss.fac_id = f.fac_mfl
    JOIN survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = "'.$survey.'")
    WHERE
        f.fac_county = "'.$county.'" and f.fac_level!=""
    GROUP BY fac_Level
    ORDER BY COUNT(fac_Level) ASC) AS tracker;';

			$myData = $this -> db -> query($query);
			$finalData = $myData -> result_array();

		} catch(exception $ex) {
			//ignore
			//echo($ex -> getMessage());
		}
		return $finalData;
	}

	/**
	 * Run County Maps
	 */
	public function runMap($survey) {
		$myData = array();
		$counties = $this -> getAllCountyNames();
		foreach ($counties as $county) {
			$countyName = $county['countyName'];
			//$countyName=str_replace("'","", $countyName);
			$myData[$countyName] = array($this -> getReportingRatio($countyName, $survey), $county['countyFusionMapId'], $countyName);
		}

		return $myData;
	}

	/**
	 * Lists for NO
	 */
	public function getFacilityListForNo($criteria, $value,  $survey, $choice) {
		urldecode($value);
	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				$value = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		switch($choice) {
			case 'GuidelinesAvailability' :
				#Facility List
				$query = "SELECT DISTINCT lq.fac_mfl, g.question_name, f.fac_name
					FROM log_questions lq,questions g, facilities f WHERE response = 'No'AND lq.question_code IN (SELECT question_code FROM questions
 					WHERE  question_for = 'gp') AND lq.fac_mfl IN (SELECT fac_mfl FROM facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
               ".$criteria_condition.")
 					 AND lq.question_code = g.question_code AND lq.fac_mfl=f.fac_mfl;";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();
					if ($this -> dataSet !== NULL) {

						$size = count($this -> dataSet);
						$i = 0;
						$facilities = array();
						foreach ($this->dataSet as $value) {
							$facilities[$value['question_name']][] = array($value['fac_mfl'], $value['fac_name']);

						}
						return $facilities;

					} else {
						return $this -> dataSet = null;
					}
				} catch(exception $ex) {
				}
				break;

			case 'ServicesOffered' :
				$query = "SELECT 
    i.indicatorName, il.fac_mfl, f.fac_name
FROM
    mch_indicator_log il,
    mch_indicators i,
    facilities f
WHERE
    il.response = 'No'
        AND il.indicatorID = i.indicatorCode
        AND il.fac_mfl = f.fac_mfl
        AND il.indicatorID IN (SELECT 
            indicatorCode
        FROM
            mch_indicators
        WHERE
            indicatorFor = 'svc')
        AND il.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
               ".$criteria_condition.")";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();

					if ($this -> dataSet !== NULL) {

						$size = count($this -> dataSet);
						$i = 0;
						$facilities = array();
						foreach ($this->dataSet as $value) {
							$facilities[$value['indicatorName']][] = array($value['fac_mfl'], $value['fac_name']);
						}
						return $facilities;

						//var_dump($this->dataSet);die;

					} else {
						return $this -> dataSet = null;
					}
				} catch(exception $ex) {
				}
				break;
			case 'DangerSigns' :
				$query = "SELECT 
    i.indicatorName, il.fac_mfl, f.fac_name
FROM
    mch_indicator_log il,
    mch_indicators i,
    facilities f
WHERE
    il.response = 'No'
        AND il.indicatorID = i.indicatorCode
        AND il.fac_mfl = f.fac_mfl
        AND il.indicatorID IN (SELECT 
            indicatorCode
        FROM
            mch_indicators
        WHERE
            indicatorFor = 'sgn')
        AND il.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
               ".$criteria_condition.")";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();

					if ($this -> dataSet !== NULL) {

						$size = count($this -> dataSet);
						$i = 0;
						$facilities = array();
						foreach ($this->dataSet as $value) {
							$facilities[$value['indicatorName']][] = array($value['fac_mfl'], $value['fac_name']);
						}
						return $facilities;

						//var_dump($this->dataSet);die;

					} else {
						return $this -> dataSet = null;
					}
				} catch(exception $ex) {
				}
				break;
			case 'ActionsPerformed' :
				$query = "SELECT 
    i.indicatorName, il.fac_mfl, f.fac_name
FROM
    mch_indicator_log il,
    mch_indicators i,
    facilities f
WHERE
    il.response = 'No'
        AND il.indicatorID = i.indicatorCode
        AND il.fac_mfl = f.fac_mfl
        AND il.indicatorID IN (SELECT 
            indicatorCode
        FROM
            mch_indicators
        WHERE
            indicatorFor = 'dgn')
        AND il.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
               ".$criteria_condition.")";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();

					if ($this -> dataSet !== NULL) {

						$size = count($this -> dataSet);
						$i = 0;
						$facilities = array();
						foreach ($this->dataSet as $value) {
							$facilities[$value['indicatorName']][] = array($value['fac_mfl'], $value['fac_name']);
						}
						return $facilities;

						//var_dump($this->dataSet);die;

					} else {
						return $this -> dataSet = null;
					}
				} catch(exception $ex) {
				}
				break;
			case 'Counsel Given' :
				$query = "SELECT 
    i.indicatorName, il.fac_mfl, f.fac_name
FROM
    mch_indicator_log il,
    mch_indicators i,
    facilities f
WHERE
    il.response = 'No'
        AND il.indicatorID = i.indicatorCode
        AND il.fac_mfl = f.fac_mfl
        AND il.indicatorID IN (SELECT 
            indicatorCode
        FROM
            mch_indicators
        WHERE
            indicatorFor = 'cns')
        AND il.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
               ".$criteria_condition.")";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();

					if ($this -> dataSet !== NULL) {

						$size = count($this -> dataSet);
						$i = 0;
						$facilities = array();
						foreach ($this->dataSet as $value) {
							$facilities[$value['indicatorName']][] = array($value['fac_mfl'], $value['fac_name']);
						}
						return $facilities;

						//var_dump($this->dataSet);die;

					} else {
						return $this -> dataSet = null;
					}
				} catch(exception $ex) {
				}
				break;
			case 'Tools' :
				$query = "SELECT 
    i.indicatorName, il.fac_mfl, f.fac_name
FROM
    mch_indicator_log il,
    mch_indicators i,
    facilities f
WHERE
    il.response = 'No'
        AND il.indicatorID = i.indicatorCode
        AND il.fac_mfl = f.fac_mfl
        AND il.indicatorID IN (SELECT 
            indicatorCode
        FROM
            mch_indicators
        WHERE
            indicatorFor = 'ror')
        AND il.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
               ".$criteria_condition.")";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();

					if ($this -> dataSet !== NULL) {

						$size = count($this -> dataSet);
						$i = 0;
						$facilities = array();
						foreach ($this->dataSet as $value) {
							$facilities[$value['indicatorName']][] = array($value['fac_mfl'], $value['fac_name']);
						}
						return $facilities;

						//var_dump($this->dataSet);die;

					} else {
						return $this -> dataSet = null;
					}
				} catch(exception $ex) {
				}
				break;
			/**
			 * MNH Questions
			 */
		}
	}

	/**
	 * Lists for NEVER
	 */
	public function getFacilityListForNever($criteria, $value,  $survey, $choice) {
		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				$value = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		switch($choice) {
			case 'Commodity' :
				$query = "SELECT 
    ca.fac_mfl,
    f.fac_name,
    ca.Availability AS frequency,
    ca.comm_code as commodities,
    c.comm_unit as unit
FROM
    available_equipments ca,
    commodities c,
    facilities f
WHERE
    ca.Availability = 'Never Available'
        AND ca.comm_code = c.comm_code
        AND ca.fac_mfl = f.fac_mfl
        AND ca.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
               ".$criteria_condition.")
        AND ca.comm_code IN (SELECT 
            comm_code
        FROM
            commodities
        WHERE
            comm_for = 'mch')
ORDER BY ca.comm_code";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();

					if ($this -> dataSet !== NULL) {

						$size = count($this -> dataSet);
						$i = 0;
						$facilities = array();
						foreach ($this->dataSet as $value) {
							//$title[$this->getCommodityNameById($value['commodities'])][]=$this->getCommodityNameById($value['commodities']).'  ['.$value['unit'].']';
							$facilities[$this -> getCommodityNameById($value['commodities']) . '  [' . $value['unit'] . ']'][] = array($value['fac_mfl'], $value['fac_name']);

						}
						return $facilities;
						//$this -> dataSet = array('breastfeed' => $breastfeed, 'lethargy' => $lethargy);

						//var_dump($this->dataSet);die;
					} else {
						return $facilities = null;
					}
				} catch(exception $ex) {
				}
				break;
			case 'ORT' :
				$query = "SELECT 
    ea.fac_mfl,
    f.fac_name,
    ea.ae_availability AS frequency,
    ea.eq_code as equipment
FROM
    available_equipments ea,
    facilities f
WHERE
    ea.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
               ".$criteria_condition.")
        AND ea.eq_code IN (SELECT 
            eq_code
        FROM
            equipments
        WHERE
            eq_for = 'ort')
        AND ea.ae_availability = 'Never Available'
        AND ea.fac_mfl = f.fac_mfl
ORDER BY ea.eq_code ASC";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();

					if ($this -> dataSet !== NULL) {
						$facilities = array();
						$size = count($this -> dataSet);
						$i = 0;

						foreach ($this->dataSet as $value) {
							$facilities[$this -> getCHEquipmentName($value['equipment'])][] = array($value['fac_mfl'], $value['fac_name']);
						}

						return $facilities;
						//var_dump($this->dataSet);die;

					} else {
						return $facilities = null;
					}
				} catch(exception $ex) {
				}
				break;
			case 'Water' :
				$query = "SELECT 
    sq.fac_mfl,
    f.fac_name,
    sq.supply_code as supplies,
    sq.as_availability AS frequency
FROM
    available_supplies sq,
    supplies s,
    facilities f
WHERE
    sq.supply_code = s.supply_code
        AND sq.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
               ".$criteria_condition.")
        AND sq.supply_code IN (SELECT 
            supply_code
        FROM
            supplies
        WHERE
            supply_for = 'mch')
        AND sq.as_availability = 'Never Available'
        AND sq.fac_mfl = f.fac_mfl
ORDER BY sq.supply_code;";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();

					if ($this -> dataSet !== NULL) {
						$facilities = array();
						$size = count($this -> dataSet);
						$i = 0;

						foreach ($this->dataSet as $value) {
							$facilities[$this -> getSupplyName($value['supplies'])][] = array($value['fac_mfl'], $value['fac_name']);
						}
						return $facilities;
						//var_dump($this->dataSet);die;

					} else {
						return $facilities = null;
					}
				} catch(exception $ex) {
				}
				break;
			case 'Running Water' :
				$query = "SELECT 
    sq.fac_mfl,
    f.fac_name,
    sq.supply_code as supplies,
    sq.as_availability AS frequency
FROM
    available_supplies sq,
    supplies s,
    facilities f
WHERE
    sq.supply_code = s.supply_code
        AND sq.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
               ".$criteria_condition.")
        AND sq.supply_code IN (SELECT 
            supply_code
        FROM
            supplies
        WHERE
            supply_for = 'mh')
        AND sq.as_availability = 'Never Available'
        AND sq.fac_mfl = f.fac_mfl
ORDER BY sq.supply_code;";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();

					if ($this -> dataSet !== NULL) {
						$facilities = array();
						$size = count($this -> dataSet);
						$i = 0;

						foreach ($this->dataSet as $value) {
							$facilities[$this -> getSupplyName($value['supplies'])][] = array($value['fac_mfl'], $value['fac_name']);
						}
						return $facilities;
						//var_dump($this->dataSet);die;

					} else {
						return $facilities = null;
					}
				} catch(exception $ex) {
				}
				break;
			case 'Resources' :
			switch($survey)
				{
					case 'mnh':
						$for = "mhw";
					break;
					case 'ch':
						$for="hwr";
					break;

					}
				$query = "SELECT 
				
    ra.fac_mfl,
    f.fac_name,
    ra.eq_code as equipment,
    ra.ar_availability AS frequency
FROM
    available_resources ra,
    facilities f
WHERE
    ra.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
               ".$criteria_condition.") 
        AND ra.eq_code IN (SELECT 
            eq_code
        FROM
            equipments
        WHERE
            eq_for = '".$for."')
        AND ra.ar_availability = 'Never Available'
        AND ra.fac_mfl = f.fac_mfl
ORDER BY ra.eq_code ASC";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();

					if ($this -> dataSet !== NULL) {

						$size = count($this -> dataSet);
						$i = 0;
						$facilities = array();
						foreach ($this->dataSet as $value) {
							$facilities[$this -> getCHEquipmentName($value['equipment'])][] = array($value['fac_mfl'], $value['fac_name']);
						}

						return $facilities;

						//var_dump($this->dataSet);die;

					} else {
						return $facilities = null;
					}
				} catch(exception $ex) {
				}
				break;
		}
	}

	public function case_summary($county, $choice) {
		$final = array();
		$query = '';
		switch($choice) {
			case 'Cases' :
				$query = "SELECT 
    SUM(CASE
        WHEN tl.lt_severe_dehydration_number = - 1 THEN 0
        ELSE tl.lt_severe_dehydration_number
    END) AS severe_dehydration,
    SUM(CASE
        WHEN tl.lt_some_dehydration_number = - 1 THEN 0
        ELSE tl.lt_some_dehydration_number
    END) AS some_dehydration,
    SUM(CASE
        WHEN tl.lt_no_dehydration_number = - 1 THEN 0
        ELSE tl.lt_no_dehydration_number
    END) AS no_dehydration,
    SUM(CASE
        WHEN tl.lt_dysentry_number = - 1 THEN 0
        ELSE tl.lt_dysentry_number
    END) AS dysentry,
    SUM(CASE
        WHEN tl.lt_no_Classification_number = - 1 THEN 0
        ELSE tl.lt_no_Classification_number
    END) AS no_classification
FROM
    log_treatment tl
WHERE
    tl.treatment_code IN (SELECT 
            treatment_code
        FROM
            treatments
        WHERE
            treatment_for = 'dia')
        AND tl.facility_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
    JOIN survey_status ss ON ss.fac_id = f.fac_mfl
    JOIN survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = 'ch') WHERE fac_county='$county');";
				$results = $this -> db -> query($query);
				return $results -> result_array();
				break;
			case 'Classification' :
				$final = array();
				$query = "SELECT 
    tl.treatment_code AS treatment,
    (SUM(CASE
        WHEN tl.lt_severe_Dehydration_number = - 1 THEN 0
        ELSE tl.lt_severe_Dehydration_number
    END) + SUM(CASE
        WHEN tl.lt_some_Dehydration_number = - 1 THEN 0
        ELSE tl.lt_some_Dehydration_number
    END) + SUM(CASE
        WHEN tl.lt_no_Dehydration_number = - 1 THEN 0
        ELSE tl.lt_no_Dehydration_number
    END) + SUM(CASE
        WHEN tl.lt_dysentry_number = - 1 THEN 0
        ELSE tl.lt_dysentry_number
    END) + SUM(CASE
        WHEN tl.lt_no_Classification_number = - 1 THEN 0
        ELSE tl.lt_no_Classification_number
    END)) as total
FROM
    log_treatment tl
WHERE
    tl.treatment_code IN (SELECT 
            treatment_code
        FROM
            treatments
        WHERE
            treatment_for = 'dia')
        AND tl.facility_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = 'ch')WHERE fac_county='$county')
GROUP BY tl.treatment_code
ORDER BY tl.treatment_code ASC";
				$results = $this -> db -> query($query);
				$results = $results -> result_array();

				foreach ($results as $result) {
					//echo $this->getChildHealthTreatmentName($result['treatment']);
					//$result['treatment']=$this->getChildHealthTreatmentName($result['treatment']);
					$final[$this -> getChildHealthTreatmentName($result['treatment'])][] = array('treatment' => $this -> getChildHealthTreatmentName($result['treatment']), 'total' => $result['total']);
				}
				return $final;
				break;
		}

	}

	/**
	 * Mother and Neonatal Health Section
	 */

	#Section 1
	#-----------------------------------------------------------------------------

	/**
	 * Nurses Deployed in Maternity
	 */
	public function getNursesDeployed($criteria, $value,  $survey) {
		$value = urldecode($value);
		/*using CI Database Active Record*/
		$data = array();
		
		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		$query = "SELECT 
    question_code, SUM(lq_response_count) as response
FROM
    log_questions
WHERE
    question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = 'nur')
        AND fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
GROUP BY question_code
ORDER BY question_code";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			//echo $this->db->last_query();die;
			foreach ($this->dataSet as $value_) {
				$question = $this -> getQuestionName($value_['question_code']);
				$response = $value_['response'];
				//1. collect the categories
				$data[$question][] = $response;

			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

		return $data;
	}

	/**
	 * Beds in facility
	 */
	public function getBeds($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$value = urldecode($value);
		$data = array();
	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		$query = "SELECT 
    question_code,SUM(lq_response_count) as response
FROM
    log_questions
WHERE
    question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = 'bed')
        AND fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
            GROUP BY question_code
ORDER BY question_code";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			foreach ($this->dataSet as $value_) {
				$question = $this -> getQuestionName($value_['question_code']);
				$response = $value_['response'];
				//1. collect the categories
				$data[$question][] = $response;

			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

		return $data;
	}

	/**
	 * 24 Hour Service
	 */
	public function getService($criteria, $value,  $survey) {
		$value = urldecode($value);
		/*using CI Database Active Record*/
		$data = array();
	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		$query = "SELECT 
    question_code,SUM(lq_response_count) as response
FROM
    log_questions
WHERE
    question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = 'serv')
        AND fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
            GROUP BY question_code
ORDER BY question_code";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			foreach ($this->dataSet as $value_) {
				$question = $this -> getQuestionName($value_['question_code']);
				$response = $value_['response'];
				//1. collect the categories
				$data[$question][] = $response;

			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

		return $data;
	}

	/**
	 * Health Facility Management
	 */
	public function getHFM($criteria, $value,  $survey) {
		$value = urldecode($value);
		/*using CI Database Active Record*/
		$data = array();
	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		$query = "SELECT 
    question_code,sum(if (lq_response ='Yes' , 1 , 0)) as yes_values,
    sum(if (lq_response ='No' , 1 , 0)) as no_values
FROM
    log_questions
WHERE
    question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = 'commi')
        AND fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
            GROUP BY question_code
ORDER BY question_code";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			foreach ($this->dataSet as $value_) {
				$question = $this -> getQuestionName($value_['question_code']);
				$yes = $value_['yes_values'];
				$no = $value_['no_values'];
				//1. collect the categories
				$data[$question]['yes'] = $yes;
				$data[$question]['no'] = $no;

			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

		return $data;
	}

	/**
	 * Deliveries
	 */
	public function getDeliveries($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$value = urldecode($value);
		$data = array();
	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		$query = "SELECT 
    question_code,
    sum(if (lq_response ='Yes' , 1 , 0)) as yes_values,
    sum(if (lq_response ='No' , 1 , 0)) as no_values
FROM
    log_questions
WHERE
    question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = 'prep')
        AND fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
GROUP BY question_code
ORDER BY question_code";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			foreach ($this->dataSet as $value_) {
				$question = $this -> getQuestionName($value_['question_code']);
				$yes = $value_['yes_values'];
				$no = $value_['no_values'];
				//1. collect the categories
				$data['overview'][$question]['yes'] = $yes;
				$data['overview'][$question]['no'] = $no;
			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}
		$query = "SELECT 
    question_code,
    sum(if (lq_response ='Yes' , 1 , 0)) as yes_values,
    	(fac_level) as facility_level
		FROM
		    log_questions
		JOIN 
			facilities
		ON
			facilities.fac_mfl = log_questions.fac_mfl and facilities.fac_level!=''
		WHERE
    question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = 'prep')
        AND facilities.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
               ".$criteria_condition.")
GROUP BY fac_level
ORDER BY fac_level;";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			foreach ($this->dataSet as $value_) {
				$fac_level = $value_['facility_level'];
				$yes = $value_['yes_values'];
				//1. collect the categories
				$data['drilldown'][$fac_level] = $yes;
			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

		return $data;
	}

	#Section 2
	#-----------------------------------------------------------------------------

	/**
	 * Deliveries Conducted
	 */
	public function getDeliveriesConducted($criteria, $value,  $survey) {
	}

	/**
	 * Signal Functions
	 * Options:
	 * 		.bemonc
	 * 		.cemonc
	 */
	public function getSignalFunction($criteria, $value,  $survey, $signal) {
		/*using CI Database Active Record*/
		$value = urldecode($value);
		/*using CI Database Active Record*/
		$data = array();
	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		switch($signal) {
			case 'bemonc' :
				$query = "SELECT 
    sf_code,
    sum(if(bem_conducted = 'Yes', 1, 0)) as yes_values,
    sum(if(bem_conducted = 'No', 1, 0)) as no_values
FROM
    bemonc_functions
WHERE
    sf_code IN (SELECT 
            sf_code
        FROM
            signal_functions)
        AND fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
".$criteria_condition.")
GROUP BY sf_code
ORDER BY sf_code";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();
					foreach ($this->dataSet as $value_) {
						$question = $this -> getSignalName($value_['sf_code']);
						$yes = $value_['yes_values'];
						$no = $value_['no_values'];
						//1. collect the categories
						$data['conducted'][$question]['yes'] = $yes;
						$data['conducted'][$question]['no'] = $no;

					}
					//die(var_dump($this->dataSet));
				} catch(exception $ex) {
					//ignore
					//die($ex->getMessage());//exit;
				}

				$query = "SELECT 
    count(*) as response,challenge_code,fac_level as level
FROM
    bemonc_functions join
    facilities f ON bemonc_functions.fac_mfl = f.fac_mfl AND f.fac_level!=''
WHERE
    sf_code IN (SELECT 
            sf_code
        FROM
            signal_functions)
        AND f.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
GROUP BY challenge_code,f.fac_level
ORDER BY challenge_code
";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();
					foreach ($this->dataSet as $value_) {
						$data['reason'][$value_['challenge_code']][$value_['level']] = (int)$value_['response'];
						$data['categories'][] = $value_['challenge_code'];
					}
					$data['categories'] = array_unique($data['categories']);
					//die(var_dump($this->dataSet));
				} catch(exception $ex) {
					//ignore
					//die($ex->getMessage());//exit;
				}

				return $data;
				break;
			case 'ceoc' :
				$query = "SELECT 
     question_code,
    sum(if (lq_response ='Yes' , 1 , 0)) as yes_values,
    sum(if (lq_response ='No' , 1 , 0)) as no_values
FROM
    log_questions
WHERE
    question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = '$signal')
        AND fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
            GROUP BY question_code
ORDER BY question_code";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();
					foreach ($this->dataSet as $value_) {
						$question = $this -> getQuestionName($value_['question_code']);
						$yes = $value_['yes_values'];
						$no = $value_['no_values'];
						//1. collect the categories
						$data['conducted'][$question]['yes'] = $yes;
						$data['conducted'][$question]['no'] = $no;

					}
					//die(var_dump($this->dataSet));
				} catch(exception $ex) {
					//ignore
					//die($ex->getMessage());//exit;
				}

				$query = "SELECT 
     count(*) as lq_response,lq_reason,question_code
FROM
    log_questions
WHERE
    question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = 'ceoc')
        AND fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
            GROUP BY lq_reason,question_code
ORDER BY question_code";
				try {
					$this -> dataSet = $this -> db -> query($query, array($value));
					$this -> dataSet = $this -> dataSet -> result_array();
					foreach ($this->dataSet as $value_) {
						$question = $this -> getSignalName($value_['question_code']);

						$data['reason'][$value_['lq_reason']][$value_['question_code']] = (int)$value_['lq_response'];
						$data['categories'][] = $question;
					}
					$data['categories'] = array_unique($data['categories']);
					//die(var_dump($this->dataSet));
				} catch(exception $ex) {
					//ignore
					//die($ex->getMessage());//exit;
				}

				return $data;
				break;
		}

	}

	/**
	 * Deliveries Conducted
	 */
	public function getQuestionStatistics($criteria, $value, $survey,$for) {
		/*using CI Database Active Record*/
		$value = urldecode($value);
		$data = array();
	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		$query = "SELECT 
     question_code,
    sum(if(lq_response = 'Yes', 1, 0)) as yes_values,
    sum(if(lq_response = 'No', 1, 0)) as no_values
FROM
    log_questions lq
WHERE
    lq.question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = '".$for."')
        AND lq.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f JOIN
    survey_status ss ON ss.fac_id = f.fac_mfl
        JOIN
    survey_types st ON (st.st_id = ss.st_id
        AND st.st_name = '".$survey."') ".$criteria_condition." )
GROUP BY lq.question_code
ORDER BY lq.question_code ASC";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			foreach ($this->dataSet as $value_) {
				$question = $this -> getQuestionName($value_['question_code']);
				$question = trim($question, 'Does this facility have an updated');
				$question = trim($question, '?');

				if ($question == 'Has the facility done baby friendly hospital initiative in the last 6 months') {
					$question = 'Baby Friendly Hospital Initiative';
				} else if ($question == 'National Guidelines for Quality Obstetric and Prenatal Care') {
					$question = 'Quality Obstetric and Prenatal Care';
				} else {

					//$question = trim($question, 'National Guidelines for ');
				}
				$yes = $value_['yes_values'];
				$no = $value_['no_values'];
				//1. collect the categories
				$data[$question]['yes'] = $yes;
				$data[$question]['no'] = $no;
			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

		return $data;
	}

	/**
	 * Community Strategy
	 */
	public function getCommunityStrategyMNH($criteria, $value,  $survey) {
		/*using CI Database Active Record*/
		$value = urldecode($value);
		/*using CI Database Active Record*/
		$data = array();
	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		$query = "SELECT 
    question_code,SUM(lq_response_count) as response
FROM
    log_questions
WHERE
    question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = 'cms')
        AND fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')
                 ".$criteria_condition.")
            GROUP BY question_code
ORDER BY question_code";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			foreach ($this->dataSet as $value_) {
				$question = $this -> getQuestionName($value_['question_code']);
				$response = $value_['response'];
				//1. collect the categories
				$data[$question][] = $response;

			}
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

		return $data;

	}

	#Summary Excel
	#-----------------------------------------------------------------------------
	/**
	 *
	 */
	public function commodities_supplies_summary($criteria, $value,  $survey) {/*using CI Database Active Record*/
		$value = urldecode($value);
		/*using CI Database Active Record*/
		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}
		$query = "SELECT 
    f.fac_name,f.fac_district,SUM(ca.ac_quantity) AS total_quantity,
    ca.comm_code as commodities,commodities.comm_unit AS unit
FROM
    available_commodities as ca
        INNER JOIN
    facilities as f ON ca.fac_mfl = f.fac_mfl,
    commodities
WHERE
commodities.comm_code=ca.comm_code AND
    ca.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')" . $criteria_condition . ")
        AND ca.comm_code IN (SELECT 
            comm_code
        FROM
            commodities
        WHERE
            comm_for = '".$survey."')
        AND ca.ac_quantity != - 1
GROUP BY f.fac_name,ca.comm_code
ORDER BY f.fac_district,f.fac_name,ca.comm_code;";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			//echo $this->db->last_query();
			$data['commodity_categories'][]='Facility Name';
			$data['commodity_categories'][]='District/Sub County';
			foreach ($this->dataSet as $value_) {
				//$data['commodities_categories'][0] = 'Facility Name';
				$supply = $this -> getCommodityNameById($value_['commodities'], $survey) . ' ' . $value_['unit'];
				$facility = $value_['fac_name'];
				$district = $value_['fac_district'];
				//$response = $value_['supplies'];
				//1. collect the categories
				$data['commodities'][$facility]['facility'] = $facility;
				$data['commodities'][$facility]['district'] = $district;
				$data['commodities'][$facility][$supply] = $value_['total_quantity'];
				$data['commodity_categories'][] = $supply;

			}
			$data['commodity_categories'] = array_unique($data['commodity_categories']);
//echo '<pre>';print_r($data['commodity_categories']);echo '</pre>';
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

		$query = "SELECT 
    f.fac_name,f.fac_district,SUM(sa.as_quantity) AS total_quantity,
    sa.supply_code as Supplies
FROM
    available_supplies as sa
        INNER JOIN
    facilities as f ON sa.fac_mfl = f.fac_mfl,
    Supplies
WHERE
Supplies.supply_code=sa.supply_code AND
    sa.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')" . $criteria_condition . ")
        AND sa.supply_code IN (SELECT 
            supply_code
        FROM
            Supplies
        WHERE
            supply_for = '".$survey."')
        AND sa.as_quantity != - 1
GROUP BY f.fac_name,sa.supply_code
ORDER BY f.fac_name,sa.supply_code;";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			foreach ($this->dataSet as $value_) {
				$supply = $this -> getSupplyName($value_['Supplies'], $survey);
				$facility = $value_['fac_name'];
				$district = $value_['fac_district'];
				//1. collect the categories
				$data['supplies'][$facility][$supply] = $value_['total_quantity'];
				$data['supply_categories'][] = $supply;

			}
			$data['supply_categories'] = array_unique($data['supply_categories']);
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

		$query = "SELECT 
    f.fac_name,
    f.fac_district,
    SUM(sa.ae_fully_functional) AS fully,
    SUM(sa.ae_non_functional) AS non,
    sa.eq_code as Equipments
FROM
    available_equipments as sa
        INNER JOIN
    facilities as f ON sa.fac_mfl = f.fac_mfl
WHERE
    sa.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')" . $criteria_condition . ")
        AND sa.eq_code IN (SELECT 
            eq_code
        FROM
            equipments
        WHERE
            eq_for = '".$survey."')
        AND sa.ae_fully_functional != - 1 AND sa.ae_non_functional!=-1
GROUP BY f.fac_name , sa.eq_code
ORDER BY f.fac_district,f.fac_name , sa.eq_code;";
		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();
			//echo $this->db->last_query();
			
			foreach ($this->dataSet as $value_) {
				//$data['commodities_categories'][0] = 'Facility Name';
				$supply = $this -> getCHEquipmentName($value_['Equipments'], $survey);
				$facility = $value_['fac_name'];
				$district = $value_['fac_district'];
				//$response = $value_['supplies'];
				//1. collect the categories
				$data['equipments'][$facility][$supply. ' (Fully Functional)'] = $value_['fully'];
				$data['equipments'][$facility][$supply. ' (Non-Functional)'] = $value_['non'];
				$data['equipment_categories'][] = $supply. ' (Fully Functional)';
				$data['equipment_categories'][] = $supply. ' (Non-Functional)';

			}
			$data['equipment_categories'] = array_unique($data['equipment_categories']);
//echo '<pre>';print_r($data['equipment_categories']);echo '</pre>';die;
			//die(var_dump($this->dataSet));
		} catch(exception $ex) {
			//ignore
			//die($ex->getMessage());//exit;
		}

		return $data;

	}

	public function getFacilityListForNoMNH($criteria, $value,  $survey, $question) {
		urldecode($value);
	

		switch($criteria) {
			case 'national' :
				$criteria_condition = ' ';
				$value = ' ';
				break;
			case 'county' :
				$criteria_condition = 'WHERE fac_county=?';
				break;
			case 'district' :
				$criteria_condition = 'WHERE fac_district=?';
				break;
			case 'facility' :
				$criteria_condition = 'WHERE fac_mfl=?';
				break;
			case 'none' :
				$criteria_condition = '';
				break;
		}

		$query = "SELECT 
    q.question_name, lq.fac_mfl, f.fac_name
FROM
    log_questions lq,
    questions q,
    facilities f
WHERE
    lq.lq_response = 'No'
        AND lq.question_code = q.question_code 
        AND lq.fac_mfl = f.fac_mfl
        AND lq.question_code IN (SELECT 
            question_code
        FROM
            questions
        WHERE
            question_for = '$question')
        AND lq.fac_mfl IN (SELECT 
            fac_mfl
        FROM
            facilities f
                JOIN
            survey_status ss ON ss.fac_id = f.fac_mfl
                JOIN
            survey_types st ON (st.st_id = ss.st_id
                AND st.st_name = '".$survey."')" . $criteria_condition . ") ";

		try {
			$this -> dataSet = $this -> db -> query($query, array($value));
			$this -> dataSet = $this -> dataSet -> result_array();

			if ($this -> dataSet !== NULL) {

				$size = count($this -> dataSet);
				$i = 0;
				$facilities = array();
				foreach ($this->dataSet as $value) {
					$facilities[$value['question_name']][] = array($value['fac_mfl'], $value['fac_name']);
				}
				return $facilities;

				//var_dump($this->dataSet);die;

			} else {
				return $this -> dataSet = null;
			}
		} catch(exception $ex) {
		}

	}

}
