<?php

class AreaController extends BaseController {

	public function __construct(){
		parent::__construct();
	}

	public function index($lang){

		global $data;
		$this->getAreaGroups();
		$this->getAreas();
		
		// create metas
		$pageTitle = $data['search_ways']['area'][$data['language']];
		$data['title'] = $pageTitle . " | ". $data['title'];
		$data['keywords'] = $pageTitle . ",". $data['keywords'];
		$data['description'] = $pageTitle . " | ". $data['description'];
		$data['breadcrumbs'] = array(
			array("text" => $data['texts']['home'], "url" => "/"),
			array("text" => $data['search_ways']['area'][$data['language']])
		);

		return Response::json($data)->setCallback(Input::get('callback'));

	}

	public function result($areas, $lang, $area){

		global $data;
		global $settings;

		$this->getAreaGroups();
		$data['station_lines'] = $this->getStationLineList();
		$data['detail_search_by'] = 'area';

		// set perPage
		$data['per_page'] = $settings['max_display_list']->default;
		if(in_array(Input::get('per-page'), $settings['max_display_list']->list)){
			$data['per_page'] = Input::get('per-page');
		}

		// get areas as array
		$areas = explode('-', $areas);
		$this->getAreas($areas);

		// get review items
		$this->getReviewItems();

		// get items informations
		$this->getItemInformations();

		// get orders
		$this->getOrders();

		// set display way
		if(Input::get('display_way') != NULL){
			$data['display_way'] = Input::get('display_way');
		}else{
			$data['display_way'] = $settings['list_display_ways'][0];
		}

		// get sub categories
		$data['sub_categories'] = $this->getSubCategoryList($data['categories'][$data['display_way']]['code']);

		// get lists
		if(Input::get('order') != NULL){
			if(in_array(Input::get('order'), $data['categories'][$data['display_way']]['settings']['order_lists']['lists'])){
				$data['order'] = $data['orders'][Input::get('order')];
			}else{
				$data['order'] = $data['orders'][$data['categories'][$data['display_way']]['settings']['order_lists']['default']];
			}
		}else{
			$data['order'] = $data['orders'][$data['categories'][$data['display_way']]['settings']['order_lists']['default']];
		}
		$conditions = array(
			'search_by' => 'area',
			'area' => $areas
		);
		$this->getListsBySearch($conditions, $data['display_way'], $data['per_page'], $data['order']['item'], $data['order']['order']);
		
		// create metas
		$data['page_title'] = $this->createResultPageTitle();
		$data['title'] = $data['page_title'] . " | ". $data['title'];
		$data['keywords'] = $this->createResultPageKeywords() . "," . $data['keywords'];
		$data['description'] = $data['page_title'] . " | ". $data['description'];
		$data['breadcrumbs'] = array(
			array("text" => $data['texts']['home'], "url" => "/"),
			array("text" => $data['search_ways']['area'][$data['language']], "url" => "/area"),
			array("text" => $data['page_title'])
		);
		
		// set settings
		$this->setSettingsInData();
		
		return Response::json($data)->setCallback(Input::get('callback'));

	}

	public function lists($lang, $area){

		global $data;
		global $settings;

		if(Request::exists('area-group-id')){
			$result = $this->getAreaList(Request::input('area-group-id'));
			return Response::json($result)->setCallback(Input::get('callback'));
		}

	}
		
	/**
	 * Create page title.
	 *
	 * @return array
	 */
	private function createResultPageTitle(){
		
		global $data;
		
		$array = array();
		foreach($data['areas'] as $key => $areas){
			$array[$key] = $areas[$data['language']];
		}
		if($data['language'] == 'ja'){
			$title = implode($data['texts']['delimiter'], $array) . $data['texts']['in'] . $data['siteGenre'][$data['language']];
		}else{
			$title = $data['siteGenre'][$data['language']] . $data['texts']['glue'] . $data['texts']['in'] . $data['texts']['glue'] . implode($data['texts']['delimiter'], $array);
		}
		return $title;
	}

	/**
	 * Create page keywords.
	 *
	 * @return array
	 */
	private function createResultPageKeywords(){
		
		global $data;
		
		$array = array();
		foreach($data['areas'] as $key => $area){
			$array[$key] = $area[$data['language']] . $data['texts']['glue'];
		}
		$keywords = implode(',', $array);
		return $keywords;
	}
	
}
