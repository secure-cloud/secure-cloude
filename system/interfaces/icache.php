<?php
/**
 * ICache
 * 
 * интерфейс для классов, реализующих кэш в различных хранилищах
 * 
 * @author Константин Макарычев
 */
interface ICache {
    /**
     * public function add()
     * 
     * добавление в кеш
     * 
     * @param mixed $data добавляемые данные
     * @return boolean результат вставки
     */
    public function add($data);
    /**
     * public function get()
     * 
     * запрос кэша по идентификатору
     * 
     * @param mixed $id идентификатор записи
     * @return mixed результат запроса
     */
    public function get($id);
    /**
     * public function set()
     * 
     * установка значения кэша
     * 
     * @param mixed $data устанавливаемые данные
     * @param mixed $id идентификатор записи
     * @return boolean результат установки
     */
    public function set($data, $id);
}

?>
