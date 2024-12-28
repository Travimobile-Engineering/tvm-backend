<?php

namespace App\Trait;

trait HttpResponse
{
    protected function success($data, $message = null, $code = 200){
		return response()->json([
			'status' => true,
			'message' => $message,
			'data' => $data
		], $code);
	}

	protected function error($data, $message = null, $code = 500){
		return response()->json([
			'status' => false,
			'message' => $message,
			'data' => $data
		], $code);
	}

	protected function response($array = []){
		return response()->json([
			'status' => isset($array['code']) ? ($array['code'] >= 400 ? false : true) : true,
			'message' => isset($array['message']) ? $array['message'] : null,
			'data' => isset($array['data']) ? $array['data'] : null,
		], isset($array['code']) ? $array['code'] : 200);
	}
}
