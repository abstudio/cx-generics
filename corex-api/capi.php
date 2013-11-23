<?php
interface iCAPI {
	public function parse($placeholders, $text, $method='modx', $fix='+'); // Парсит текст по массиву
	public function placeholder($key, $method='modx', $fix=":"); // Создает плейсхолдер
	public function json($data); // Преобразует массив в json
	public function array_to_php_syntax($arrName, $arrContent, &$phpcontent=Array()); // Преобразует массив в PHP код
	public function unlink($dir); // Удаляет директорию и все файлы в ней!!!
	public function get_remote($remoteAddress, $request, $method='GET'); // Возвращает ответ с удаленного адреса
	public function get_remote_json($remoteAddress, $request, $method='GET'); // Возвращает json ответ с удаленного адреса
	public function scan_dir($dir, $ext='.*', $updatesfrom=0, $updatesto=999999999999999, $deep=9999); // Сканирует директорию
	public function readInfoFile($filename); // Считывает файл с данными строка/tab
	public function xrequest($text=false); // Создает кроссплатформенный запрос по алгоритму x-request
	public function xconsole($text=false); // Создает кроссплатформенный запрос по алгоритму x-console
	public function array_unshift_assoc(&$arr, $key, $val); // Добавляет ключ=значение в начало массива
	public function flat($arr, $div=':', &$result=Array(), $key=false); // Превращает многомерный массив в одномерный
	public function hasharray($arr, $raw_output=false); // Создает хэш массива
	public function makeHTMLAttributes($arr); // Конвертирует массив в аттрибуты синтаксиса HTML
	public function generateUIN($prefix='', $test=false); // Генерирует уникальный идентификатор
}

