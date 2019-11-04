<?php

namespace MDS\Collivery\Model;

class Cache
{
    private $cache_dir;
    private $cache;

    public function __construct($cache_dir = 'cache/mds_collivery/')
    {
        if ($cache_dir === null) {
            $cache_dir = 'cache/mds_collivery/';
        }
        $this->cache_dir = $cache_dir;
    }

    protected function create_dir($dir_array)
    {
        if (!is_array($dir_array)) {
            $dir_array = explode('/', $this->cache_dir);
        }
        array_pop($dir_array);
        $dir = implode('/', $dir_array);

        if ($dir!='' && ! is_dir($dir)) {
            $this->create_dir($dir_array);
            mkdir($dir);
        }
    }

    protected function load($name)
    {
        if (! isset($this->cache[ $name ])) {
            if (file_exists($this->cache_dir . $name) && $content = file_get_contents($this->cache_dir . $name)) {
                $this->cache[ $name ] = json_decode($content, true);
                return $this->cache[ $name ];
            } else {
                $this->create_dir($this->cache_dir);
            }
        } else {
            return $this->cache[ $name ];
        }
    }

    public function has($name)
    {
        $cache = $this->load($name);
        if (is_array($cache) && ($cache['valid'] - 30) > time()) {
            return true;
        } else {
            return false;
        }
    }

    public function get($name)
    {
        $cache = $this->load($name);
        if (is_array($cache) && $cache['valid'] > time()) {
            return $cache['value'];
        } else {
            return null;
        }
    }

    public function put($name, $value, $time = 1440)
    {
        $cache = [ 'value' => $value, 'valid' => time() + ($time*60) ];
        if (file_put_contents($this->cache_dir . $name, json_encode($cache))) {
            $this->cache[ $name ] = $cache;
            return true;
        } else {
            return false;
        }
    }

    public function forget($name)
    {
        $cache = [ 'value' => '', 'valid' => 0 ];
        if (file_put_contents($this->cache_dir . $name, json_encode($cache))) {
            $this->cache[ $name ] = $cache;
            return true;
        } else {
            return false;
        }
    }
}