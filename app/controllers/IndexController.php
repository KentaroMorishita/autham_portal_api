<?php

class IndexController extends BaseController {

	public function __construct(){
		parent::__construct();
	}

	public function index($lang){

		global $data;
		$subCategories = array();
		foreach($data['categories'] as $key => $row){
			$subCategories[$row['code']] = $this->getSubCategoryList($row['code']);
		}
		$data['sub_categories'] = $subCategories;
		$this->getPhotos();

		return Response::json($data)->setCallback(Input::get('callback'));

	}

	public function other($lang){

		global $data;

		return Response::json($data)->setCallback(Input::get('callback'));

	}

}