/**
* @class CAPI
*/
Class CAPI implements iCAPI {
	/**
	* @method parse
	*/
	public function parse($placeholders, $text, $method='modx', $fix='+') {
		
		
		if (empty($placeholders)) return $text;
		
		foreach($placeholders as $key=>$value) {
			
			switch(gettype($value)) {
				case 'boolean':
					$value = ($value) ? '1' : '0';
				break;
				case 'array':
					/*
					* Мы заранее проверяем есть ли в тексте не закрытая часть плейсхолдера, т.е. если ли смысл перебирать вложенный массив дальше
					*/
					if (strpos(CAPI::unclosedPlaceholder($method, $fix), $text)) {
						$text = CAPI::parse(CX::flatArray($key, $value), $text, $method, $fix);
					}
				break;
			}
			
			$text = str_replace(CAPI::placeholder($key, $method, $fix), $value, $text);			
		}
		return $text;
	}
	
	/**
	* ~private @method unclosedPlaceholder
	*/
	function unclosedPlaceholder($method='modx', $fix=":") {
		switch($method) {
				case 'prefix': # :holder
					return $fix.$key;
				break;
				case 'modx': # [:holder:]
					return "[$fix".$key;
				break;
		}
	}
	
	/**
	* @method placeholder
	*/
	public function placeholder($key, $method='modx', $fix=":") {
		switch($method) {
				case 'prefix': # :holder
					return $fix.$key;
				break;
				case 'modx': # [:holder:]
					return "[$fix".$key."$fix]";
				break;
		}
	}
	
	/**
	* @method json
	*/
	public function json($data) {
		if (!empty($data))
		foreach($data as $key=>$value) {
			switch(gettype($value)) {
				case 'boolean':
					$data[$key] =  ($value) ? '1' : '0';
				break;
				case 'integer':
				case 'double':
					$data[$key] =  (string)$value;
				break;
				break;
				case 'array':
					$data[$key] = CAPI::json($value);
				break;
			}
		}
		
		return json_encode($data);
	}
	
	/**
	* @method array_to_php_syntax
	*/
	public function array_to_php_syntax($arrName, $arrContent, &$phpcontent=Array()) {
		foreach($arrContent as $key=>$value) {
			switch(gettype($value)) {
				case 'string':
					$value = str_replace("'", "\\'", $value);
					
					$phpcontent[] = $arrName."['$key'] = '$value';";
				break;
				case 'integer':
				case 'double':
					$phpcontent[] = $arrName."['$key'] = $value;";
				break;
				case 'boolean':
					$phpcontent[] = $arrName."['$key'] = ".($value ? 'true' : 'false').";";
				break;
				case 'array':
					CAPI::array_to_php_syntax($arrName."['$key']", $value, $phpcontent);
				break;
			}
		}
		
		return join("\n", $phpcontent);
	}
	
	/**
	* @method unlink
	*/
	public function unlink($dir, &$log=Array(), $save_mode=true) {
		if ( ((string)$dir=='/'||(string)$dir=='') && $save_mode)
		{ 
			$log[] = 'CAPI::unlink-save_mode-message:"Операция прервана. Удаления корня невозможно в безопасном режиме."';
			return false;
		}
		if (!is_dir($dir)) return true;
		
		$handle = opendir($dir);
		if (!$handle)
		{
			$log[] = 'CAPI::unlink-message:"Операция прервана. Не возможно открыть директорию '.$dir.'"';
			return false;
		}
		$dr = scandir($dir);
		$result = Array();
		foreach($dr as $d) {
			
			if ($d=='..') continue;
			if ($d=='.') continue;
			$subfile = $dir.'/'.$d;
			
			if (is_dir($subfile)) {
				
				CXfile::unlink($subfile);
				
			} else {
				
				if (!unlink($subfile))
				{
					$log[] = 'CAPI::unlink-message:"Операция прервана. Нету доступа к файлу '.$subfile.'"';
					return false;
				}
			}
		}
		@closedir($dir);
		if (!@rmdir($dir))
		{
			$log[] = 'CAPI::unlink-message:"Операция прервана. Нету доступа к директории '.$dir.'"';
			return false;
		}
		return cxmain::answer(true);
	}
	
	/**
	* @method get_remote
	*/
	public function get_remote($remoteAddress, $request, $method='GET') {
		switch($method) {
			case 'GET':
				$get = Array();
				foreach($request as $key=>$value) {
					$get[] = "$key=$value";
				}
				$get = join("&", $get);
				$fh = @file_get_contents($remoteAddress.'?'.$get);
				if (!$fh) return false;
				else return $fh;
			break;
			case 'DEBUG':
				$get = Array();
				foreach($request as $key=>$value) {
					$get[] = "$key=$value";
				}
				$get = join("&", $get);
				die($remoteAddress.'?'.$get);
			break;
			case 'GET_REQUEST':
			$get = Array();
				foreach($request as $key=>$value) {
					$get[] = "$key=$value";
				}
				$get = join("&", $get);
				return($remoteAddress.'?'.$get);
			break;
		}
	}
	
	/**
	* @method get_remote_json
	*/
	public function get_remote_json($remoteAddress, $request, $method='GET', &$log=Array()) {
		$fh = CAPI::remote($remoteAddress, $request, $method);
		if ($method=='SHOW_REQUEST') $logp[] = $fh;
		if (!$fh) 
		{
			$log[] = 'Коннектор содержит ошибки:'.CXfile::remote($remoteAddress, $request, 'GET_REQUEST');
			return false;
		}
		if (!$res = json_decode($fh, true) or empty($res)) 
		{
			$log[] = 'Ответ с сервер не содержит json-синтаксиса:'.CXfile::remote($remoteAddress, $request, 'GET_REQUEST');
			return false;
		}
		
		return $res;
	}
	
	/**
	* @method scan_dir
	*/
	public function scan_dir($dir, $ext='.*', $updatesfrom=0, $updatesto=999999999999999, $deep=9999) {
		
		$result['files'] = Array();
		$result['folders'] = Array();
		
		
		$dr = scandir($dir);
		$result = Array();
		foreach($dr as $d) {
			
			if ($d=='..') continue;
			if ($d=='.') continue;
			$subfile = $dir.'/'.$d;
			/*
			* Проверяем на соответствие регулярному выражению
			*/
			
			if (is_dir($subfile)) {
				$result['folders'][$d] = Array();
				$result['folders'][$d]['updatedon'] = filemtime($subfile);
				if ($deep>0) {
					
					$result['folders'][$d]['childs'] = CAPI::scan_dir($subfile, $ext, $updatesfrom, $updatesto, ($deep-1));
					if (empty($result['folders'][$d]['childs']['files']) && empty($result['folders'][$d]['childs']['folders'])) unset($result['folders'][$d]);
				}
				
				
			} else {
				if (!preg_match('/'.$ext.'/', $d)) continue;
				$upd = filemtime($subfile); 
				
				if ($upd>=$updatesfrom && $upd<=$updatesto) {
					$result['files'][$d] = Array();
					$result['files'][$d]['updatedon'] = filemtime($subfile);
				}
			}
		}
		return $result;
	}
	
	/**
	* @method readInfoFile
	*/
	public function readInfoFile($filename) {
		$lines = file($filename);
		$result = Array();
		foreach($lines as $line) {
			$l2 = preg_split("/[\t]{1}/", $line);
			$result[$l2[0]] = $l2[1];
		}
		return $result;
	}
	
	/**
	* @method xrequest
	*/
	public function xrequest($text=false) {
		return new XRequest($text);
	}
	
	/**
	* @method xrequest
	*/
	public function xconsole($text=false) {
		return new xConsole($text);
	}
	
	/**
	* @method array_unshift_assoc
	*/
	public function array_unshift_assoc(&$arr, $key, $val) 	{ 
		$arr = array_reverse($arr, true); 
		$arr[$key] = $val; 
		array_reverse($arr, true); 
	} 
	
	/**
	* @method flat([array arr, [string div, [link|array result, [string key]]]])
	* @descript превращает многомерный массив в одновременый
	* @param arr - массив
	* @param div - разделитель между ключами
	*/
	public function flat($arr, $div=':', &$result=Array(), $key=false) {
		foreach($arr as $k=>$v)
		{
			if ($key) $newkey = $key.$div.$k;
			else $newkey = $k;
			if (is_array($v))
			{
				CAPI::flat($v, $div, $result, $newkey);
			}
			else 
			{
				$result[$newkey] = $v;
			}
		}
		return $result;
	}
	
	/**
	* @method arrayhash
	*/
	public function hasharray($arr, $raw_output=false) {
		return md5(join('', CAPI::flat($arr)), $raw_output);
	}
	
	/**
	* @method makeHTMLAttributes
	*/
	public function makeHTMLAttributes($arr) {
		$result = Array();
		foreach($arr as $k=>$v)
			$result[] = "$k=\"$v\"";
		return join(" ", $result);
	}
	
	/**
	* @method generateUIN
	*/
	public function generateUIN($prefix='', $test=Array()) {
		do {
			$uin = md5(rand(1,pow(9,10)));
		} while (in_array($uin, $test));
		return $uin;
	}
}

