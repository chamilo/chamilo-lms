<?php
namespace Culqi;

class Tokens extends Resource
{
    /**
     * @param string $id La ID del token a devolver.
     * @param array|string|null $opts
     *
     * @return Token
     */
    public static function retrieve($id, $opts = null)
    {
        return self::_retrieve($id, $opts);
    }
    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Token Token creado
     * ATENCIÓN: Solo para desarollo. Lo ideal es usar el CULQI.JS.
     *
     */
    public static function create($params = null, $opts = null)
    {
        return self::_create($params, $opts);
    }
}
