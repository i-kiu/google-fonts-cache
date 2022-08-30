<?php

namespace Ikiu\GoogleFontsCache;

class Bootstrap
{
    /**
     * @var string
     */
    private $dir;

    /**
     * @var int
     */
    private $mode;

    /**
     * @var string
     */
    private $filename='';

    /**
     * FileCacher constructor.
     *
     * @param string $path
     * @param int $mode
     */
    public function __construct(string $path, int $mode = 0777)
    {
        $this->dir = $path;
        $this->mode = $mode;
        if (!$this->mkdir($this->dir, $this->mode)) {
            trigger_error("can't create cache director {$this->dir}", E_USER_WARNING);
        }
        if (!$this->mkdir($this->dir . 'fonts/', $this->mode)) {
            trigger_error("can't create Font cache director {$this->dir}", E_USER_WARNING);
        }
    }

    public function init()
    {
        if (isset($_GET['family'])) {
            $this->loadRemoteFontCss($_GET['family']);
        }
    }

    private function loadRemoteFontCss($FontFamily)
    {
        $this->filename= $this->dir . 'remotefont-' . md5($FontFamily) . '.css';


        if (!file_exists($this->filename) || (time() - 84600 < filemtime($this->filename))) {
            $client = new \GuzzleHttp\Client();
            $fontUri = 'https://fonts.googleapis.com/css2?family=' . urlencode($FontFamily);
            $response = $client->request('GET', $fontUri);

            if ($response->getStatusCode()===200) {
                $data = $response->getBody();
                $data = $this->retreiveFontsByCSS($data);
                file_put_contents($this->filename, $data);
            }
        }

    }

    protected function retreiveFontsByCSS($css)
    {
        $pattern = '/url\((.*)\)/mU';
        $protocol = stripos($_SERVER['REQUEST_SCHEME'], 'https') === 0 ? 'https://' : 'http://';
        $replaceUri =$protocol . $_SERVER['SERVER_NAME'] . '/css/fonts/';
        if (preg_match_all($pattern, $css, $matches, PREG_SET_ORDER, 0)) {
            $information = [];
            foreach ($matches as $match) {
                $uri=$match[1];
                $fileName =$this->dir . 'fonts/' . basename($uri);
                if (!file_exists($fileName) || (time() - 84600 < filemtime($fileName))) {
                    $client = new \GuzzleHttp\Client();
                    $response = $client->request('GET', $uri);
                    if ($response->getStatusCode()===200) {
                        $data = $response->getBody();
                        file_put_contents($fileName, $data);
                    }
                }
                $css = str_replace($uri, $replaceUri . basename($uri), $css);
            }
        }
        return $css;
    }

    public function run()
    {

        if (!empty($this->filename)) {
                $this->setHeader();
                echo file_get_contents($this->filename);
                exit;
        }

    }

    private function setHeader($headerType='text/css')
    {
        header('Content-type: ' . $headerType);
    }
    /**
     * Create dir with change permission
     *
     * @param string $dir
     * @param int $perm
     *
     * @return bool
     */
    private function mkdir(string $dir, int $perm): bool
    {
        if (!is_dir($dir) && mkdir($dir, $perm, true)) {
            chmod($dir, $perm);
        }
        return is_dir($dir);
    }
}
