<?php


namespace MaurizioMonticelli\SuisseRugby\Command;


use mysqli;

class Config
{
    public $skipPages = [
        1,
        12,
        129,
        13,
        62,
        64,
        67,
        65,
        11,
        128,

    ];

    public $pageMap = [ 1 => 1];
    public $contentMap = [];
    public $fileMap = [];
    public $connSource;
    public $connTarget;

    public function __construct()
    {
        $this->connSource = new mysqli("127.0.0.2", "root", "root", 'suisserugby');
        $this->connSource->set_charset("utf8");
        if ($this->connSource->connect_error) {
            die("Connection failed: " . $this->connSource->connect_error);
        }
        echo "Connected successfully";
        $this->connTarget = new mysqli("127.0.0.1", "root", "root", 'suisserugby_new');
        $this->connTarget->character_set_name();
        $this->connTarget->set_charset("utf8");
        if ($this->connTarget->connect_error) {
            die("Connection failed: " . $this->connTarget->connect_error);
        }
        echo "Connected successfully";
    }

    public function close()
    {
        $this->connSource->close();
        $this->connTarget->close();
    }
}
