<?php

class DetailController extends BaseController {

	public function __construct(){
		parent::__construct();
	}

	public function index($lang){

		global $data;
		global $settings;
		
		$this->getAreaGroups();
		$data['station_lines'] = $this->getStationLineList();
		$data['detail_search_by'] = 'station';

		// get items informations
		$this->getItemInformations();

		// set display ways
		if(Input::get('display_way') != NULL){
			$data['display_way'] = Input::get('display_way');
		}else{
			$data['display_way'] = $settings['list_display_ways'][0];
		}

		// get sub categories
		$data['sub_categories'] = $this->getSubCategoryList($data['categories'][$data['display_way']]['code']);

		// create metas
		$data['page_title'] = $data['search_ways']['detail'][$data['language']];
		$data['title'] = $data['page_title'] . " | " . $data['title'];
		$data['keywords'] = $data['page_title'] . "," . $data['keywords'];
		$data['description'] = $data['page_title'] . " | " . $data['description'];
		$data['breadcrumbs'] = array(
			array("text" => $data['texts']['home'], "url" => "/"),
			array("text" => $data['search_ways']['detail'][$data['language']])
		);
		
		// set settings
		$this->setSettingsInData();
		
		return Response::json($data)->setCallback(Input::get('callback'));

	}

}
