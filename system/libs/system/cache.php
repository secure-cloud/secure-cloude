<?php
namespace System;
/**
 * CacheFactory
 * 
 * класс с единственным фабричным методом для работы с кэшем
 * 
 * @author Константин Макарычев
 */
class Cache {
    const CACHE_MEMORY = 0;
    const CACHE_FILE = 1;
    const CACHE_MYSQL = 2;
    /**
     * public static function init
     * 
     * фабричный метод возвращает нужный экземпляр класса кэша, в зависимости
     * от условий
     * 
     * @param string $storage хранилище кэша
     * @return ICache экземпляр реализации кэша
     */
    public static function factory($storage) {
        switch($storage) {
            case self::CACHE_MEMORY:
                return new \Cache\Memcache();
			case self::CACHE_MYSQL:
				return new \Cache\Mysql();
			case self::CACHE_FILE:
				return new \Cache\File();
        }
    }

}

?>
