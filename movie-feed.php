<?php
ini_set('max_execution_time', 60);
ob_implicit_flush(true);
///TEST KEY 470fd2ec8853e25d2f8d86f685d2270e
define("API_KEP","7fadea113179be0e80ad6358c80db32b",true); 
define("DIR","movie_feed",true); 
define("FILE_NAME",'movie_'.date('Ydm').'.json',true); 

class GetMovieData
{
	public $json = '';
	public $xml = '';
	public $date;
	public $decodeJson = array();
	
	function GetMovieData(){
		$this->xml = $this->run();
		$entry = $this->xml->channel;
		
		$namespaces = $entry->getNameSpaces(true);			
		$dc = $entry->children($namespaces['dc']); 
		$this->date = date("Y-m-d", strtotime($dc->date));
				
		$count =count($entry->item);
		for($i=0; $i<$count; $i++){
			$this->setMovietime($i);
						
			if(strpos($entry->item[$i]->link,'imdb.com')){
				
				$obj = $this->getMovieobj($i);
				
				$this->decodeJson['title']    = $this->cleanStr($entry->item[$i]->title);	
				$this->decodeJson['year']     = (!empty($obj['imdb']['Year']))    ? $this->cleanStr($obj['imdb']['Year']) : "";	
				$this->decodeJson['imdbID']   = (!empty($obj['imdb']['imdbID']))  ? $this->cleanStr($obj['imdb']['imdbID']) : "";	
				$this->decodeJson['rated']    = (!empty($obj['imdb']['Rated']))   ? $this->cleanStr($obj['imdb']['Rated']) : "";
				$this->decodeJson['genre']    = (!empty($obj['imdb']['Genre']))   ? $this->cleanStr($obj['imdb']['Genre']) : "";
				$this->decodeJson['actors']   = (!empty($obj['imdb']['Actors']))  ? $this->cleanStr($obj['imdb']['Actors']) : "";
				$this->decodeJson['director'] = (!empty($obj['imdb']['Director']))? $this->cleanStr($obj['imdb']['Director']) : "";
				$this->decodeJson['tagline']  = (isset($obj['tagline']))? $this->cleanStr($obj['tagline']) : NULL;
				$this->decodeJson['vote']     = (isset($obj['vote_average']))       ? $obj['vote_average'] : NULL;
				$this->decodeJson['overview'] = (isset($obj['overview']))           ? $this->cleanStr($obj['overview']) : NULL;
				$this->decodeJson['trailers'] = (isset($obj['trailers']['youtube']))? $obj['trailers']['youtube'] : NULL;
				$this->decodeJson['posters']  = (isset($obj['images']['posters']))  ? $obj['images']['posters']     : NULL;
				$this->decodeJson['backdrops']= (isset($obj['images']['backdrops']))? $obj['images']['backdrops'] : NULL;
				$this->decodeJson['backdrops'][]['file_path']= (isset($obj['backdrop_path']))? $obj['backdrop_path'] : NULL;
				$this->decodeJson['posters'][]['file_path']  = (isset($obj['poster_path'])  )? $obj['poster_path'] : NULL;
				$this->json .= json_encode($this->decodeJson).',';				
			}
		}	
		
		$this->json = $this->json;
		echo $this->json = $this->fixJson(str_replace(",ENDZ","",$this->json."ENDZ"));
				
		$oldFileName = 'movie_'.date('Ydm',strtotime('-1 day')).'.json';
		if(file_exists(DIR.'/'.$oldFileName)){
			unlink(DIR.'/'.$oldFileName);
		}
		
		if(!is_dir(DIR)){
			mkdir(DIR,0700);
		}
		$fo = fopen(DIR.'/'.FILE_NAME,'w');
		fwrite($fo,$this->json);
	}
	
	
	function setMovietime($i){
		$time = $this->cleanStr($this->xml->channel->item[$i]->description);
		$time = str_replace('Showing at:','',$time);
		$time = str_replace('(','',$time);		
		$time = str_replace(')','',$time);	
		$time = str_replace('&','||',$time);			
		$time = strstr($time,'Rating',true);
		$time = explode(',',$time);
		
		for($x=0; $x<count($time); $x++){
			if(strpos($time[$x],'Carib 5')){
				$carib = trim(str_replace('Carib 5','',$time[$x]));
			}
			if(strpos($time[$x],'Palace Cineplex')){
				$cineplex = trim(str_replace('Palace Cineplex','',$time[$x]));
			}
			if(strpos($time[$x],'Palace Multiplex')){
				$multiplex = trim(str_replace('Palace Multiplex','',$time[$x]));
			}			
		}
			$this->decodeJson['Carib'] 		= (!empty($carib))		? $carib 		: "" ;
			$this->decodeJson['Multiplex'] 	= (!empty($multiplex))	? $multiplex 	: "" ;
			$this->decodeJson['Cineplex']	= (!empty($cineplex))	? $cineplex 	: "" ;
	}	
	
	function getMovieobj($i){
		$link  = explode('/',str_replace('http://', '', $this->cleanStr($this->xml->channel->item[$i]->link)));
		$id = $link[2];		
		$file = file("http://api.themoviedb.org/3/movie/{$id}?api_key=".API_KEP."&language=en&append_to_response=trailers,images&include_image_language=en,null");		
		$imdb = file("http://www.imdbapi.com/?i=".$id);
		
		if($file){			
			$obj = json_decode($file[0],true);
			$obj['imdb'] = json_decode($imdb[0],true);
			return $obj; 
		}
		
		return null;
	}
	
	function run(){
		$file = "https://www.palaceamusement.com/generaterss.php?channel=schedule";
		$xml=simplexml_load_file($file) or die("Error: Cannot create object");
		exit($xml);
		return $xml;
	}
	
	function cleanStr($str){
		$str = trim($str);
		$str = str_replace('"',"'",$str);
		//$str = stripcslashes($str);
		$str = strip_tags($str);
		return $str;
	}
	
	function fixJson($str){
		$str = stripcslashes($str);
		return '{"date":"'.$this->date.'","list":['.$str.']}';		
	}
}


if(file_exists(DIR.'/'.FILE_NAME))
{
	$file = file(DIR.'/'.FILE_NAME);
	echo $file[0];
	exit();
}

$mObj = new GetMovieData();
?>