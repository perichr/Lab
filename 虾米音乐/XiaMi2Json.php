<?php
class XiaMi2Json
{
	private static function getUrlContent( $url ){
		$curl = curl_init( $url );
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$res=curl_exec($curl);
		curl_close($curl);
		return $res;
	}
	private static function tryGetParam( $key, &$value ){
		if( isset( $_GET[$key] ) && $_GET[$key] ){
			$value = $_GET[$key];
			return true;
		}
		return false;
	}
	private static function getLyric ( $lyric_url ) {
		$lrc = array();
		$lyric = XiaMi2Json::getUrlContent( $lyric_url );
		$preg = '/^(.*?)$/m';
		$count = preg_match_all( $preg, $lyric, $match );
		foreach( $match[1] as $line ) {
			$preg = '/\[(\d{2}:.*?)\]/s';
			preg_match_all( $preg, $line, $m ); 
			$text = implode( '', $m[0] );
			$text = str_replace( $text, '' ,$line );
			foreach( $m[1] as $t ) {
				$i = explode( ':', $t );
				$time = $i[0] * 60 + $i[1];
				$lrc[] = array( $time, $text );	
			}
		}
		sort( $lrc );
		return $lrc;
	}
	public static function Auto ()
	{
		if( XiaMi2Json::tryGetParam( 'id', $id ) && XiaMi2Json::tryGetParam( 'callback', $callback )){
			XiaMi2Json::tryGetParam( 'type', $type );
			if($type){
				$type = intval($type, 10);
			}
			return XiaMi2Json::ById( $id, $callback, $type );
		}
	}
	public static function ById( $id, $callback, $type = null ){
		$json = XiaMi2Json::getUrlContent( "http://www.xiami.com/app/iphone/song/id/$id" );
		if($json){
			if(!$type){
				$type = 1;
			}
			$result = json_decode( $json, true );
			$song = array();
			if(1&$type){ //url
				$song['url'] = $result['location'];
			}
			if(2&$type){ //title
				$song['title'] = $result['name'];
			}
			if(4&$type){ //artist
				$song['artist'] = $result['artist_name'];
			}
			if(8&$type){ //pic
				$pic = $result['album_logo'];
				if($pic){
					$pics = array();
					$pics['original'] = substr( $pic, 0, -6) . '.jpg';
					$pics['100'] = substr( $pic, 0, -5) . '1.jpg';
					$pics['small'] = substr( $pic, 0, -5) . '2.jpg';
					$pics['55'] = substr( $pic, 0, -5) . '3.jpg';
					$pics['medium'] = substr( $pic, 0, -5) . '4.jpg';
					$pics['185'] = substr( $pic, 0, -5) . '5.jpg';
					$song['pic'] = $pics;
				}
			}
			if(16&$type){ //lyric_url
				$song['lyric_url'] = $result['lyric'];
			}
			if(32&$type){ //lyric
				$lyric_url = $result["lyric"];
				if($lyric_url){
					$song['lyric'] = XiaMi2Json::getLyric($lyric_url);
				}
			}
		}
		$json = json_encode( $song );
		return is_null( $callback ) ? $json : "$callback($json)";
	}
}
?>