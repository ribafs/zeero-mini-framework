<?php

namespace Zeero\facades;

use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile as FileUploadedFile;
use Symfony\Component\HttpFoundation\FileBag;


class UploadedFile
{

    /**
     * get a uploaded file
     *
     * @param string $key the file key
     * @return FileUploadedFile
     */
    public static function get(string $key)
    {
        $bag = new FileBag($_FILES);
        return $bag->get($key);
    }

}
