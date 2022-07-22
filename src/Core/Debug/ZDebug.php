<?php

namespace Zeero\Core\Debug;

use Throwable;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Zeero\facades\Request;
use Zeero\Kernel;

/**
 * Zeero Debug and Exception Report
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
final class ZDebug
{

    /**
     * Sclice the Lines Array from the first statment that start with $
     *
     * @param array $lines
     * @param integer $line
     * @return array
     */
    public static function sliceLinesArray(array $lines, int $line)
    {
        $line += 1;

        for ($i = $line; $i < count($lines); $i++) {
            // remove whitespaces
            $l = trim($lines[$i]);
            // a statement that start with variable
            if (strpos($l, '$') === 0) {
                return array_slice($lines, 0, $i);
            }
        }

        return $lines;
    }


    /**
     * Render the Zdebug view
     *
     * @param array $info
     * @return void
     */
    private static function show(array $info)
    {
        $loader = new FilesystemLoader([__DIR__ . DS]);
        $twig = new Environment($loader);

        $twig->addFunction(new TwigFunction('exclude_basedir', function ($f, $v = null) {

            if ($pos = strpos($f, 'zeero-framework')) {
                return substr($f, $pos);
            } else if ($pos = strpos($f, 'App')) {
                return substr($f, $pos + 4);
            }

            if ($v) {
                if (($p = strpos($f, 'vendor'))) {
                    return substr($f, $p);
                }
            }
        }));


        echo $twig->render('errorReport.twig', $info);
        die;
    }


    /**
     * Get the Information from the Throwable Instance
     *
     * @param Throwable $obj
     * @return array
     */
    private static function getInfo(Throwable $obj): array
    {
        $info = [];
        $info['_class'] = $obj::class;
        $_line = $info['_line'] = $obj->getLine();
        $info['_message'] = $obj->getMessage();
        $info['_stack'] = $obj->getTrace();

        // rewrite twig loader error
        if (($p = strpos($info['_message'], '(looked into')) > 0) {
            $info['_message'] = substr($info['_message'], 0, $p);
        }

        // 
        if (strpos($info['_class'], 'Exception')) {
            $info['base'] = 'Exception';
            $info['item'] = str_replace('Exception', '', $info['_class']);
        } else {
            $info['base'] = 'Error';
            $info['item'] = str_replace(['Error'], '', $info['_class']);
        }
        //

        $_file = $info['_file'] = $obj->getFile();

        $appdir = substr(APP_DIR, 0, strpos(APP_DIR, 'public') - 1);

        // find the first App directory file to display their entries
        for ($i = 0; $i < count($info['_stack']); $i++) {
            $value = $info['_stack'][$i];

            if (isset($value['file'])) {
                if (strpos($value['file'], $appdir) === 0) {
                    $_file = $value['file'];
                    $_line = $value['line'];
                    break;
                }
            }
        }

        $lines = file($_file);

        if (strpos($lines[0], '<?php') === 0)
            $lines = array_slice($lines, 1);

        $real_line = $_line;
        $_line -= 2;

        // remove null values
        $lines = array_filter($lines, function ($i) {
            return $i != null;
        });

        $lines = self::sliceLinesArray($lines, $_line);

        // remove *use statement
        $lines = array_filter($lines, function ($i) {
            return strpos($i, 'use ') !== 0;
        });

        // the line to highlight
        $_ln = $lines[$_line];

        // reorder the indexes
        $lines = array_values($lines);

        // get the new array index
        $_line = array_search($_ln, $lines);

        $line_minus_ten = $_line - 20;

        if (isset($lines[$line_minus_ten])) {
            $lines = array_slice($lines, $line_minus_ten);
            $_line = array_search($_ln, $lines);
        }

        $info['_f_lines'] = $lines;
        $info['_f'] = $_file;
        $info['_f_line'] = $_line;
        $info['_real_line'] = $real_line;

        return $info;
    }


    /**
     * Process the Throwable instance
     *
     * @param Throwable $obj
     * @return void
     */
    public static function debug(Throwable $obj)
    {
        Kernel::BootAppLogger();
        app_log()->warning($obj->getMessage(), [$obj->getFile(), $obj->getLine()]);

        if (DEV) {
            // in Browser Request
            if (substr(_ROOT_, -6) == 'public') {

                if (!Request::isAjax()) {
                    $info = self::getInfo($obj);
                    self::show($info);
                } else {
                    json_response(['error' => 'internal server error'], 500);
                }
            } else {
                // in CLI
                die($obj->getMessage() . " ( " . $obj->getFile() . " ) (" . $obj->getLine() . ")");
            }
        } else {

            if (substr(_ROOT_, -6) == 'public') {
                errorPage('Server Error', 'Internal Server Error', 500);
            } else {
                die($obj->getMessage());
            }
        }
    }
}
