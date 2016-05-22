<?php

$CONFIG = array();


function get_config($path, $default=null) {
    global $CONFIG;
    $current_config = $CONFIG;
    foreach (explode('/', $path) as $path_component) {
        if (array_key_exists($path_component, $current_config)) {
            $current_config = $current_config[$path_component];
        } else {
            return $default;
        }
    }
    return $current_config;
}


function load_config_file($filename) {
    include $filename;
    if (!empty($CONFIG)) {
        return $CONFIG;
    }
    return array();
}


foreach (glob(CONFIG_DIR . '/*.php') as $filename) {
    $CONFIG = array_merge($CONFIG, load_config_file($filename));
}
