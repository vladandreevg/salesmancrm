<?php
	function onpbx_get_secret_key($domain, $apikey, $new=false){
		$data = array('auth_key'=>$apikey);
		if ($new){$data['new'] ='true';}
		
		$ch = curl_init('https://api.onlinepbx.ru/'.$domain.'/auth.json');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		$res = json_decode(curl_exec($ch), true);
		if ($res){return $res;}else{return false;}
	}

	function onpbx_api_query($secret_key, $key_id, $url, $post=array(), $opt=array()){
		$method = 'POST';
		$date = @date('r');
		
		if (is_array($post)){
			foreach ($post as $key => $val){
				if (is_string($key) && preg_match('/^@(.+)/', $val, $m)){
					$post[$key] = array('name'=>basename($m[1]), 'data'=>base64_encode(file_get_contents($m[1])));
				}
			}
		}
		$post = http_build_query($post);
		$content_type = 'application/x-www-form-urlencoded';
		$content_md5 = hash('md5', $post);
		$signature = base64_encode(hash_hmac('sha1', $method."\n".$content_md5."\n".$content_type."\n".$date."\n".$url."\n", $secret_key, false));
		$headers = array('Date: '.$date, 'Accept: application/json', 'Content-Type: '.$content_type, 'x-pbx-authentication: '.$key_id.':'.$signature, 'Content-MD5: '.$content_md5);
		
		$ch = curl_init('https://'.$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		$res = json_decode(curl_exec($ch), true);
		if ($res){return $res;}else{return false;}
	}
?>
