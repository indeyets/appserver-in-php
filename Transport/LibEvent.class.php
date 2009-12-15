<?php 
namespace MFS\AppServer\Transport;

define ( 'EVBUFFER_WRITE', 0x02 ); 
define ( 'EVBUFFER_EOF', 0x10 ); 
define ( 'EVBUFFER_ERROR', 0x20 ); 
define ( 'EVBUFFER_TIMEOUT', 0x40 ); 

class LibEvent {

    const EV_BUFFER_READ = 0x01;
    
    const STATE_READ     = 0x01;
    const STATE_WRITE    = 0x02;
    const STATE_COMPLITE = 0x04;
     
    protected $event_base; 
     
    protected $sockets_count      = 0; 
    protected $sockets            = array();  
    protected $socket_events      = array(); 
      
    protected $connections_count  = 0;  
    protected $connections        = array();  
    protected $connection_events  = array();     
    protected $connection_states  = array(); 

    public $buffer_len = 512;
    public $timeout = 5;       
    
    protected $request_callback;
     
    function __construct($addrs)
    {    	
    	if(!is_array($addrs))
			$addrs = array($addrs);
		
		$this->addrs = $addrs;					  
    } 
    
    function loop($request_callback)
    {        
    	$this->request_callback = $request_callback;

    	if (!$this->event_base = event_base_new())
        	throw new Exception("Can't create event base");
    	
    	foreach($this->addrs as $addr) {
			$this->addAddr($addr);      
    	}    
        
        event_base_loop($this->event_base);             
    }
    
    static function log($object, $object_id, $message)
    { 
         echo "$object #{$object_id} -> $message\n"; 
    } 
          
    protected function addAddr($addr)
    { 
        $socket_num = $this->addSocket($addr);                     
        $this->addSocketEvent($socket_num);
    } 
 
    protected function addSocketEvent($socket_num)
    {         
        $socket = $this->sockets[$socket_num];
        
        $event = event_new();          
        if (!event_set($event, $socket, EV_READ | EV_PERSIST, array($this, 'onEventAccept'), array($socket_num))) { 
            throw new \Exception("Can't set event"); 
        }        
        
        if (false === event_base_set ($event, $this->event_base)) 
            throw new \Exception("Can't set [{$socket_num}] event base.");
        if (false === event_add ($event)) { 
            throw new \Exception("Can't add event"); 
        }
        $this->socket_events[$socket_num] = $event;    
        self::log('Socket', $socket_num, 'event added');       
    } 
            
    function onEventAccept($socket, $event, $args)
    {          
        $socket_num = $args[0];         
        $conn = $this->acceptSocket($socket_num);         
        $callback = $this->request_callback; 
        $callback($conn); 
    }
         
    protected function addSocket($addr)
    {
        $socket = stream_socket_server($addr, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
        $socket_num = $this->sockets_count++;        
        $this->sockets[$socket_num] = $socket;
        self::log('Socket', $socket_num, 'created');
        return $socket_num;
    }
    
    protected function acceptSocket($socket_num) 
    {
        $socket = $this->sockets[$socket_num];
        $connection = stream_socket_accept($socket, 0);  
        stream_set_blocking($socket, 0);
        self::log('Socket', $socket_num, 'accepted');
        return $connection;
    }
    
    protected function addConnection($connection)
    {
        $num = $this->connections_count++;        
        $this->connections[$num] = $connection;
        $this->connections_states[$num] = self::STATE_READ;
        self::log('Connection', $num, 'created');        
        return $num;
    }
        
    protected function closeConnection($conn_num)
    {            
        $this->freeBuffer($conn_num);      
        fclose($this->connections[$conn_num]);
        self::log('Connection', $conn_num, 'closed');  
         
        unset($this->connections[$conn_num]); 
        unset($this->connections_states[$conn_num]); 
    }    
} 