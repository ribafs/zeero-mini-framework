<?php

namespace Zeero\Database\Resource;



/**
 * 
 * Resource Helper Class
 * 
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
class ResourceHelper
{

    /**
     * return a record in relation 1:1
     *
     * @param string $model
     * @param string $field
     * @param mixed $value
     * @param array|null $only
     * @return array
     */
    public function useOne(string $model, string $field, $value, array $only = null)
    {
        $modelclass = "App\Models\\" . $model;
        $result = $modelclass::findOne("{$field} = ?", [$value]);

        if (is_object($result)) {
            $resource = Resource::single(strtolower($model), $result);

            if ($only) {
                foreach ($resource as $key => $value) {
                    if (!in_array($key, $only)) unset($resource[$key]);
                }
            }
        }

        return $resource ?? [];
    }


    /**
     * return a record in relationship 1:*
     *
     * @param string $model
     * @param mixed $field
     * @param string $value
     * @param array|null $only
     * @return array
     */
    public function useAll(string $model, $field, string $value, array $only = null)
    {
        $modelclass = "App\Models\\" . $model;
        $result = $modelclass::find("{$field} = ?", [$value]);

        $resources = [];

        if (is_array($result)) {

            foreach ($result as $obj) {
                $resource = Resource::single(strtolower($model), $obj);

                if ($only) {
                    foreach ($resource as $key => $value) {
                        if (!in_array($key, $only)) unset($resource[$key]);
                    }
                }

                $resources[] = $resource;
            }
        }

        return $resources;
    }
}
