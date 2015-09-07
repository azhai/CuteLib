<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 *
 * CODE FROM: http://code.google.com/p/php-compressor/
 * AUTHOR: Вариант номер три  http://blog.amartynov.ru/
 */

namespace Cute;


/**
 * PHP代码压缩
 * NOTICE: 
 *   如果代码中使用了extract()函数，为避免这些变量名被改写，
 *   需要用变量的字符串形式${'x'}代替通常形式$x
 */
class Bale
{
    public static $RESERVED_VARS = array(
        '$GLOBALS' => 1,
        '$_ENV' => 1,
        '$_SERVER' => 1,
        '$_SESSION' => 1,
        '$_REQUEST' => 1,
        '$_GET' => 1,
        '$_POST' => 1,
        '$_FILES' => 1,
        '$_COOKIE' => 1,
        '$HTTP_RAW_POST_DATA' => 1,
        '$php_errormsg' => 1,
        '$http_response_header ' => 1,
        '$argc ' => 1,
        '$argv ' => 1,
        '$this' => 1
    );
    public $comment = null;
    public $options = array(
        'shrink' => true,     //混淆变量名
        'phptag' => true,      //添加PHP开始标记
        'keeplb' => false,     //保持换行
    );
    private $tokens = array();
    private $ns = array();
    private $last_is_root_ns = false;
    
    public function __construct(array $options = array())
    {
        $this->options = array_merge($this->options, $options);
    }
    
    public function prepend($filename)
    {
        $this->load(file_get_contents($filename));
        return $this;
    }
    
    public function minify($outfile, $infiles)
    {
        $files = array_slice(func_get_args(), 1);
        $infiles = call_user_func_array('array_merge', $files);
        // 去重复文件
        $infiles = array_unique($infiles, SORT_STRING);
        foreach ($infiles as $filename) {
            $this->load(file_get_contents($filename));
        }
        if ($content = $this->run()) {
            file_put_contents($outfile, str_replace("\n\n", "\n", $content), LOCK_EX);
            if ($filename = reset($infiles)) { // 对照修改文件的所有者
                chown($outfile, fileowner($filename));
                chgrp($outfile, filegroup($filename));
            }
            $this->tokens = array();
            $this->ns = array();
        }
        return filesize($outfile);
    }

    public function load($text)
    {
        $ns_start = count($this->tokens);
        $ns = $this->add_tokens($text);
        $ns_name = $ns['name'];
        $ns['start'] = $ns_start;
        if ($ns_name === false) {
            if (!empty($this->ns))
                $this->tokens[] = "}\n"; //添加中间结束
        } else if (isset($this->ns[$ns_name])) {
            $ns_uses = array_diff_key($ns['uses'], $this->ns[$ns_name]['uses']);
            $this->ns[$ns_name]['uses'] += $ns_uses;
            $ns_tokens = $this->add_uses($ns_uses);
            array_splice($this->tokens, $ns_start, 0, $ns_tokens);
        } else {
            if ($ns_name === '\\') { //根域名
                $ns_open = "\nnamespace{";
                $this->last_is_root_ns = true;
            } else if ($this->last_is_root_ns) {
                $ns_open = "\nnamespace $ns_name{";
                $this->last_is_root_ns = false;
            } else {
                $ns_open = empty($this->ns) ? '' : "}\n";
                $ns_open .= "namespace $ns_name{";
            }
            $this->ns[$ns_name] = $ns;
            $ns_tokens = $this->add_uses($ns['uses']);
            array_unshift($ns_tokens, array(-1, $ns_open));
            array_splice($this->tokens, $ns_start, 0, $ns_tokens);
        }
    }

    public function run()
    {
        if ($this->options['shrink']) {
            $this->shrink_var_names();
        }
        $this->remove_public_modifier();
        $result = $this->options['phptag'] ? "<?php\n" : '';
        $content = $this->generate_result($result);
        if (!empty($this->ns) && substr($content, strlen($content)-2) !== "}\n")
            $content .= '}'; //添加全文结束
        return $content;
    }
    
    private function add_uses(array $ns_uses)
    {
        $ns_tokens = array();
        foreach ($ns_uses as $alias => $origin) {
            if ($alias === $origin)
                $ns_tokens[] = array(-1, "use $origin;");
            else
                $ns_tokens[] = array(-1, "use $origin as $alias;");
        }
        return $ns_tokens;
    }

    private function generate_result($result)
    {
        if ($this->comment) {
            foreach ($this->comment as $line) {
                $result .= '# ' . trim($line) . "\n";
            }
        }

        foreach ($this->tokens as $t) {
            $text = $t[1];

            if (!strlen($text))
                continue;

            if (preg_match('~^\\w\\w$~', $result[strlen($result) - 1] . $text[0]))
                $result .= ' ';

            $result .= $text;
        }

        return $result;
    }

    private function remove_public_modifier()
    {
        for ($i = 0; $i < count($this->tokens) - 1; $i++) {
            if ($this->tokens[$i][0] == T_PUBLIC)
                $this->tokens[$i] = $this->tokens[$i + 1][1][0] == '$' ? array(T_VAR, 'var') : array(-1, '');
        }
    }

