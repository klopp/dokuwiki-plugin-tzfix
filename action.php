<?php

if( !defined( 'DOKU_INC' ) ) die();

class action_plugin_tzfix extends DokuWiki_Action_Plugin
{
    public function register(Doku_Event_Handler &$controller)
    {
        $controller->register_hook( 'DOKUWIKI_STARTED', 'BEFORE', $this, 
                'tz_fix' );
    }
/*
    private function plog($s)
    {
        $f = @fopen( '/hsphere/local/home/a957968/xxx.ato.su/log/tzfix.log', 'a' );
//        $f = @fopen( '/home/localhost/snippets.ato.su/log/tzfix.log', 'a' );
        if( $f )
        {
            fwrite( $f, "$s\n" );
            fclose( $f );
        }
    }
*/
    function tz_fix( $event, $data )
    {
      global $INPUT;
      $ip = $INPUT->server->str('REMOTE_ADDR');
      if( !$ip ) return;
      if( intval($ip) == 127 || intval($ip) == 192 ) return; 
      //{
      //  $ip = '213.180.204.213';
      //}

      $key = preg_replace( '/\.\\d+$/', '', $ip );
      if( !$key ) return;
      $meta = p_get_metadata( 'plugin_tzfix', $key );

      if( $meta )
      {
        $meta = explode( ',', $meta );
        $tdiff = time()-$meta[1];
        $ttl = $this->getConf('tzfix_ttl');
        if( $tdiff > (60*60*24* ($ttl ? $ttl : 30)) )
        {
          //$this->plog( "TZ for $key expired!" );
          $meta = false;
        }
      }
      if( !$meta )
      {
        $json = @json_decode
        (
          @file_get_contents( "http://api.sypexgeo.net/json/$ip" )
        );
        $this->plog( print_r( $json, 1 ) );
        if( !$json ) return;

        $meta = $json->region->timezone;
        //if( $meta == 'Europe/Moscow' ) $meta = 'Europe/Kaliningrad';
        $meta = array( $meta, time() );
        //$this->plog( "new TZ for $key:\n".print_r( $meta, 1 ) );
        p_set_metadata( 'plugin_tzfix', array( $key => $meta[0].','.$meta[1] ) );
      }
      @date_default_timezone_set( $meta[0] );
    }
}


