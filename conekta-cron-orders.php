<?php

class conektaWC_update_order extends{
    
    function __construct(){
        
        wp_schedule_event( time(), $recurrence, $hook, $args);
        
    }
    
}