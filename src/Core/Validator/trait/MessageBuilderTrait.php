<?php

namespace Zeero\Core\Validator\trait;

use Zeero\Core\Validator\Rule;

/**
 * MessageBuilder
 * 
 * process and return a messages array
 * 
 * @author Carlos Bumba git:@CarlosNIo
 */
trait MessageBuilderTrait
{


    /**
     * helper: check if a validation is true in all tests
     *
     * @return int
     * @author Carlos Bumba
     **/

    private static function helper_rule_in_all_params(string $rule, array $target)
    {
        $search = ".{$rule}";
        $positive = 1;

        foreach ($target as $key => $value) {
            if (strpos($key, $search) > 0) {
                if (!$value) $positive = 0;
            }
        }

        return $positive;
    }


    /**
     * helper: put variable message into message text
     *
     * this is an helper that replace output varibles in message for respectives values
     *
     * @return string
     * @author Carlos Bumba
     **/

    private static function helper3_put_message(array $data, string $txt, string $item)
    {
        $pos = strpos($item, ".");
        $placeholder = substr($item, $pos + 1);
        $regexp = "/\{(\d)\}/";

        if (strpos($txt, "{{$placeholder}}")) {
            $v = $data[$item]["data"];

            if (is_array($v)) $v = "[" . implode(" , ", $v) . "]";

            $txt = str_replace("{{$placeholder}}", $v, $txt);
            //
        } else if (preg_match_all($regexp, $txt, $match)) {
            // ex: {0} => [0]
            $i = $data[$item]["data"];
            foreach ($match[1] as $key => $value) {
                $txt = str_replace("{{$value}}", $i[$value], $txt);
            }
        }

        return $txt;
    }


    /**
     * Make a fields array , that contains the information about the rule
     * 
     * @return array
     */
    private function makefieldsArray(array $rules)
    {
        $fields = [];

        foreach ($rules as $f => $r) {
            if (strpos($r, "|")) {
                $list = explode("|", $r);
                foreach ($list as $value) {
                    $info = Rule::info($value);
                    $fields["$f.{$info['name']}"] = $info;
                }
            } else {
                $info = Rule::info($r);
                $fields["$f.{$info['name']}"] = $info;
            }
        }

        return $fields;
    }


    /**
     * Return the messages errors of the validation
     *
     * this function return an `EMPTY` array if the validation was success
     * and else return an array with the respectives messages errors
     *
     * @return array
     * @author Carlos Bumba
     **/
    private function buildMessages(array $tests, array $rules, array $messages)
    {
        $fields = $this->makefieldsArray($rules);
        $list = [];
        
        foreach ($messages as $item => $message) {
            // for example: name.min
            if (strpos($item, ".") === false) {

                $v = self::helper_rule_in_all_params($item, $tests);

                if ($v === 0) {
                    $list[$item] = self::helper3_put_message($fields, $message, $item);
                }
                //
            } else {
                // for example: required
                $v = $tests[$item] ?? 1;

                if ($v === false) {
                    $list[$item] = self::helper3_put_message($fields, $message, $item);
                }
                //
            }
        }


        return $list;
    }
}