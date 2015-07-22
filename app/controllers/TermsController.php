<?php

class TermsController extends BaseController {

	public function __construct(){
		parent::__construct();
	}

	public function index($lang){

		global $data;

		return Response::json($data)->setCallback(Input::get('callback'));

	}

}
