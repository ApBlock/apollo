<?php
namespace ApBlock\Apollo\Logger;

class LoggerVisualizer
{
    private $browserName;

    private $browserVersion;

    private $platform;

    private $phpVersion;

    private $exceptionMessage;

    private $exceptionTrace;

    public function __construct()
    {
        $browser = new Browser();
        $this->browserName = $browser->getName();
        $this->browserVersion = $browser->getVersion();
        $this->platform = $browser->getPlatform();
        $this->phpVersion = explode('PHP/',$_SERVER["SERVER_SOFTWARE"])[1];

    }

    public function addException($error){
        if(is_array($error)){
            $this->exceptionMessage = $error["message"];
            $this->exceptionTrace = $error["trace"];
        }
        if($error instanceof \Exception){
            $this->exceptionMessage = $error->getMessage();
            $this->exceptionTrace = $error->getTrace();
        }
    }

    public function render(){
        $html = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/vendor/apblock/apollo/src/Logger/Visualizer/html/visualizer.html');
        $exceptionTrace = array();
        foreach($this->exceptionTrace as $trace) {
            $lineContent = "";
            $file = new \SplFileObject($trace["file"]);
            $file->setFlags($file::READ_AHEAD);
            $lines = iterator_count($file);

            if ($file = fopen($trace["file"], "r")) {
                $i = 0;
                $startLine = $trace["line"]-5 >= 0 ? $trace["line"]-5 : 0;
                $endLine = $trace["line"]+5 <= $lines ? $trace["line"]+5 : $lines;
                while (!feof($file)) {
                    $i++;
                    $line = fgets($file);
                    if($i >= $startLine){
                        $lineContent .= ($i == $trace["line"] ? '<span class="text-light bg-dark">'.$line.'</span>' : $line)."<br>";
                        if($i > $endLine){
                            break;
                        }
                    }
                }
                fclose($file);
                $trace["lines"] = $lineContent;
            }
            $exceptionTrace[] = $trace;
        }

        return str_replace(
            array(
                '__BROWSER__',
                '__BROWSER_VERSION__',
                '__PLATFORM__',
                '__PHP__',
                '__MAIN_EXCEPTION__',
                '__TRACE_HISTORY__',
            ),
            array(
                $this->browserName,
                $this->browserVersion,
                $this->platform,
                $this->phpVersion,
                $this->exceptionMessage,
                json_encode($exceptionTrace),
            ),
            $html);
    }
}
