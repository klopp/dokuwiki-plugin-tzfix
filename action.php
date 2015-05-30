<?php
if( !defined( 'DOKU_INC' ) ) die();

class action_plugin_tzfix extends DokuWiki_Action_Plugin
{

    public function register(Doku_Event_Handler &$controller)
    {
        $controller->register_hook( 'DOKUWIKI_STARTED', 'BEFORE', $this, 
                'tz_fix' );
    }

    function tz_fix($event, $data)
    {
        global $INPUT;
        $ip = $INPUT->server->str( 'REMOTE_ADDR' );
        if( !$ip ) return;
        if( intval( $ip ) == 127 || intval( $ip ) == 192 ) return;
        
        $key = preg_replace( '/\.\\d+$/', '', $ip );
        if( !$key ) return;
        $meta = p_get_metadata( 'plugin_tzfix', $key );
        
        if( $meta )
        {
            $meta = explode( ',', $meta );
            $tdiff = time() - $meta[1];
            $ttl = $this->getConf( 'tzfix_ttl' );
            if( $tdiff > (60 * 60 * 24 * ($ttl ? $ttl : 30)) ) $meta = false;
        }
        if( !$meta )
        {
            $json = @json_decode( 
                    @file_get_contents( "http://api.sypexgeo.net/json/$ip" ) );
            if( !$json ) return;
            
            $meta = $json->region->timezone;
             
            // IX-hosting temporary fix:
            // if( $meta == 'Europe/Moscow') $meta = 'Europe/Kaliningrad';
            
            $meta = array($meta,time() 
            );
            p_set_metadata( 'plugin_tzfix', 
                    array($key => $meta[0] . ',' . $meta[1] 
                    ) );
        }
        @date_default_timezone_set( $meta[0] );
    }
}


