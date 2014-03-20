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
	private static function getText( $key, $content ){
		$count = preg_match_all( "/<$key>(.*?)<\/$key>/s", $content, $matchs );
		if( $count ){
			return $matchs[1][0];
		}
		return "";
	}
	private static function tryGetParam( $key, &$value ){
		if( isset( $_GET[$key] ) && $_GET[$key] ){
			$value = $_GET[$key];
			return true;
		}
		return false;
	}
	public static function Auto ()
	{
		if( XiaMi2Json::tryGetParam( 'id', $id ) && XiaMi2Json::tryGetParam( 'callback', $callback )){
			return XiaMi2Json::ById( $id, $callback );
		}
	}
	public static function ById( $id, $callback = null ){
		$json = '{"error":"Not a valid song id!"}';
		$xml = XiaMi2Json::getUrlContent( "http://www.xiami.com/song/playlist/id/$id" );
		if( strlen( $xml ) != 0 && strpos( $xml, '<song_id>') != false ){
			$info = array( );
			$info['song_id'] = XiaMi2Json::getText( 'song_id', $xml );
			$info['song_name'] = XiaMi2Json::getText( 'title', $xml );
			$info['album_id'] = XiaMi2Json::getText( 'album_id', $xml );
			$info['album_name'] = XiaMi2Json::getText( 'album_name', $xml );
			$info['album_pic'] = XiaMi2Json::getText( 'pic', $xml );
			$info['artist_id'] = XiaMi2Json::getText( 'artist_id', $xml );
			$info['artist_name'] = XiaMi2Json::getText( 'artist', $xml );
			$lyric = XiaMi2Json::getText( 'lyric', $xml );
			$info['lyric_url'] = $lyric;
			$location = XiaMi2Json::getText( 'location', $xml );
			
			$num = substr( $location, 0, 1 );
			$inp = substr( $location, 1 );
			$iLe = strlen( $inp ) % $num;
			$quo = ( strlen( $inp ) - $iLe ) / $num;
			
			$a = 0;
			$ret = '';
			$arr = array();
			for ( $i = 0; $i < $num; $i ++ ) {
				$arr[$i] = ( $iLe > $i ? 1 : 0 ) + $quo;
			}
			for ( $i = 0; $i < $arr[1] ; $i ++) {
				$a = 0;
				for ( $j = 0; $j < $num; $j ++) {
					$ret .= substr( $inp, $a + $i, 1 );
					$a += $arr[$j];
				}
			}
			
			$location = rawurldecode( $ret );
			$location = str_replace( '^', '0', $location );
			$location = str_replace( '+', ' ', $location );
			$location = preg_replace( '/00-0-nul(.*)/', '00-0-null', $location );
			$info['location'] = $location;
			
			if( strlen( $lyric ) != 0 ){
				$lrc = array();
				$lyric = XiaMi2Json::getUrlContent( $lyric );
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
				$info['lyric'] = $lrc;
			}
			$json = json_encode( $info );
		}
		return is_null( $callback ) ? $json : "$callback($json)";
	}
}
?>