<?php
namespace Cache;
/**
 * MCache
 * 
 * реализует ICache для работы с memcached
 * 
 * @author Константин Макарычев
 */
class Memcache implements ICache {
    private $memcache;
    public function __construct() {
        $this->memcache = new \Memcache();
        $this->memcache->connect(
			\System\Config::instance()->cache['memcache']['address'],
			\System\Config::instance()->cache['memcache']['port']
		);
    }
    
    public function add($data, $key = NULL, $expiration = 3600) {
        //для сохранения совместимости
        if ($key == NULL)
            $key = md5(microtime());

        $data = is_scalar($data) ? (string)$data : $data;
        return $this->memcache->add($key, $data, MEMCACHE_COMPRESSED);
    }
    
    public function get($key) {
        $ret = $this->memcache->get($key);
        return $ret === FALSE ? NULL : $ret;
    }
    
    public function set($data, $key, $expiration = 3600) {
        if ($key == NULL)
            $key = md5(microtime());
        $data = is_scalar($data) ? (string)$data : $data;
    }
}

?>
