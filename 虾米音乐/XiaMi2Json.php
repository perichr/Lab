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
			return XiaMi2Json::ById( $id, $callback );
		}
	}
	public static function ById( $id, $callback = null ){
		
		$json = XiaMi2Json::getUrlContent( "http://www.xiami.com/app/iphone/song/id/$id" );
		if($json){
			$result = json_decode( $json, true );
			$lyric_url = $result["lyric"];
			if($lyric_url){
				$result['lyric_json'] = XiaMi2Json::getLyric($lyric_url);
			}
		}
		$json = json_encode( $result );
		return is_null( $callback ) ? $json : "$callback($json)";
	}
}
?>