    private function shrink_var_names()
    {
        $stat = array();
        $indices = array();

        for ($i = 0; $i < count($this->tokens); $i++) {
            list($type, $text) = $this->tokens[$i];

            if ($type != T_VARIABLE)
                continue;

            if (isset(self::$RESERVED_VARS[$text]))
                continue;

            if ($i > 0) {
                $prev_type = $this->tokens[$i - 1][0];
                if ($prev_type == T_DOUBLE_COLON)
                    continue;
                if ($this->is_class_scope($i))
                    continue;
            }

            $indices[] = $i;
            if (!isset($stat[$text]))
                $stat[$text] = 0;
            $stat[$text] ++;
        }

        arsort($stat);

        $aliases = array();
        foreach (array_keys($stat) as $i => $name) {
            $aliases[$name] = $this->encode_id($i);
        }
        unset($stat);

        foreach ($indices as $index) {
            $name = $this->tokens[$index][1];
            $this->tokens[$index][1] = '$' . $aliases[$name];
        }
    }

    private function is_class_scope($index)
    {
        while ($index--) {
            $type = $this->tokens[$index][0];
            if ($type == T_CLASS)
                return true;
            if ($type == T_FUNCTION)
                return false;
        }
        return false;
    }

    private function add_tokens($text)
    {
        $tokens = token_get_all(trim($text));
        if (!count($tokens))
            return;

        if (is_array($tokens[0]) && $tokens[0][0] == T_OPEN_TAG)
            array_shift($tokens);

        $last = count($tokens) - 1;
        if (is_array($tokens[$last]) && $tokens[$last][0] == T_CLOSE_TAG)
            array_pop($tokens);

        $pending_whitespace = count($this->tokens) ? "\n" : '';

        $ns = array('name'=> false, 'uses' => array());
        $i = 0;
        $count = count($tokens);
        while ($i < $count) {
            $t = $tokens[$i++];
            if (!is_array($t))
                $t = array(-1, $t);

            if ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT)
                continue;

            if ($t[0] === T_WHITESPACE) {
                $pending_whitespace .= $t[1];
                continue;
            }
            
            if ($t[0] === T_NAMESPACE) { //处理namespace，顶部声明改为体内声明的形式
                $ns['name'] = $this->parse_namespace($tokens, $t, $i, $count);
                continue;
            }
            
            if ($t[0] === T_USE) { //处理use，合并同一个namespace下相同的use
                list($use_orig, $use_alias) = $this->parse_use($tokens, $t, $i, $count);
                $ns['uses'][$use_alias] = $use_orig;
                continue;
            }

            if ($this->options['keeplb'] && strpos($pending_whitespace, "\n") !== false) {
                $this->tokens[] = array(-1, "\n");
            }
            $this->tokens[] = $t;
            if ($t[0] === T_END_HEREDOC) { //处理heredoc结尾，单独占一行
                $this->tokens[] = array(-1, "\n");
            }

            $pending_whitespace = '';
        }
        if ($ns['name'] === '') {
            $ns['name'] = '\\';
        }
        return $ns;
    }
    
    private function parse_namespace(array& $tokens, $t, & $i, $count)
    {
        $ns_name = '';
        do {
            $t = $tokens[$i++];
            if (!is_array($t))
                $t = array(-1, $t);
            if ($t[1] === ';' || $t[1] === '{')
                break;
            if (!in_array($t[0], array(T_NAMESPACE,T_COMMENT,T_WHITESPACE), true)) {
                $ns_name .= $t[1];
            }
        } while ($i < $count);
        return $ns_name;
    }
    
    private function parse_use(array& $tokens, $t, & $i, $count)
    {
        $use_orig = '';
        $use_alias = '';
        $var_name = 'use_orig';
        do {
            $t = $tokens[$i++];
            if (!is_array($t))
                $t = array(-1, $t);
            if ($t[1] === ';')
                break;
            if ($t[0] === T_USE)
                $var_name = 'use_orig';
            else if ($t[0] === T_AS)
                $var_name = 'use_alias';
            else if (!in_array($t[0], array(T_COMMENT,T_WHITESPACE), true)) {
                ${$var_name} .= $t[1];
            }
        } while ($i < $count);
        return array($use_orig, $use_alias ?: $use_orig);
    }

    private function encode_id($value)
    {
        $result = '';

        if ($value > 52) {
            $result .= $this->encode_id_digit($value % 53);
            $value = floor($value / 53);
        }

        while ($value > 62) {
            $result .= $this->encode_id_digit($value % 63);
            $value = floor($value / 63);
        }

        $result .= $this->encode_id_digit($value);
        return $result;
    }

    private function encode_id_digit($digit)
    {
        if ($digit < 26)
            return chr(65 + $digit);
        if ($digit < 52)
            return chr(71 + $digit);
        if ($digit == 52)
            return '_';
        return chr($digit - 5);
    }
}