/***
* @class xconsole
* @descript формат передачи данных в стиле DOS запросов
* @copyrights Разработано Vladimir Kalmykov (mrls.pro@gmail.com)
*/
Class xConsole {
	 public $command, $params = Array();
	/**
	* @method parse
	*/
	public function __construct($command) {
		$this->parse($command);
	}
	
	private function invars($remain, $key, &$mathes, &$params) {
		$key++;
		
		
		if (!isset($mathes[$key])) {
			$params_for_before = $remain;
		} else {
			
			$parts = explode($mathes[$key], $remain);
			$params_for_before = array_shift($parts);
			$parts = join('', $parts);
			$params[substr($mathes[$key], 1)] = $this->invars(trim($parts), $key, $mathes, $params);
		}
		
		if (empty($params_for_before)) return Array();
		preg_match_all('/("[^"].*")/', $params_for_before, $m);
		if (!empty($m[1])) {
			
			foreach($m[1] as $quotes_string) {
				$unspaced_string = str_replace(" ", "--ci-space--", $quotes_string);
				
				$params_for_before = str_replace($quotes_string, substr($unspaced_string, 1, -1), $params_for_before);
			}
		}
		
		$vars = explode(' ', $params_for_before);
		
		
		$result = Array();
		foreach($vars as $k=>$v) {
			if (empty($v)) continue;
			if (!empty($m[1])) $vars[$k] = str_replace("--ci-space--", " ", $v);
			$result[] = $vars[$k];
		}
		
		if (count($result)<2) $result = $result[0];
		
		return $result;
		
	}
	
	/**
	* @method parse
	*/
	private function parse($command) {
		
		$cmdl = explode(" ", $command);
		$this->command = array_shift($cmdl);
		$params = join(" ", $cmdl);
		preg_match_all("/(\-[a-zA-Z0-9]*)/", $params, $mathes);
		if (!empty($mathes[1])) {
			$this->invars($params, -1, $mathes[1], $this->params);
		}
		$this->params = array_reverse($this->params);
		
	}
	
	/**
	* @method toString
	*/
	public function toString($command, $params=Array()) {
		$result = Array();
		$result[] = $command;
		
		if (!empty($params)) foreach($params as $key=>$value) {
			$result[] = '-'.$key;
			if (!empty($value)) {
				if (!is_array($value)) $value = Array($value);
				foreach($value as $key=>$val) {
					if (strpos($val, ' ')) $value[$key] = '"'.$val.'"';
				}
				$result[] = join(' ', $value);
			}
		}
		
		return join(' ', $result);
	}
}

