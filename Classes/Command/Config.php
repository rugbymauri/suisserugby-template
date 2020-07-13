<?php


namespace MaurizioMonticelli\SuisseRugby\Command;


class Config
{
    public array $skipPages =[
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

    public array $pageMap = [ 1 => 1];
    public mysqli $connSource;
    public mysqli $connTarget;

    public function __construct()
    {
        $this->connSource = new mysqli("127.0.0.2", "root", "root", 'suisserugby');
        if ($this->connSource->connect_error) {
            die("Connection failed: " . $this->connSource->connect_error);
        }
        echo "Connected successfully";
        $this->connTarget = new mysqli("127.0.0.1", "root", "root", 'suisserugby_new');
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
