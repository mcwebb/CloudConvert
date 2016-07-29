<?php
/*
 * Part of mcwebb/CloudConvert
 * 2014 Matthew Webb <matthewowebb@gmail.com>
 * MIT License
 *
 * Based on Lunaweb's API Example Class
 * 2013 by Lunaweb Ltd.
 * Feel free to use, modify or publish it.
 */
namespace Mcwebb\CloudConvert;

/**
 * Class Process
 * @package Mcwebb\CloudConvert
 */
class Process
{
    /**
     * @const string API endpoint
     */
    const API_PROCESS_URL = 'https://api.cloudconvert.com/process';

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string format of the input file
     */
    private $inputFormat;

    /**
     * @var string format of the output file to convert to
     */
    private $outputFormat;

    /**
     * @var
     */
    private $data;

    /**
     * @var array
     */
    private $options = array();

    /**
     * creates new Process ID.
     * see: https://cloudconvert.com/api#start
     *
     * @param $inputFormat
     * @param $outputFormat
     * @param User $user
     */
    public function __construct($inputFormat, $outputFormat, User $user)
    {
        $this->apiKey = $user->getApiKey();
        $this->inputFormat = $inputFormat;
        $this->outputFormat = $outputFormat;

        $data = $this->req(self::API_PROCESS_URL, array(
            'inputformat' => $inputFormat,
            'outputformat' => $outputFormat,
            'apikey' => $this->apiKey
        ));

        if (strpos($data->url, 'http') === false) {
            $data->url = "https:" . $data->url;
        }
        $this->url = $data->url;

        return $this;
    }

    /**
     * /*
     * Set conversion option.
     * examples:
     * $converter->setOption('email', '1');
     * $converter->setOption('options[audio_bitrate]', '128');
     *
     * @param string $name
     * @param mixed $val
     */
    public function setOption($name, $val)
    {
        $this->options[$name] = $val;
    }

    /**
     * Uploads the input file (server side)
     * @param string $filePath
     */
    public function upload($filePath)
    {
        $this->req($this->url, array_merge(array(
            'input' => 'upload',
            'format' => $this->outputFormat,
            'filename' => 'input.' . $this->inputFormat,
            'file' => (class_exists('CURLFile') ? new \CURLFile($filePath) : '@' . $filePath)
        ), $this->options));
    }

    /**
     * Let CloudConvert download the input file by a given URL and filename
     * @param string $url
     * @param string $filename
     * @param string $outputFormat
     */
    public function uploadByURL($url, $filename, $outputFormat)
    {
        $this->req($this->url, array_merge(array(
            'input' => 'download',
            'format' => $outputFormat,
            'filename' => $filename,
            'link' => $url,
        ), $this->options));
    }

    /**
     * Returns Process URL
     * @return string
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * Checks the current status of the process
     * @param null $action
     * @return array|object
     * @throws \Exception
     */
    public function status($action = null)
    {
        if (empty($this->url)) {
            throw new \Exception("No process URL found! (Conversion not started)");
        }
        $this->data = $this->req($this->url . ($action ? '/' . $action : ''));

        return $this->data;
    }

    /**
     * @return array|object
     */
    public function cancel()
    {
        return $this->status('cancel');
    }

    /**
     * @return array|object
     */
    public function delete()
    {
        return $this->status('delete');
    }

    /**
     * Blocks until the conversion is finished
     * @param int $timeout
     * @return bool
     * @throws \Exception
     */
    public function waitForConversion($timeout = 120)
    {
        $time = 0;

        // Check the status every second, up to timeout
        while ($time <= $timeout) {
            sleep(1);
            $time++;
            $data = $this->status();

            if ($data->step == 'error') {
                throw new \Exception ($data->message);
            } elseif ($data->step == 'finished' && isset($data->output) && isset($data->output->url)) {
                return true;
            }
        }
        throw new \Exception('Timeout');
    }

    /**
     * Download output file to local target
     * @param string $target
     * @throws \Exception
     */
    public function download($target)
    {
        if (empty($this->data->output->url)) {
            throw new \Exception("No download URL found! (Conversion not finished or failed)");
        }

        if (strpos($this->data->output->url, 'http') === false) {
            $this->data->output->url = "https:" . $this->data->output->url;
        }

        $fp = fopen($target, 'w+');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->data->output->url);
        curl_setopt($ch, CURLOPT_FILE, $fp);

        if (!curl_exec($ch)) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);
        fclose($fp);
    }

    /**
     * Return output stream to variable
     * @return string|boolean
     * @throws \Exception
     */
    public function downloadStream()
    {
        if (empty($this->data->output->url)) {
            throw new \Exception("No download URL found! (Conversion not finished or failed)");
        }
        if (strpos($this->data->output->url, 'http') === false) {
            $this->data->output->url = "https:" . $this->data->output->url;
        }

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_URL => $this->data->output->url
        ));

        $stream = curl_exec($ch);
        curl_close($ch);

        return $stream;
    }


    /**
     * @param string $url
     * @param null|array $post
     * @return array|object
     * @throws \Exception
     */
    private function req($url, $post = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);

        if (!empty($post)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        $return = curl_exec($ch);

        if ($return === false) {
            throw new \Exception(curl_error($ch));
        } else {
            $json = json_decode($return);
            if (isset($json->error)) {
                throw new \Exception($json->error);
            }
            curl_close($ch);

            return $json;
        }
    }
}
