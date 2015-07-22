<?php

class SearchController extends BaseController {

	public function __construct(){
		parent::__construct();
	}

	public function index($lang, $area){

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

		if($data['detail_search_by'] == 'station'){
			if(Input::get('station') != NULL || Input::get('station') != ''){
				// get stations
				$stations = explode('-', Input::get('station'));
				Input::merge(array('station' => $stations));
				$this->getStations($stations);
			}
		}elseif($data['detail_search_by'] == 'area'){
			if(Input::get('area') != NULL || Input::get('area') != ''){
				// get areas as array
				$areas = explode('-', Input::get('area'));
				Input::merge(array('area' => $areas));
				$this->getAreas($areas);
			}
		}
		if(Input::get('freeword') != NULL){
			$data['freewords'] = explode(' ', str_replace('　', ' ', Input::get('freeword')));
		}
		
		// get review items
		$this->getReviewItems();

		// get items informations
		$this->getItemInformations();

		// get orders
		$this->getOrders();

		// set search conditions
		$data['searchs'] = Input::all();

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
		$data['keywords'] = implode(",", $data['page_keywords']) . "," . $data['keywords'];
		$data['description'] = $data['page_title'] . " | " . $data['description'];
		$data['breadcrumbs'] = array(
			array("text" => $data['texts']['home'], "url" => "/"),
			array("text" => $data['search_ways']['detail'][$data['language']], "url" => "/details"),
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
		if(isset($data['stations'])){
			foreach($data['stations'] as $key => $station){
				$array[] = $station[$data['language']] . $data['texts']['glue'] . $data['texts']['station'];
			}
		}
		if(isset($data['areas'])){
			foreach($data['areas'] as $key => $areas){
				$array[] = $areas[$data['language']];
			}
		}
		if(Input::get('sub_category') != NULL){
			foreach($data['sub_categories'] as $key => $sub_category){
				if($sub_category['code'] == Input::get('sub_category')){
					$array[] = $sub_category[$data['language']];
				}
			}
		}
		if(isset($data['freewords'])){
			foreach($data['freewords'] as $key => $freeword){
				$array[] = $freeword;
			}
		}
		if(Input::get('details') != NULL){
			foreach(Input::get('details') as $key => $row){
				$thisItem = $data['item_informations'][$data['display_way']][$key];
				if($thisItem['settings']['search_type'] == 'range'){
					$thisFirstSuffix = '';
					$thisLastSuffix = '';
					if($thisItem['settings']['type'] == 'currency'){
						if((int) $row['min'] > 1){
							$thisFirstSuffix = $data['currency'][$data['language'] . '_plural'];
						}else{
							$thisFirstSuffix = $data['currency'][$data['language']];
						}
						if((int) $row['max'] > 1){
							$thisLastSuffix = $data['currency'][$data['language'] . '_plural'];
						}else{
							$thisLastSuffix = $data['currency'][$data['language']];
						}
					}
					$thisString = $row['min'] . $data['texts']['glue'] . $thisFirstSuffix . $data['texts']['to_symbol_glue'] . $row['max'] . $data['texts']['glue'] . $thisLastSuffix;
					$array[] = $thisString;
				}
				if($thisItem['settings']['search_type'] == 'boolean'){
					if($row == 'true'){
						$array[] = $thisItem[$data['language']];
					}
				}
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

	/**
	 * Create page keywords.
	 *
	 * @return array
	 */
	private function createResultPageKeywords(){
		
		global $data;
		
		$array = array();
		foreach($data['stations'] as $key => $station){
			$array[$key] = $station[$data['language']] . $data['texts']['glue'] . $data['texts']['station'];
		}
		$keywords = implode(',', $array);
		return $keywords;
	}
	
}
