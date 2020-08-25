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
    public $newsMap = [];
    public $categoryMap = [];
    public $connSource;
    public $connTarget;
    public $baseDir;

    public function __construct()
    {
        require 'config.php';

        /** @var $config */
        $this->baseDir = $config['baseDir'];


        $this->connSource = new mysqli($config['source']['host'], $config['source']['user'], $config['source']['pwd'], $config['source']['db']);
        $this->connSource->set_charset("utf8");
        if ($this->connSource->connect_error) {
            die("Connection failed: " . $this->connSource->connect_error);
        }
        echo "Connected successfully";
        $this->connTarget = new mysqli($config['target']['host'], $config['target']['user'], $config['target']['pwd'], $config['target']['db']);
        $this->connTarget->character_set_name();
        $this->connTarget->set_charset("utf8");
        if ($this->connTarget->connect_error) {
            die("Connection failed: " . $this->connTarget->connect_error);
        }
        echo "Connected successfully";



        $this->loadCategoryMap();
    }

    public function close()
    {
        $this->connSource->close();
        $this->connTarget->close();
    }

    private function loadCategoryMap()
    {
        $result = $this->connTarget->query("SELECT uid, old_uid FROM sys_category");


        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                $this->categoryMap[$row['old_uid']] = $row['uid'];
            }
        }
    }
}
