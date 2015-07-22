<?php

class PrivacyController extends BaseController {

	public function __construct(){
		parent::__construct();
	}

	public function index($lang){

		global $data;

		$pageTitle = $data['texts']['privacy_policy'];
		$data['title'] = $pageTitle . " | " . $data['title'];
		$data['keywords'] = $pageTitle . "," . $data['keywords'];
		$data['description'] = $pageTitle . " | " . $data['description'];
		$data['breadcrumbs'] = array(
			array("text" => $data['texts']['home'], "url" => "/"),
			array("text" => $pageTitle)
		);

		return Response::json($data)->setCallback(Input::get('callback'));

	}

}
