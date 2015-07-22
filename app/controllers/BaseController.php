<?php

class BaseController extends Controller {

	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}

	public function __construct(){

		global $data;
		global $settings;
		$data = array();
		$this->beforeFilter(function(){
			$this->getSettings();
			$this->getTexts();
			$this->getSearchWays();
			$this->getLanguages();
			$this->getSiteGenre();
			$this->getSiteArea();
			$this->getCountry();
			$this->getCurrency();
			$this->getSocialNetworks();
			$this->getInformations();
			$this->getCategories();
			$this->createPageMeta();
			$this->setSettingsInData();
		});

	}

	/**
	 * Create page meta.
	 *
	 * @return void
	 */
	public function createPageMeta(){

		global $data;
		global $settings;
		
		$data['title'] = $data['texts']['catch_copy'] . " | " . $data['texts']['title'];
		$data['keywords'] = $data['area'][$data['language']] . "," . $data['siteGenre'][$data['language']];
		$data['description'] = $data['texts']['description'];
		
	}

	/**
	 * Get settings from DB.
	 *
	 * @return array
	 */
	public function getSettings(){

		global $data;
		global $settings;

		$rawSettings = Setting::all();
		$settings = array();
		foreach($rawSettings as $key => $data){
			if($data['type'] == 'array'){
				$data['value'] = json_decode($data['value']);
			}
			$settings[$data['code']] = $data['value'];
		}

	}

	/**
	 * Get country
	 *
	 * @return void
	 */
	public function getCountry(){
		
		global $data;
		global $settings;

		$data['country'] = Country::where('id', '=', $data['area']['country_id'])->get()->toArray()[0];

	}

	/**
	 * Get currency
	 *
	 * @return void
	 */
	public function getCurrency(){
		
		global $data;
		global $settings;

		$data['currency'] = Currency::where('country_id', '=', $data['area']['country_id'])->get()->toArray()[0];

	}

	/**
	 * Set settings in data
	 *
	 * @return array
	 */
	public function setSettingsInData(){

		global $data;
		global $settings;

		$data['settings'] = $settings;

	}

	/**
	 * Get texts for rendering all texts.
	 *
	 * @return void
	 */
	public function getTexts(){

		global $data;

		$controllerAction = Route::currentRouteAction();
		list($controller, $action) = explode('@', $controllerAction);
		$controller = strtolower(str_replace('Controller', '', $controller));
		$lang = Route::input('lang');

		$data['texts'] = Text::where('controller', '=', $controller)
						->where('action', '=', $action)
						->orWhere(function($query){
							$query->where('action', '=', 'global');
						})
						->lists($lang, 'code');
		$data['controller'] = $controller;
		$data['action'] = $action;
		$data['language'] = $lang;
		

	}

	/**
	 * Get search ways for rendering menu.
	 *
	 * @return void
	 */
	public function getSearchWays(){

		global $data;
		global $settings;

		$lang = Route::input('lang');

		$searchWays = SearchWay::whereIn('code', $settings['search_ways'])
			->orderBy(DB::raw("FIELD(code, '" . implode("','", $settings['search_ways']) . "')"))
			->get()->keyBy('code')->toArray();
		$data['search_ways'] = $searchWays;

	}

	/**
	 * Get languages for rendering language menu.
	 *
	 * @return void
	 */
	public function getLanguages(){

		global $data;
		global $settings;

		$data['languages'] = Language::whereIn('code', $settings['languages'])->get()->toArray();

	}

	/**
	 * Get site genre for rendering genre text.
	 *
	 * @return void
	 */
	public function getSiteGenre(){

		global $data;
		global $settings;

		$data['siteGenre'] = SiteGenre::where('code', $settings['site_genre'])->find(1)->toArray();

	}

	/**
	 * Get site area for rendering area text.
	 *
	 * @return void
	 */
	public function getSiteArea(){

		global $data;
		global $settings;

		$area = Route::input('area');

		$data['area'] = City::where('code', $area)->firstOrFail()->toArray();

	}

	/**
	 * Get social networks for rendering social network links.
	 *
	 * @return void
	 */
	public function getSocialNetworks(){

		global $data;
		global $settings;

		$data['social_networks'] = SocialNetwork::all()->toArray();

	}

	/**
	 * Get photo sub category list rendering photo sub categories.
	 *
	 * @return void
	 */
	public function getSubCategoryList($categoryCode){

		global $data;
		global $settings;

		$thisCategoryId = $data['categories'][$categoryCode]['id'];
		$thisSubCategories = $data['categories'][$categoryCode]['settings']['sub_categories'];
		$stmt = SubCategory::where('category_id', '=', $thisCategoryId);
		$stmt->whereIn('code', $thisSubCategories);
		$stmt->orderBy(DB::raw("FIELD(code, '" . implode("','", $thisSubCategories) . "')"));
		$result = $stmt->get()->keyBy('code')->toArray();

		return $result;

	}

	/**
	 * Get photos for rendering photos.
	 *
	 * @return void
	 */
	public function getPhotos(){

		global $data;
		global $settings;

		$photos = array();
		foreach($settings['list_display_ways'] as $ld_key => $ld_row){
			if($ld_row == $settings['customer_category']){
				$ld_row = $settings['customer_category'];
			}
			$thisCategoryId = $data['categories'][$ld_row]['id'];
			$photos[$thisCategoryId] = array();
			foreach($data['categories'][$ld_row]['settings']['sub_categories'] as $sc_key => $sc_row){
				$photos[$thisCategoryId][] = Photo::leftJoin('items', 'photos.item_id', '=', 'items.id')
						->leftJoin('customers', 'items.customer_id', '=', 'customers.id')
						->where('customers.city_id', '=', $data['area']['id'])
						->where('items.sub_category_id', '=', $data['sub_categories'][$ld_row][$sc_row]['id'])
						->orderBy('photos.created_at', 'DESC')
						->take($settings['top_photo_max'])
						->get()
						->toArray();
			}
		}
		$data['photos'] = $photos;

	}

	/**
	 * Get station line groups for rendering station line groups.
	 *
	 * @return void
	 */
	public function getStationLineGroups(){

		global $data;
		global $settings;

		$data['station_line_groups'] = StationLineGroup::where('city_id', '=', $data['area']['id'])->get()->toArray();

	}

	/**
	 * Get station line list.
	 *
	 * @return void
	 */
	public function getStationLineList(){

		global $data;
		global $settings;

		$stationLines = StationLine::select('station_lines.*')->join('stations', 'stations.station_line_id', '=', 'station_lines.id')->where('stations.city_id', '=', $data['area']['id'])->groupBy('station_lines.id')->get()->toArray();

		return $stationLines;

	}

	/**
	 * Get station lines for rendering station lines.
	 *
	 * @return void
	 */
	public function getStationLines(){

		global $data;
		global $settings;

		$stationLines = $this->getStationLineList();
		$array = array();
		foreach($stationLines as $key => $row){
			if(!isset($array[$row['station_line_group_id']])){
				$array[$row['station_line_group_id']] = array();
			}
			$array[$row['station_line_group_id']][] = $row;
		}
		$data['station_lines'] = $array;

	}

	/**
	 * Get stations list for rendering stations.
	 *
	 * @return void
	 */
	public function getStationList($lineId = NULL, $stations = array()){

		global $data;
		global $settings;

		$stmt = Station::where('city_id', '=', $data['area']['id']);
		if($lineId != NULL){
			$stmt->where('station_line_id', '=', $lineId);
		}
		if(!empty($stations)){
			$stmt->whereIn('code', $stations);
			$stmt->groupBy('code');
		}

		$stations = $stmt->get()->toArray();

		return $stations;

	}

	/**
	 * Get stations for rendering stations.
	 *
	 * @return void
	 */
	public function getStations($stations = array()){

		global $data;
		global $settings;

		$station = $this->getStationList(NULL, $stations);
		if(empty($stations)){
			$array = array();
			foreach($station as $key => $row){
				if(!isset($array[$row['station_line_id']])){
					$array[$row['station_line_id']] = array();
				}
				$array[$row['station_line_id']][] = $row;
			}
			$station = $array;
		}
		$data['stations'] = $station;

	}

	/**
	 * Get area groups for rendering areas groups.
	 *
	 * @return void
	 */
	public function getAreaGroups(){

		global $data;
		global $settings;

		$data['area_groups'] = AreaGroup::where('city_id', '=', $data['area']['id'])->get()->toArray();

	}

	/**
	 * Get area list for rendering areas.
	 *
	 * @return void
	 */
	public function getAreaList($areaGroupId = NULL, $areas = array()){

		global $data;
		global $settings;

		$stmt = Area::where('city_id', '=', $data['area']['id']);
		if($areaGroupId != NULL){
			$stmt->where('area_group_id', '=', $areaGroupId);
		}
		if(!empty($areas)){
			$stmt->whereIn('code', $areas);
			$stmt->groupBy('code');
		}
		$area = $stmt->get()->toArray();

		return $area;

	}

	/**
	 * Get areas for rendering areas.
	 *
	 * @return void
	 */
	public function getAreas($areas = array()){

		global $data;
		global $settings;

		$area = $this->getAreaList(NULL, $areas);
		if(empty($areas)){
			$array = array();
			foreach($area as $key => $row){
				if(!isset($array[$row['area_group_id']])){
					$array[$row['area_group_id']] = array();
				}
				$array[$row['area_group_id']][] = $row;
			}
			$area = $array;
		}
		$data['areas'] = $area;

	}

	/**
	 * Get lists by search for rendering result list.
	 *
	 * @return void
	 */
	public function getListsBySearch($conditions, $display_way, $perPage, $orderType, $order){

		global $data;
		global $settings;
		
		$lists = array();
		if($display_way == $settings['customer_category']){

			$lists = $this->getCustomerListsBySearch($conditions, $perPage, $orderType, $order);

		}else{

			$lists = $this->getItemListsBySearch($conditions, $display_way, $perPage, $orderType, $order);

		}

		$data['result_lists'] = $lists;

	}

	/**
	 * Get customer lists by search for rendering result list.
	 *
	 * @return void
	 */
	public function getCustomerListsBySearch($conditions, $perPage, $orderType, $order){

		global $data;
		global $settings;

		$columns = array(
			'*',
			'customers.id as customer_id',
			'customers.name_' . $data['language'] . ' as title',
			'customers.content_' . $data['language'] . ' as content',
			'stations.' . $data['language'] . ' as station',
			'areas.' . $data['language'] . ' as area',
			DB::raw("GROUP_CONCAT(photos.path SEPARATOR ',') as photos"),
			DB::raw("AVG(reviews.rate) as review_average")
		);
		if(isset($conditions['details'])){
			foreach($conditions['details'] as $key => $detail){
				if($data['item_informations'][$settings['customer_category']][$key]['settings']['search_type'] == 'range'){
					$columns[] = DB::raw("CAST(common_schema.extract_json_value(customers.informations, '/" . $key . "') AS UNSIGNED) as " . $key);
				}else{
					$columns[] = DB::raw("common_schema.extract_json_value(customers.informations, '/" . $key . "') as " . $key);
				}
			}
		}

		$stmt = Customer::select($columns)
			->leftJoin('stations', 'customers.station_id', '=', 'stations.id')
			->leftJoin('areas', 'customers.area_id', '=', 'areas.id')
			->leftJoin('items', 'customers.id', '=', 'items.customer_id')
			->leftJoin('sub_categories', 'items.sub_category_id', '=', 'sub_categories.id')
			->leftJoin('categories', function($join){
				$join->on('sub_categories.category_id', '=', 'categories.id');
				$join->on('categories.code', '=', DB::raw("'store'"));
			})
			->leftJoin('photos', function($join){
				$join->on('items.id', '=', 'photos.item_id');
				$join->on('categories.code', DB::raw('IS NOT'), DB::raw("NULL"));
			})
			->leftJoin('reviews', function($join){
				$join->on('customers.id', '=', 'reviews.customer_id');
				$join->on('reviews.item_id', DB::raw('IS'), DB::raw('NULL'));
			})
			->where('customers.city_id', '=', $data['area']['id']);
		if($conditions['search_by'] == 'station' && isset($conditions['station'])){
			if($conditions['station'] != ''){
				$stmt->whereIn('stations.code', $conditions['station']);
			}
		}elseif($conditions['search_by'] == 'area' && isset($conditions['area'])){
			if($conditions['area'] != ''){
				$stmt->whereIn('areas.code', $conditions['area']);
			}
		}
		if(isset($conditions['freeword'])){
			$freewords = explode(' ', str_replace('　', ' ', $conditions['freeword']));
			foreach($freewords as $key => $freeword){
				$stmt->where(function($query) use ($data, $freeword){
					$query->where('customers.name_' . $data['language'], 'like', '%' . $freeword . '%');
					$query->orWhere('customers.content_' . $data['language'], 'like', '%' . $freeword . '%');
					$query->orWhere('stations.' . $data['language'], 'like', '%' . $freeword . '%');
					$query->orWhere('areas.' . $data['language'], 'like', '%' . $freeword . '%');
				});
			}
		}
		if(isset($conditions['details'])){
			foreach($conditions['details'] as $key => $detail){
				// Range
				if($data['item_informations'][$settings['customer_category']][$key]['settings']['search_type'] == 'range'){
					$stmt->whereBetween(DB::raw("CAST(common_schema.extract_json_value(customers.informations, '/" . $key . "') AS UNSIGNED)"), array($detail['min'], $detail['max']));
				}
				// Boolean
				if($data['item_informations'][$settings['customer_category']][$key]['settings']['search_type'] == 'boolean'){
					if($detail == 'true'){
						$stmt->where(DB::raw("common_schema.extract_json_value(customers.informations, '/" . $key . "')"), '=', DB::raw(($detail === 'true')));
					}
				}
			}
		}
		$stmt->groupBy('customers.id')
			->orderBy('customers.validity_flag', 'DESC');
		if($orderType == 'recent'){
			$stmt->orderBy('customers.created_at', 'DESC');
		}elseif($orderType == 'rating'){
			$stmt->orderBy('review_average', 'DESC');
		}else{
			$stmt->orderBy($orderType, $order);
		}
		//print($stmt->toSql());
		//exit;

		$result = $stmt->paginate($perPage)->toArray();
		$result['range'] = $this->makePaginationLinks($result);

		foreach($result['data'] as $key => $row){
			$result['data'][$key]['reviews'] = array();
			foreach($data['review_items'][$settings['customer_category']] as $reviewItem){
				$result['data'][$key]['reviews'][$reviewItem['code']] = array();
				$result['data'][$key]['reviews'][$reviewItem['code']] = $this->getReviews(NULL, $reviewItem['id'], $result['data'][$key]['customer_id']);
			}
		}

		return $result;

	}

	/**
	 * Get item lists by search for rendering result list.
	 *
	 * @return void
	 */
	public function getItemListsBySearch($conditions, $display_way, $perPage, $orderType, $order){

		global $data;
		global $settings;

		$columns = array(
			'*',
			DB::raw("items.id as id"),
			'items.title_' . $data['language'] . ' as title',
			'items.content_' . $data['language'] . ' as content',
			'sub_categories.' . $data['language'] . ' as sub_category',
			'categories.code as category_code',
			'stations.' . $data['language'] . ' as station',
			'areas.' . $data['language'] . ' as area',
			'items.informations as informations',
			DB::raw("GROUP_CONCAT(photos.path SEPARATOR ',') as photos"),
			DB::raw("AVG(reviews.rate) as review_average")
		);
		if(isset($conditions['details'])){
			foreach($conditions['details'] as $key => $detail){
				if($data['item_informations'][$display_way][$key]['settings']['search_type'] == 'range'){
					$columns[] = DB::raw("CAST(common_schema.extract_json_value(items.informations, '/" . $key . "') AS UNSIGNED) as " . $key);
				}else{
					$columns[] = DB::raw("common_schema.extract_json_value(items.informations, '/" . $key . "') as " . $key);
				}
			}
		}

		if($orderType != 'recent'){
			if($orderType == 'price'){
				$columns[] = DB::raw("CAST(common_schema.extract_json_value(items.informations, '/" . $orderType . "') AS UNSIGNED) as " . $orderType);
			}else{
				$columns[] = DB::raw("common_schema.extract_json_value(items.informations, '/" . $orderType . "') as " . $orderType);
			}
		}

		$stmt = Item::select($columns)
			->leftJoin('customers', 'items.customer_id', '=', 'customers.id')
			->leftJoin('sub_categories', 'items.sub_category_id', '=', 'sub_categories.id')
			->leftJoin('categories', 'sub_categories.category_id', '=', 'categories.id')
			->leftJoin('stations', 'customers.station_id', '=', 'stations.id')
			->leftJoin('areas', 'customers.area_id', '=', 'areas.id')
			->leftJoin('photos', 'items.id', '=', 'photos.item_id')
			->leftJoin('reviews', 'items.id', '=', 'reviews.item_id')
			->where('customers.city_id', '=', $data['area']['id'])
			->where('categories.code', '=', $display_way);
		if($conditions['search_by'] == 'station' && isset($conditions['station'])){
			if($conditions['station'] != ''){
				$stmt->whereIn('stations.code', $conditions['station']);
			}
		}elseif($conditions['search_by'] == 'area' && isset($conditions['area'])){
			if($conditions['area'] != ''){
				$stmt->whereIn('areas.code', $conditions['area']);
			}
		}
		if(isset($conditions['sub_category'])){
			$stmt->where('sub_categories.code', '=', $conditions['sub_category']);
		}
		if(isset($conditions['freeword'])){
			$freewords = explode(' ', str_replace('　', ' ', $conditions['freeword']));
			foreach($freewords as $key => $freeword){
				$stmt->where(function($query) use ($data, $freeword){
					$query->where('items.title_' . $data['language'], 'like', '%' . $freeword . '%');
					$query->orWhere('items.content_' . $data['language'], 'like', '%' . $freeword . '%');
					$query->orWhere('stations.' . $data['language'], 'like', '%' . $freeword . '%');
					$query->orWhere('areas.' . $data['language'], 'like', '%' . $freeword . '%');
					$query->orWhere('sub_categories.' . $data['language'], 'like', '%' . $freeword . '%');
				});
			}
		}
		if(isset($conditions['details'])){
			foreach($conditions['details'] as $key => $detail){
				// Range
				if($data['item_informations'][$display_way][$key]['settings']['search_type'] == 'range'){
					$stmt->whereBetween(DB::raw("CAST(common_schema.extract_json_value(items.informations, '/" . $key . "') AS UNSIGNED)"), array($detail['min'], $detail['max']));
				}
				// Boolean
				if($data['item_informations'][$display_way][$key]['settings']['search_type'] == 'boolean'){
					$stmt->where(DB::raw("common_schema.extract_json_value(items.informations, '/" . $key . "')"), '=', $detail);
				}
			}
		}
		$stmt->groupBy('items.id')
			->orderBy('customers.validity_flag', 'DESC');
		if($orderType == 'recent'){
			$stmt->orderBy('items.created_at', 'DESC');
		}elseif($orderType == 'rating'){
			$stmt->orderBy('review_average', 'DESC');
		}else{
			$stmt->orderBy($orderType, $order);
		}
		//print($stmt->toSql());
		//exit;

		$result = $stmt->paginate($perPage)->toArray();
		$result['range'] = $this->makePaginationLinks($result);

		foreach($result['data'] as $key => $row){
			$result['data'][$key]['reviews'] = array();
			foreach($data['review_items'][$display_way] as $reviewItem){
				$result['data'][$key]['reviews'][$reviewItem['code']] = array();
				$result['data'][$key]['reviews'][$reviewItem['code']] = $this->getReviews($row['id'], $reviewItem['id']);
			}
			$result['data'][$key]['informations'] = json_decode($result['data'][$key]['informations'], true);
		}

		return $result;
	}

	/**
	 * Make pagination links.
	 *
	 * @return array
	 */
	public function makePaginationLinks($records){

		global $data;
		global $settings;

		$pgFirst = 1;
		$pgLast = $records['last_page'];
		$pgCurrent = $records['current_page'];

		$pgFrontBackNum = floor($settings['pagination_max_links'] / 2);

		$pgFrontNum = $records['current_page'] - $pgFrontBackNum;
		$pgBackNum = $records['current_page'] + $pgFrontBackNum;

		if($pgFrontNum < 1){
			$pgBackNum = $pgBackNum + (-1 * ($pgFrontNum - 1));
			$pgFrontNum = $pgFirst;
		}
		if($pgBackNum > $pgLast){
			if($pgFrontNum > 1){
				$pgFrontNum = $pgFrontNum - ($pgBackNum - $pgLast);
			}
			$pgBackNum = $pgLast;
		}
		$array = range($pgFrontNum, $pgBackNum);
		if($pgFrontNum > $pgFirst){
			if(($pgFrontNum - $pgFirst) > 1){
				array_unshift($array, '...');
			}
			array_unshift($array, $pgFirst);
		}
		if($pgBackNum < $pgLast){
			if(($pgLast - 1) > $pgBackNum){
				array_push($array, '...');
			}
			array_push($array, $pgLast);
		}
		return $array;

	}

	/**
	 * Get sub categories for rendering sub category.
	 *
	 * @return void
	 */
	public function getCategories(){

		global $data;
		global $settings;

		if(in_array('customer', $settings['list_display_ways'])){
			unset($settings['list_display_ways'][array_search('customer', $settings['list_display_ways'])]);
			$settings['list_display_ways'][] = $settings['customer_category'];
		}
		$categories = Category::whereIn('code', $settings['list_display_ways'])->get()->toArray();
		$array = array();
		foreach($categories as $key => $row){
			$row['settings'] = json_decode($row['settings'], true);
			$array[$row['code']] = $row;
		}
		$data['categories'] = $array;

	}

	/**
	 * Get target review items
	 *
	 * @return void
	 */
	public function getTargetReviewItems(){

		global $data;
		global $settings;

		$array = array();
		foreach($data['categories'] as $key => $category){
			foreach($category['settings']['review_items'] as $review_item){
				if(!in_array($review_item, $array)){
					if(!isset($array[$category['code']])){
						$array[$category['code']] = array();
					}
					$array[$category['code']][] = $review_item;
				}
			}
		}

		$settings['review_items'] = $array;
	
	}
	
	/**
	 * Get target item informations
	 *
	 * @return void
	 */
	public function getTargetItemInformations(){

		global $data;
		global $settings;

		$array = array();
		foreach($data['categories'] as $key => $category){
			foreach($category['settings']['item_informations'] as $item_information){
				if(!in_array($item_information, $array)){
					if(!isset($array[$category['code']])){
						$array[$category['code']] = array();
					}
					$array[$category['code']][] = $item_information;
				}
			}
		}

		$settings['item_informations'] = $array;
	
	}
	
	/**
	 * Get review items for rendering review items.
	 *
	 * @return void
	 */
	public function getReviewItems(){

		global $data;
		global $settings;

		// get target review items
		$this->getTargetReviewItems();

		$array = array();
		foreach($settings['review_items'] as $code => $review_items){
			$array[$code] = ReviewItem::whereIn('code', $review_items)->get()->toArray();
			$arraySecond = array();
			foreach($array[$code] as $key => $row){
				$arraySecond[$row['code']] = $row;
			}
			$array[$code] = $arraySecond;
		}

		$data['review_items'] = $array;

	}

	/**
	 * Get item informations for rendering item_informations.
	 *
	 * @return void
	 */
	public function getItemInformations(){

		global $data;
		global $settings;

		// get target item informations
		$this->getTargetItemInformations();

		$array = array();
		foreach($settings['item_informations'] as $code => $item_informations){
			$array[$code] = ItemInformation::whereIn('code', $item_informations)->get()->toArray();
			$arraySecond = array();
			foreach($array[$code] as $key => $row){
				$row['settings'] = json_decode($row['settings'], true);
				$arraySecond[$row['code']] = $row;
			}
			$array[$code] = $arraySecond;
		}

		$data['item_informations'] = $array;

	}

	/**
	 * Get reviews for rendering reviews.
	 *
	 * @return array
	 */
	public function getReviews($itemId, $reviewItemId, $customerId = NULL){

		global $data;
		global $settings;

		$reviewItems = Review::where('review_item_id', '=', $reviewItemId);
		if($itemId == NULL){
			$reviewItems->whereNull('item_id');
		}else{
			$reviewItems->where('item_id', '=', $itemId);
		}
		if($customerId != NULL){
			$reviewItems->where('customer_id', '=', $customerId);
		}
		$reviews = $reviewItems->whereNull('deleted_at')
			->get()->toArray();
		
		return $reviews;

	}

	/**
	 * Get orders for rendering list.
	 *
	 * @return void
	 */
	public function getOrders(){

		global $data;
		global $settings;

		$orderLists = array();
		foreach($data['categories'] as $key => $category){
			foreach($category['settings']['order_lists']['lists'] as $orderList){
				if(!in_array($orderList, $orderLists)){
					$orderLists[] = $orderList;
				}
			}
		}
		$data['orders'] = Order::whereIn('code', $orderLists)->orderByRaw("FIELD(code, '" . implode("','", $orderLists) . "') ASC")->get()->keyBy('code')->toArray();

	}

	/**
	 * Get informations for rendering information.
	 *
	 * @return void
	 */
	public function getInformations(){

		global $data;
		global $settings;
		
		$informations = Information::select('*', DB::raw('title_' . $data['language'] . ' AS title'), DB::raw('content_' . $data['language'] . ' AS content'))->orderBy('date', 'DESC')->get()->keyBy('id')->toArray();

		$data['informations'] = $informations;

	}

}
