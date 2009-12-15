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
    protected $connection_buffers = array(); 

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
         echo "LIBEVENT: $object #{$object_id} -> $message\n"; 
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
            
    function onEventAccept($socket, $rEvent, $args)
    {          
        $socket_num = $args [0];
         
        $conn = $this->acceptSocket($socket_num);         
        $conn_num = $this->addConnection($conn);        
        $this->addConnectionBuffer($conn_num); 
    }
     
    function onEventRead($socket, $args)
    {          
        $conn_num = $args [0]; 
                  
//        if ($this->connections_states[$conn_num] != self::STATE_READ) { 
//            self::log('Connection', $conn_num, 'client tries to write in read connection'); 
//            $this->closeConnection ($conn_num); 
//            return;
//        } 
//        
//        if(!$tmp = event_buffer_read($this->connection_buffers[$conn_num], $this->buffer_len)) {
//            return;                                
//        }
        
        //self::log('Connection', $conn_num, 'readed '.strlen($tmp).' chars');        
        self::log('Connection', $conn_num, 'request callback');
        
        $callback = $this->request_callback; 
        $callback($socket);
             
        //$this->connections_states[$conn_num] = self::STATE_WRITE;         
        
        //self::log('Connection', $conn_num, 'add write buffer');
        //event_buffer_write($this->connection_buffers [$conn_num], $response);
    } 
           
    function onEventWrite($socket, $args)
    {          
        $conn_num = $args [0]; 
        self::log('Connection', $conn_num, 'write');  
        if (self::STATE_WRITE == $this->connections_states [$conn_num]) { 
            $this->closeConnection($conn_num);             
        }      
    } 
      
    function onEventError($socket, $error_mask, $args)
    { 
        $conn_num = $args [0]; 
        
        if ($error_mask & EVBUFFER_EOF) 
            $msg = "EOF";
        if ($error_mask & EVBUFFER_ERROR)
            $msg = "unknown error"; 
        if ($error_mask & EVBUFFER_TIMEOUT) 
            $msg = "timeout";

        if ($error_mask & self::EV_BUFFER_READ)
            $state = 'READ';
        elseif ($error_mask & EVBUFFER_WRITE)
            $state = 'WRITE';

        self::log('Connection #'.$conn_num.' -> '.$msg.' on '.$state);
         
        $this->closeConnection($conn_num);      
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
        $connection = stream_socket_accept ($socket, 0);  
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
    
    protected function addConnectionBuffer($conn_num)
    { 
        $buffer = event_buffer_new($this->connections[$conn_num],
                                   array($this, 'onEventRead'),
                                   array($this, 'onEventWrite'),
                                   array($this, 'onEventError'),
                                   array ($conn_num)); 
         
        event_buffer_base_set($buffer, $this->event_base);   
        event_buffer_timeout_set($buffer, $this->timeout, $this->timeout);   
        event_buffer_enable($buffer, EV_READ | EV_WRITE | EV_PERSIST);         
         
        $this->connection_buffers[$conn_num] = $buffer;
    }
    
    protected function closeConnection($conn_num)
    {            
        $this->freeBuffer($conn_num);      
        fclose($this->connections[$conn_num]);
        self::log('Connection', $conn_num, 'closed');  
         
        unset($this->connections[$conn_num]); 
        unset($this->connections_states[$conn_num]); 
    }

    protected function freeBuffer($conn_num)
    {
        event_buffer_disable($this->connection_buffers[$conn_num], EV_READ | EV_WRITE); 
        event_buffer_free($this->connection_buffers[$conn_num]); 
        unset($this->connection_buffers[$conn_num]);
        self::log('Connection', $num, 'buffer is free. Fly, bird, fly!');
    }
} 