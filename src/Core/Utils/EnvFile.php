<?php

namespace Zeero\Core\Utils;

use Exception;
use Zeero\Core\Utils\Dictionary;

/**
 * Env File Class
 * 
 * this class represent a env file
 * 
 * @author  carlos bumba <carlosbumbanio@gmail.com>
 */
class Envfile
{
    /**
     * the env filename
     *
     * @var string
     */
    private string $filename;

    /**
     * the env file real content
     *
     * @var array
     */
    private array $content;

    /**
     * The Index of each item in real content
     *
     * this variable is util in @UpdateItem Method
     * @var array
     */
    private array $itemsIndex = [];


    /**
     * the content as a dictionary object
     *
     * @var Dictionary
     */
    private Dictionary $dictionary;


    /**
     * set the filename
     *
     * @param string $filename
     * @return void
     */
    public function setFilename(string $filename): void
    {
        if (substr(_ROOT_, -6) == 'public') {
            $filename = _ROOT_ . DS . '..' . DS . '..' . DS . $filename;
        } else {
            $filename = _ROOT_ . DS . $filename;
        }

        if (!file_exists($filename)) {
            throw new Exception("File Not Exists");
        }

        $this->filename = $filename;
        // load the content
        $this->loadContent();
        // build the dictionary from the contents
        $this->loadDictionary();
    }


    /**
     * get the dictionary that represent all data in env file
     *
     * @return Zeero\Utils\Dictionary
     */
    public function getDictionary(): Dictionary
    {
        return $this->dictionary;
    }



    /**
     * Add a new item into env file
     *
     * @param string $item
     * @param string $value
     * @return void
     */
    public function addItem(string $item, string $value)
    {
        if ($this->dictionary->has($item)) {
            throw new Exception("The Item '$item' Already Exists");
        }

        // add a new pair
        $this->content[] = "{$item} = {$value}\n";

        // update the real file
        file_put_contents($this->filename, implode('', $this->content));

        // update the dictionary
        $this->dictionary->set($item, $value);
    }


    /**
     * Update a item value in env file
     *
     * @param string $item
     * @param string $value
     * @return bool
     */
    public function updateItem(string $item, string $value): bool
    {
        if ($this->dictionary->has($item)) {
            // the item index in real content
            $index = $this->itemsIndex[$item];
            // change the item value
            $this->content[$index] = "{$item} = {$value} \n";

            // update the real file
            file_put_contents($this->filename, implode('', $this->content));

            // update the dictionary
            $this->dictionary->replace($item, $value);
            return true;
        }

        return false;
    }

    public function removeItem(string $item)
    {
        if ($this->dictionary->has($item)) {
            // remove in dictionary
            $this->dictionary->remove($item);

            // remove in the itemsIndex array
            $index = $this->itemsIndex[$item];
            unset($this->itemsIndex[$item]);
            // remove in the content
            unset($this->content[$index]);

            // update the real file
            file_put_contents($this->filename, implode('', $this->content));
        }
    }

    /**
     * load the content of the env file
     *
     * @return void
     */
    private function loadContent(): void
    {
        $this->content = file($this->filename);
    }


    /**
     * load the content and build a dictionary
     *
     * @return void
     */
    private function loadDictionary(): void
    {
        $dictionary = [];

        for ($i = 0; $i < count($this->content); $i++) {
            $line = trim($this->content[$i]);

            // avoid comments
            if (strpos($line, '##') !== 0) {
                if (strpos($line, '=')) {
                    list($key, $value) = explode('=', $line);
                    $key = trim($key);

                    // test if the key already exists
                    if (!isset($dictionary[$key])) {
                        // store the pair
                        $dictionary[$key] = trim($value);
                        // store the index
                        $this->itemsIndex[$key] = $i;
                    }
                }
            }
        }

        // create a dictionary instance
        $this->dictionary = new Dictionary($dictionary);
    }
}
