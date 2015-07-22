<?php

class FreewordController extends BaseController {

	public function __construct(){
		parent::__construct();
	}

	public function index($lang){

		global $data;

		// create metas
		$pageTitle = $data['search_ways']['freeword'][$data['language']];
		$data['title'] = $pageTitle . " | " . $data['title'];
		$data['keywords'] = $pageTitle . "," . $data['keywords'];
		$data['description'] = $pageTitle . " | " . $data['description'];
		$data['breadcrumbs'] = array(
			array("text" => $data['texts']['home'], "url" => "/"),
			array("text" => $data['search_ways']['freeword'][$data['language']], "url" => "/freewords")
		);
		
		return Response::json($data)->setCallback(Input::get('callback'));
		
	}
	
	public function result($freewords, $lang, $area){

		global $data;
		global $settings;

		$this->getAreaGroups();
		$data['station_lines'] = $this->getStationLineList();

		if(Input::get('search_by') == NULL){
			Input::merge(array('search_by' => 'station'));
		}

		$data['detail_search_by'] = Input::get('search_by');

		// set perPage
		$data['per_page'] = $settings['max_display_list']->default;
		if(in_array(Input::get('per-page'), $settings['max_display_list']->list)){
			$data['per_page'] = Input::get('per-page');
		}

		if($freewords != NULL){
			$data['freewords'] = explode(' ', str_replace('　', ' ', $freewords));
			Input::merge(array('freeword' => $freewords));
		}
		
		// get review items
		$this->getReviewItems();

		// get items informations
		$this->getItemInformations();

		// get orders
		$this->getOrders();

		// set search conditions
		$conditions = Input::all();
		$data['searchs'] = $conditions;

		// set display ways
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
		$this->getListsBySearch($data['searchs'], $data['display_way'], $data['per_page'], $data['order']['item'], $data['order']['order']);

		// create metas
		$data['page_keywords'] = $this->createPageKeywords();
		$data['page_title'] = $this->createResultPageTitle();
		$data['title'] = $data['page_title'] . " | " . $data['title'];
		$data['keywords'] = $data['page_title'] . "," . $data['keywords'];
		$data['description'] = $data['page_title'] . " | " . $data['description'];
		$data['breadcrumbs'] = array(
			array("text" => $data['texts']['home'], "url" => "/"),
			array("text" => $data['search_ways']['freeword'][$data['language']], "url" => "/freeword"),
			array("text" => $data['page_title'])
		);
		
		
		// set settings
		$this->setSettingsInData();

		return Response::json($data)->setCallback(Input::get('callback'));

	}

	/**
	 * Create page keywords.
	 *
	 * @return array
	 */
	private function createPageKeywords(){
	
		global $data;
		
		$array = array();
		if(isset($data['freewords'])){
			foreach($data['freewords'] as $key => $freeword){
				$array[] = $freeword;
			}
		}

		return $array;

	}
	
	/**
	 * Create page title.
	 *
	 * @return array
	 */
	private function createResultPageTitle(){
		
		global $data;

		$pageKeywords = $data['page_keywords'];
		$pageKeywords[] = $data['siteGenre'][$data['language']];	
		if($data['language'] == 'ja'){
			$title = implode($data['texts']['cross_glue'], $pageKeywords);
		}else{
			$title = implode($data['texts']['cross_glue'], $pageKeywords);
		}
		return $title;
	}

	
}
