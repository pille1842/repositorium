<?php
namespace Repositorium;

class Helpers
{
    public static function titleToDocumentName($title, $extension)
    {
        return self::toAscii($title) . $extension;
    }

    public static function filenameToDocumentName($filename)
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        return self::toAscii($basename) . '.' . $ext;
    }

    public static function documentNameToPathArray($document, $delimiter)
    {
        $arrPath = explode($delimiter, $document);
        foreach ($arrPath as $key => $value) {
            if (preg_match('/^[\.]+$/', $value)) {
                unset($arrPath[$key]);
            }
        }
        
        return array_filter(array_values($arrPath));
    }

    public static function getLanguageList()
    {
        return array('css' => array('css'),
                     'javascript' => array('js'),
                     'abap' => array('abap'),
                     'actionscript' => array('as'),
                     'ada' => array('adb', 'ads'),
                     'apacheconf' => array('conf'),
                     'applescript' => array('scpt', 'scptd', 'applescript'),
                     'aspnet' => array('aspx', 'cshtml', 'vbhtml'),
                     'autoit' => array('au3'),
                     'autohotkey' => array('ahk'),
                     'bash' => array('bash', 'sh'),
                     'basic' => array('bas'),
                     'batch' => array('bat'),
                     'brainfuck' => array('b', 'bf'),
                     'bro' => array('bro'),
                     'c' => array('c', 'h'),
                     'csharp' => array('cs'),
                     'cpp' => array('cpp', 'c++'),
                     'coffeescript' => array('coffee', 'litcoffee'),
                     'crystal' => array('cr'),
                     'd' => array('d'),
                     'dart' => array('dart'),
                     'diff' => array('diff'),
                     'elixir' => array('ex', 'exs'),
                     'erlang' => array('erl', 'hrl'),
                     'fsharp' => array('fs', 'fsi', 'fsx', 'fsscript'),
                     'fortran' => array('f', 'for', 'f90', 'f95'),
                     'glsl' => array('glsl'),
                     'go' => array('go'),
                     'graphql' => array('graphql'),
                     'groovy' => array('groovy'),
                     'haml' => array('haml'),
                     'haskell' => array('hs', 'lhs'),
                     'haxe' => array('hx', 'hxml'),
                     'icon' => array('icn'),
                     'ini' => array('ini'),
                     'j' => array('j'),
                     'java' => array('java'),
                     'json' => array('json'),
                     'julia' => array('jl'),
                     'keyman' => array('kmn'),
                     'kotlin' => array('kt'),
                     'latex' => array('tex', 'latex'),
                     'less' => array('less'),
                     'livescript' => array('ls'),
                     'lolcode' => array('lol', 'lols'),
                     'lua' => array('lua'),
                     'markdown' => array('md', 'markdown'),
                     'matlab' => array('mat'),
                     'mel' => array('mel'),
                     'monkey' => array('monkey'),
                     'nasm' => array('nasm', 'asm'),
                     'nim' => array('nim'),
                     'nsis' => array('nsi', 'nsh'),
                     'ocaml' => array('mli'),
                     'parigp' => array('gp'),
                     'pascal' => array('pp', 'pas', 'inc'),
                     'perl' => array('pl'),
                     'php' => array('php', 'phtml'),
                     'powershell' => array('ps1'),
                     'processing' => array('pde'),
                     'prolog' => array('pl', 'pro', 'p'),
                     'protobuf' => array('proto'),
                     'python' => array('py'),
                     'qore' => array('q'),
                     'r' => array('r', 'rdata', 'rds', 'rda'),
                     'jsx' => array('jsx'),
                     'rest' => array('rest', 'rst'),
                     'ruby' => array('rb'),
                     'rust' => array('rs', 'rlib'),
                     'sass' => array('sass'),
                     'scss' => array('scss'),
                     'scala' => array('scala', 'sc'),
                     'scheme' => array('scm', 'ss'),
                     'smarty' => array('tpl'),
                     'sql' => array('sql'),
                     'swift' => array('swift', 'swi'),
                     'tcl' => array('tcl'),
                     'textile' => array('textile'),
                     'typescript' => array('ts'),
                     'verilog' => array('v'),
                     'vim' => array('vi', 'vim'),
                     'yaml' => array('yaml')
        );
    }

    public static function getFileLanguage($ext)
    {
        $languages = self::getLanguageList();

        return self::multidimensionalSearch($languages, $ext);
    }

    public static function getEditorLanguage($lang)
    {
        $languages = array(
            'css' => 'css',
            'javascript' => 'javascript',
            'bash' => 'shell',
            'brainfuck' => 'brainfuck',
            'c' => 'clike',
            'csharp' => 'clike',
            'cpp' => 'clike',
            'coffeescript' => 'coffeescript',
            'crystal' => 'crystal',
            'd' => 'd',
            'dart' => 'dart',
            'diff' => 'diff',
            'erlang' => 'erlang',
            'fortran' => 'fortran',
            'go' => 'go',
            'groovy' => 'groovy',
            'haml' => 'haml',
            'haskell' => 'hs', 'lhs',
            'haxe' => 'haxe',
            'json' => 'javascript',
            'julia' => 'julia',
            'less' => 'css',
            'livescript' => 'livescript',
            'lua' => 'lua',
            'markdown' => 'yaml-frontmatter',
            'nsis' => 'nsis',
            'pascal' => 'pascal',
            'perl' => 'perl',
            'php' => 'php',
            'powershell' => 'powershell',
            'protobuf' => 'protobuf',
            'python' => 'python',
            'r' => 'r',
            'jsx' => 'jsx',
            'ruby' => 'ruby',
            'rust' => 'rust',
            'sass' => 'sass',
            'scss' => 'sass',
            'scheme' => 'scheme',
            'smarty' => 'smarty',
            'sql' => 'sql',
            'swift' => 'swift',
            'tcl' => 'tcl',
            'textile' => 'textile',
            'verilog' => 'verilog',
            'yaml' => 'yaml'
        );

        if (isset($languages[$lang])) {
            return $languages[$lang];
        } else {
            return '';
        }
    }

    public static function getEditorModeDependencies($mode, &$dependencies = null)
    {
        if ($dependencies === null) {
            $dependencies = array();
        }

        $arrModeDependencies = array(
            'apl' => array(),
            'asciiarmor' => array(),
            'asn.1' => array(),
            'asterisk' => array(),
            'brainfuck' => array(),
            'clike' => array(),
            'clojure' => array(),
            'cmake' => array(),
            'cobol' => array(),
            'coffeescript' => array(),
            'commonlisp' => array(),
            'crystal' => array(),
            'css' => array(),
            'cypher' => array(),
            'd' => array(),
            'dart' => array('clike'),
            'diff' => array(),
            'django' => array('xml', 'htmlmixed'),
            'dockerfile' => array(),
            'dtd' => array(),
            'dylan' => array(),
            'ebnf' => array('javascript'),
            'ecl' => array(),
            'eiffel' => array(),
            'elm' => array(),
            'erlang' => array(),
            'factor' => array(),
            'fcl' => array(),
            'forth' => array(),
            'fortran' => array(),
            'gas' => array(),
            'gfm' => array('xml', 'markdown', 'javascript', 'css', 'htmlmixed', 'clike'),
            'gherkin' => array(),
            'go' => array(),
            'groovy' => array(),
            'haml' => array('xml', 'htmlmixed', 'javascript', 'ruby'),
            'handlebars' => array('xml'),
            'haskell' => array(),
            'haskell-literate' => array('haskell'),
            'haxe' => array(),
            'htmlembedded' => array('xml', 'javascript', 'css', 'htmlmixed'),
            'htmlmixed' => array('xml', 'javascript', 'css', 'vbscript'),
            'http' => array(),
            'idl' => array(),
            'javascript' => array(),
            'jinja2' => array(),
            'jsx' => array('javascript', 'xml'),
            'julia' => array(),
            'livescript' => array(),
            'lua' => array(),
            'markdown' => array('xml'),
            'mathematica' => array(),
            'mbox' => array(),
            'mirc' => array(),
            'mllike' => array(),
            'modelica' => array(),
            'mscgen' => array(),
            'mumps' => array(),
            'nginx' => array(),
            'nsis' => array(),
            'ntriples' => array(),
            'octave' => array(),
            'oz' => array(),
            'pascal' => array(),
            'pegjs' => array('javascript'),
            'perl' => array(),
            'php' => array('htmlmixed', 'xml', 'javascript', 'css', 'clike'),
            'pig' => array(),
            'powershell' => array(),
            'properties' => array(),
            'protobuf' => array(),
            'pug' => array('javascript', 'css', 'xml', 'htmlmixed'),
            'puppet' => array(),
            'python' => array(),
            'q' => array(),
            'r' => array(),
            'rpm' => array(),
            'rst' => array(),
            'ruby' => array(),
            'rust' => array(),
            'sas' => array('xml'),
            'sass' => array(),
            'scheme' => array(),
            'shell' => array(),
            'sieve' => array(),
            'slim' => array('xml', 'htmlembedded', 'htmlmixed', 'coffeescript', 'javascript', 'ruby', 'markdown'),
            'smalltalk' => array(),
            'smarty' => array('xml'),
            'solr' => array(),
            'soy' => array('htmlmixed', 'xml', 'javascript', 'css'),
            'sparql' => array(),
            'spreadsheet' => array(),
            'sql' => array(),
            'stex' => array(),
            'stylus' => array(),
            'swift' => array(),
            'tcl' => array(),
            'textile' => array(),
            'tiddlywiki' => array(),
            'tiki' => array(),
            'toml' => array(),
            'tornado' => array('xml', 'htmlmixed'),
            'troff' => array(),
            'ttcn' => array(),
            'ttcn-cfg' => array(),
            'turtle' => array(),
            'twig' => array(),
            'vb' => array(),
            'vbscript' => array(),
            'velocity' => array(),
            'verilog' => array(),
            'vhdl' => array(),
            'vue' => array('xml', 'javascript', 'css', 'coffeescript', 'sass', 'pug'),
            'webidl' => array(),
            'xml' => array(),
            'xquery' => array(),
            'yacas' => array(),
            'yaml' => array(),
            'yaml-frontmatter' => array('markdown', 'gfm', 'yaml'),
            'z80' => array()
        );

        $modeDeps = $arrModeDependencies[$mode];

        foreach ($modeDeps as $dep) {
            $dependencies[$dep] = $dep;
            self::getEditorModeDependencies($dep, $dependencies);
        }

        return $dependencies;
    }

    public static function getEditorAddonDependencies($arrModes)
    {
        $dependencies = array();

        $arrModeDependencies = array(
            'apl' => array('edit/matchbrackets'),
            'asciiarmor' => array(),
            'asn.1' => array(),
            'asterisk' => array(),
            'brainfuck' => array('edit/matchbrackets'),
            'clike' => array('edit/matchbrackets', 'hint/show-hint'),
            'clojure' => array(),
            'cmake' => array('edit/matchbrackets'),
            'cobol' => array('edit/matchbrackets', 'selection/active-line', 'search/search', 'dialog/dialog', 'search/searchcursor'),
            'coffeescript' => array(),
            'commonlisp' => array(),
            'crystal' => array('edit/matchbrackets'),
            'css' => array('hint/show-hint', 'hint/css-hint'),
            'cypher' => array(),
            'd' => array('edit/matchbrackets'),
            'dart' => array(),
            'diff' => array(),
            'django' => array('mode/overlay'),
            'dockerfile' => array('mode/simple'),
            'dtd' => array(),
            'dylan' => array('edit/matchbrackets', 'comment/continuecomment', 'comment/comment'),
            'ebnf' => array(),
            'ecl' => array(),
            'eiffel' => array(),
            'elm' => array(),
            'erlang' => array('edit/matchbrackets'),
            'factor' => array('mode/simple'),
            'fcl' => array('edit/matchbrackets'),
            'forth' => array(),
            'fortran' => array(),
            'gas' => array(),
            'gfm' => array('mode/overlay'),
            'gherkin' => array(),
            'go' => array('edit/matchbrackets'),
            'groovy' => array('edit/matchbrackets'),
            'haml' => array(),
            'handlebars' => array('mode/simple', 'mode/multiplex'),
            'haskell' => array('edit/matchbrackets'),
            'haskell-literate' => array(),
            'haxe' => array(),
            'htmlembedded' => array('mode/multiplex'),
            'htmlmixed' => array('selection/selection-pointer'),
            'http' => array(),
            'idl' => array(),
            'javascript' => array('edit/matchbrackets', 'comment/continuecomment', 'comment/comment'),
            'jinja2' => array(),
            'jsx' => array(),
            'julia' => array(),
            'livescript' => array(),
            'lua' => array('edit/matchbrackets'),
            'markdown' => array('edit/continuelist'),
            'mathematica' => array('edit/matchbrackets'),
            'mbox' => array(),
            'mirc' => array(),
            'mllike' => array('edit/matchbrackets'),
            'modelica' => array('edit/matchbrackets', 'hint/show-hint'),
            'mscgen' => array(),
            'mumps' => array(),
            'nginx' => array(),
            'nsis' => array('mode/simple', 'edit/matchbrackets'),
            'ntriples' => array(),
            'octave' => array(),
            'oz' => array(),
            'pascal' => array(),
            'pegjs' => array(),
            'perl' => array(),
            'php' => array('edit/matchbrackets'),
            'pig' => array(),
            'powershell' => array(),
            'properties' => array(),
            'protobuf' => array(),
            'pug' => array(),
            'puppet' => array('edit/matchbrackets'),
            'python' => array('edit/matchbrackets'),
            'q' => array('edit/matchbrackets'),
            'r' => array(),
            'rpm' => array(),
            'rst' => array('mode/overlay'),
            'ruby' => array('edit/matchbrackets'),
            'rust' => array('mode/simple'),
            'sas' => array(),
            'sass' => array('edit/matchbrackets'),
            'scheme' => array(),
            'shell' => array('edit/matchbrackets'),
            'sieve' => array(),
            'slim' => array(),
            'smalltalk' => array('edit/matchbrackets'),
            'smarty' => array(),
            'solr' => array(),
            'soy' => array('edit/matchbrackets'),
            'sparql' => array('edit/matchbrackets'),
            'spreadsheet' => array('edit/matchbrackets'),
            'sql' => array('hint/show-hint', 'hint/sql-hint'),
            'stex' => array(),
            'stylus' => array('hint/show-hint', 'hint/css-hint'),
            'swift' => array('edit/matchbrackets'),
            'tcl' => array('scroll/scrollpastend'),
            'textile' => array(),
            'tiddlywiki' => array('edit/matchbrackets'),
            'tiki' => array(),
            'toml' => array(),
            'tornado' => array('mode/overlay'),
            'troff' => array('edit/matchbrackets'),
            'ttcn' => array(),
            'ttcn-cfg' => array(),
            'turtle' => array(),
            'twig' => array(),
            'vb' => array(),
            'vbscript' => array(),
            'velocity' => array(),
            'verilog' => array('edit/matchbrackets'),
            'vhdl' => array('edit/matchbrackets'),
            'vue' => array('mode/overlay', 'mode/simple', 'selection/selection-pointer'),
            'webidl' => array('edit/matchbrackets'),
            'xml' => array(),
            'xquery' => array(),
            'yacas' => array('edit/matchbrackets'),
            'yaml' => array(),
            'yaml-frontmatter' => array('mode/overlay'),
            'z80' => array()
        );

        foreach ($arrModes as $mode) {
            foreach ($arrModeDependencies[$mode] as $dep) {
                $dependencies[$dep] = $dep;
            }
        }

        return $dependencies;
    }

    public static function getEditorAddonCss($arrAddons)
    {
        $css = array();

        $arrAddonCss = array(
            'merge',
            'hint/show-hint',
            'tern/tern',
            'lint/lint',
            'search/matchesonscrollbar',
            'dialog/dialog',
            'scroll/simplescrollbars',
            'display/fullscreen',
            'fold/foldgutter'
        );

        foreach ($arrAddons as $addon) {
            if (in_array($addon, $arrAddonCss)) {
                $css[] = $addon;
            }
        }

        return $css;
    }

    private static function toAscii($str, $replace = array(), $delimiter = '-')
    {
        if( !empty($replace) ) {
            $str = str_replace((array)$replace, ' ', $str);
        }

        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

        return $clean;
    }

    private static function multidimensionalSearch($parents, $searched) {
        if (empty($parents)) {
            return false;
        }

        foreach ($parents as $key => $value) {
            foreach ($value as $skey => $svalue) {
                if ($svalue == $searched) {
                    return $key;
                }
            }
        }

        return false;
    } 
}