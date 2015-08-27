<?php

namespace App\Http\Controllers;

Use Form;
Use Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

Use scssc;

class SassController extends Controller
{

    protected static $scssFolder;
    protected static $cssFolder;

    protected static $extraLines;
    protected static $filesToImport;

    // this runs on initialization
    public function __construct() {
        // the folder that the scss files are in
        Self::$scssFolder = base_path('public/sass-compile/');
        // where to put the compiled css file
        Self::$cssFolder  = base_path('public/css-compiled/');
        // two empty arrays for later use
        Self::$extraLines = array();
        Self::$filesToImport = array();
    }

    // get a file line by line and return an array of the lines
    static public function getFileLines($file) {
        $result = array();
        if (file_exists($file)) {
            $handle = fopen($file, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $result[] = $line;
                }

                fclose($handle);
            } else {
                // error opening the file.
            }
        }
        return $result;
    }

    // list all imports in scss files in root folder
    static public function listImports() {
        $imports = array();
        // get all .scss files from scss folder
        $filelist = glob(Self::$scssFolder . "*.scss");
        // step through all .scss files in that folder
        foreach ($filelist as $file_path) {
            // get path elements from that file
            $file_path_elements = pathinfo($file_path);
            // get file's name without extension
            $file_name = $file_path_elements['filename'];
            // get all lines in file
            $lines = Self::getFileLines($file_path);
            // loop through the lines
            foreach ($lines as $line) {
                // if the line starts with @import
                if (substr($line,0,7)=='@import') {
                    // match anything inside '' or ""
                    if (preg_match_all('~(["\'])([^"\']+)\1~', $line, $arr)) {
                        // if a match exists, add the filename to an array
                        $imports[] = $arr[2][0].'.scss';
                    }
                }
            }
        }
        return $imports;
    }

    // recursive function to import all files into a massive array of all the lines
    static public function processImports($lines, $pathinfo) {
        // loop through the lines
        foreach ($lines as $line) {
            // if line starts with @import
            if (substr($line,0,7)=='@import') {
                // match "" or ''
                if (preg_match_all('~(["\'])([^"\']+)\1~', $line, $arr)) {
                    // generate filename
                    $importFileName = $arr[2][0].'.scss';
                    // if the filename generated matches one that was submitted in the form
                    if (in_array($importFileName, Self::$filesToImport)) {
                        // stick its lines into the array
                        $file = $pathinfo['dirname'].'/'.$importFileName;
                        $importpathinfo = pathinfo($file);
                        $importLines = Self::getFileLines($file);
                        // and run this function on those lines to catch any nested imports
                        $importLines = Self::processImports($importLines,$importpathinfo);
                        foreach ($importLines as $importLine) {
                            $newlines[] = $importLine;
                        }
                    }
                }
            } else {
                $newlines[] = $line;
            }
        }
        return $newlines;
    }

    // add any lines submitted in the form to the beginning of the input array
    static public function extraLines($lines) {
        $extralines = Self::$extraLines;
        if (is_array($extralines) && count($extralines)) {
            $newLines = array();
            foreach ($extralines as $line) {
                $newLines[] = $line;
            }
            foreach ($lines as $line) {
                $newLines[] = $line;
            }
            $lines = $newLines;
        }
        return $lines;
    }

    /**
     * Compiles all .scss files in a given folder into .css files in a given folder
     *
     * @param string $format_style CSS output format, see http://leafo.net/scssphp/docs/#output_formatting for more.
     */
    static public function compile($format_style = "scss_formatter")
    {
        $scss_compiler = new scssc();
        // set the path where your _mixins are
        $scss_compiler->setImportPaths(Self::$scssFolder);
        // set css formatting (normal, nested or minimized), @see http://leafo.net/scssphp/docs/#output_formatting
        $scss_compiler->setFormatter($format_style);
        // get all .scss files from scss folder
        $filelist = glob(Self::$scssFolder . "*.scss");
        // step through all .scss files in that folder
        foreach ($filelist as $file_path) {
            // get path elements from that file
            $file_path_elements = pathinfo($file_path);
            // get file's name without extension
            $file_name = $file_path_elements['filename'];
            // get all lines in file
            $lines = Self::getFileLines($file_path);
            $lines = Self::processImports($lines,$file_path_elements);
            if (count($lines)) {
                // add the extra lines
                $lines = Self::extraLines($lines);
                // implode the array into one big string
                $string_sass = implode($lines);
                // compile this SASS code to CSS
                $string_css = $scss_compiler->compile($string_sass);
                $cssFile = Self::$cssFolder . $file_name . ".css";
                // if file exists
                if (file_exists($cssFile)) {
                    // attempt delete
                    unlink($cssFile);
                }
                // write CSS into file with the same filename, but .css extension
                file_put_contents($cssFile, $string_css);
            }
        }

    }

    // deal with any extra lines submitted be the form - add them to a global array
    static public function processInputs($inputs) {
        // which inputs do we want to add to the file
        $whichInputs = array('input_name', 'input_that_does_not_exist');
        foreach ($whichInputs as $input) {
            if (array_key_exists($input, $inputs)) {
                // an example of how you could use this function
                // lets say we want input_that_does_not_exist to have something around it?
                // if we are on that input we want
                if ($input == 'input_that_does_not_exist') {
                    // add some text around it
                    // this will make the input change from just '#000' to 'body { background-color: #000; }'
                    // the change will only happen if something was actually inputted - which is why we are using 'input_that_does_not_exist' for this one
                    $inputs[$input] = 'body { background-color: '.$inputs[$input].'; }';
                }
                Self::$extraLines[] = $inputs[$input];
            }
        }
    }

    // make an array of any files specified in the form
    static public function processImportFiles($inputs) {
        if (isset($inputs['import_file']) && count($inputs['import_file'])) {
            Self::$filesToImport = $inputs['import_file'];
        }
    }

    // load the form view and validate form inputs
    public function form(Request $request)
    {
        if ($request->isMethod('post')) {
            // input validation
            $this->validate($request, [
                'input_name' => 'required',
            ]);
            Self::processInputs($request->all());
            Self::processImportFiles($request->all());
            /*
            The next line is what compiles the scss - options:
             - scss_formatter  - this un-nests all the css
             - scss_formatter_nested (default) - this nests all the nextes css
             - scss_formatter_compressed - one line compressed and optimized
            See http://leafo.net/scssphp/docs/#output_formatting for more info
            */
            Self::compile('scss_formatter_nested');
        }
        return view('form',['imports'=>Self::listImports(), 'request'=>$request->all()]);
    }
}