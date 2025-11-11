<?php

namespace App\Traits;

trait Searchable
{

    public static function tiyin():array
    {
        return [];
    }
    public static function searchEngine()
    {
        $tiyin = self::tiyin();
        $request = request();
        $query = self::query();

        foreach ((new self())->fillable as $item) {
            $operator = $item . '_operator';

            if (!$request->filled($item)) continue;

            $select = isset($tiyin[$item]) ? $request->$item * 100 : $request->$item;
            $select_pair = $request->has($item . '_pair') ?
                (isset($tiyin[$item]) ? $request->{$item . '_pair'} * 100 : $request->{$item . '_pair'})
                : null;

            if ($request->filled($operator)) {
                switch (strtolower($request->$operator)) {
                    case 'between':
                        if ($select_pair !== null) {
                            $query->whereBetween($item, [$select, $select_pair]);
                        }
                        break;

                    case 'wherein':
                        $query->whereIn($item, array_map('trim', explode(',', $select)));
                        break;

                    case 'like':
                        $likeValue = strpos($select, '%') === false ? '%' . $select . '%' : $select;
                        $query->where($item, 'like', $likeValue);
                        break;

                    default:
                        $query->where($item, $request->$operator, $select);
                        break;
                }
            } else {
                if (is_numeric($select)) {
                    $query->where($item, 'like', '%' . $select . '%');
                } else {
                    $query->whereRaw("$item ILIKE ?", ["%$select%"]);
                }

            }
        }
        return $query;
    }

    public static function deepFilters($except = []){
        $obj = new self();
        $request = request();
        $query = self::where('id','!=','0');

        foreach ($obj->fillable as $item) {
            if (in_array($item,$except)) continue;
            //request operator key
            $operator = $item.'_operator';
            $ordering = 'orderBy_'.$item;
            $orderingDesc = 'orderByDesc_'.$item;

            if ($request->has($ordering))
                $query->orderBy($item);

            elseif ($request->has($orderingDesc))
                $query->orderByDesc($item);

            elseif ($request->has($item) && $request->$item != '' && $request->$item != null)
            {
                $select = $request->$item;
                $select_pair = $request->{$item.'_pair'};
                //set value for query
                if ($request->has($operator) && $request->$operator != '')
                {
                    if (strtolower($request->$operator) == 'between' && $request->has($item.'_pair') && $request->{$item.'_pair'} != '')
                    {
                        $value = [
                            $select,
                            $select_pair];

                        $query->whereBetween($item,$value);
                    }
                    elseif (strtolower($request->$operator) == 'wherein')
                    {
                        $value = explode(',',str_replace(' ','',$select));
                        $query->whereIn($item,$value);
                    }
                    elseif (strtolower($request->$operator) == 'like')
                    {
                        if (strpos($select,'%') === false)
                            $query->where($item,'like','%'.$select.'%');
                        else
                            $query->where($item,'like',$select);
                    }
                    else
                    {
                        $query->where($item,$request->$operator,$select);
                    }
                }
                else
                {
                    $query->where($item,'like',"%$select%");
                }
            }
        }
        return $query;
    }

    public static function search(array $params,$multiple = false)
    {
        $params = array_filter($params);
        $obj = new self();
        $attributes = array_merge($obj->fillable,['id']);
        $query = self::whereNotNull('id');
        foreach ($attributes as $attribute) {
            if (isset($params[$attribute])){
                if (isset($params[$attribute.'_operator']))
                    $query->where($attribute,$params[$attribute.'_operator'],$params[$attribute]);
                else
                {
                    if ($multiple)
                        $query->where($attribute,'like',"%".$params[$attribute]."%");
                    else
                        $query->where($attribute,'like',$params[$attribute]."%");
                }
            }
        }
        return $query;
    }
}