/***
* @class XRequest
* @descript формат передачи данных, а отличии от json имеет более простой формат, что пользоваляет его использовать, например, в аттрибутах тэга
* @copyrights Разработано Vladimir Kalmykov (mrls.pro@gmail.com)
*/
Class XRequest {
	
	var $section = false,
	$path = Array(),
	$vars = Array(),
	$attrs = Array();
	
	public function __construct($sometext=false) {
		if ($sometext) $this->import($sometext);
	}
	
	public function import($sometext) {
		
		$fsection = explode('>', $sometext);
		
		$parts = explode('|', $sometext);
		$this->path = explode('.', array_shift($parts));
		
		// @do search for section
		if (strpos($this->path[0], '>')) {
			$sc = explode('>', $this->path[0]);
			$this->section = array_shift($sc);
			$this->path[0] = join('>', $sc);
		}
		// @do search for vars
		if (strpos($this->path[count($this->path)-1], ':')) {
			$fv = explode(':', $this->path[count($this->path)-1]);
			$this->path[count($this->path)-1] = array_shift($fv);
			$this->vars = $fv;
		}
		
		for ($i = 0;$i<count($parts);$i++) {
			$kv = explode('=', $parts[$i]);
			$this->attrs[$kv[0]]=$kv[1];
		}
	}
	
	public function export() {
		$string = '';
		
		// @do build section
		if ($this->section) {
			$string = $this->section.'>';
		}
		// @do build path
		$string .= join(".", $this->path);
		// @do build vars
		if (!empty($this->vars)) {
			$string .= ':'.join(":", $this->vars);
		}
		// @do build attrs
		$attrs = Array();
		foreach ($this->attrs as $key=>$attr) {
			$attrs[] = $key.'='.$attr;
		}
		if (!empty($attrs)) {
			$string .= '|'.join('|', $attrs);
		}
		return $string;
	}
	
	public function getPathString() {
		$string = '';
		
		// @do build section
		if ($this->section) {
			$string = $this->section.'>';
		}
		// @do build path
		$string .= join(".", $this->path);
		// @do build vars
		if (!empty($this->vars)) {
			$string .= ':'.join(":", $this->vars);
		}
		return $string;
	}
	
	public function addattr($key, $value) {
		$this->attrs[$key] = $value;
	}
	
	public function removeattr($key) {
		if (isset($this->attrs[$key])) unset($this->attrs[$key]);
	}
}
?>