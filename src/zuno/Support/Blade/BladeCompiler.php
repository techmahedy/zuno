<?php

namespace Zuno\Support\Blade;

trait BladeCompiler
{
    protected $fileExtension;

    protected $viewFolder;

    protected $echoFormat;

    protected $extensions = [];

    protected static $directives = [];

    /**
     * Compile blade statements.
     *
     * @param string $statement
     *
     * @return string
     */
    protected function compileStatements($statement): string
    {
        $pattern = '/\B@(@?\w+(?:->\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x';

        return preg_replace_callback($pattern, function ($match) {
            // default commands
            if (method_exists($this, $method = 'compile' . ucfirst($match[1]))) {
                $match[0] = $this->{$method}(isset($match[3]) ? $match[3] : '');
            }

            // custom directives
            if (isset(self::$directives[$match[1]])) {
                if ((isset($match[3][0]) && '(' === $match[3][0])
                    && (isset($match[3][strlen($match[3]) - 1]) && ')' === $match[3][strlen($match[3]) - 1])
                ) {
                    $match[3] = substr($match[3], 1, -1);
                }

                if (isset($match[3]) && '()' !== $match[3]) {
                    $match[0] = call_user_func(self::$directives[$match[1]], trim($match[3]));
                }
            }

            return isset($match[3]) ? $match[0] : $match[0] . $match[2];
        }, $statement);
    }

    /**
     * Compile blade comments.
     *
     * @param string $comment
     *
     * @return string
     */
    protected function compileComments($comment): string
    {
        return preg_replace('/\{\{--((.|\s)*?)--\}\}/', '<?php /*$1*/ ?>', $comment);
    }

    /**
     * Compile blade echoes.
     *
     * @param string $string
     *
     * @return string
     */
    protected function compileEchos($string): string
    {
        // compile escaped echoes
        $string = preg_replace_callback('/\{\{\{\s*(.+?)\s*\}\}\}(\r?\n)?/s', function ($matches) {
            $whitespace = empty($matches[2]) ? '' : $matches[2] . $matches[2];
            return '<?php echo $this->e(' . $this->compileEchoDefaults($matches[1]) . ') ?>' . $whitespace;
        }, $string);

        // compile unescaped echoes
        $string = preg_replace_callback('/\{\!!\s*(.+?)\s*!!\}(\r?\n)?/s', function ($matches) {
            $whitespace = empty($matches[2]) ? '' : $matches[2] . $matches[2];
            return '<?php echo ' . $this->compileEchoDefaults($matches[1]) . ' ?>' . $whitespace;
        }, $string);

        // compile regular echoes
        $string = preg_replace_callback('/(@)?\{\{\s*(.+?)\s*\}\}(\r?\n)?/s', function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3] . $matches[3];
            return $matches[1]
                ? substr($matches[0], 1)
                : '<?php echo '
                . sprintf($this->echoFormat, $this->compileEchoDefaults($matches[2]))
                . ' ?>' . $whitespace;
        }, $string);

        return $string;
    }

    /**
     * Compile default echoes.
     *
     * @param string $string
     *
     * @return string
     */
    public function compileEchoDefaults($string): string
    {
        return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $string);
    }

    /**
     * Compile user-defined extensions.
     *
     * @param string $string
     *
     * @return string
     */
    protected function compileExtensions($string): string
    {
        foreach ($this->extensions as $compiler) {
            $string = $compiler($string, $this);
        }

        return $string;
    }

    /**
     * Replace @php and @endphp blocks.
     *
     * @param string $string
     *
     * @return string
     */
    public function replacePhpBlocks($string): string
    {
        $string = preg_replace_callback('/(?<!@)@php(.*?)@endphp/s', function ($matches) {
            return "<?php{$matches[1]}?>";
        }, $string);

        return $string;
    }

    /**
     * Escape variables.
     *
     * @param string|array|null $string
     * @param string $charset
     *
     * @return string
     */
    public function e(string|array|null $string, $charset = null): string
    {
        if (is_array($string)) {
            $string = implode(' ', $string);
        }
        return htmlspecialchars($string, ENT_QUOTES, is_null($charset) ? 'UTF-8' : $charset);
    }
}
