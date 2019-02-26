<?php
namespace Expresso\Core;

/**
 *
 * @package Core
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright Copyright (c) 2019 FGSL (http://www.fgsl.eti.br)
 *           
 */
final class GlobalService
{

    /**
     *
     * @var array
     */
    private static $data = [];

    public static function get($name)
    {
        return self::$data[$name];
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     */
    public static function set($name, $value)
    {
        self::$data[$name] = $value;
    }

    /**
     *
     * @param string $name
     * @return boolean
     */
    public static function isset($name)
    {
        return isset(self::$data[$name]);
    }

    /**
     *
     * @param string $name
     */
    public static function unset($name)
    {
        unset(self::$data[$name]);
    }
}