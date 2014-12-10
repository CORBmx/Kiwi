<?php

class conektaWC_mail_event extends WC_Email{
    
    function __construct(){
        
        wp_schedule_event( time(), $recurrence, $hook, $args);
        
    }
    
}