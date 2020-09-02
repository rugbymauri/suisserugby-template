<?php

namespace MaurizioMonticelli\SuisseRugby\Command;


use mysqli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateImageOrientCommand extends Command
{
    /** @var Config */

    private $config;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->config = new Config();
        $this->update();
        $this->config->close();
    }

    private function update()
    {
        $this->config->connTarget->query("UPDATE tt_content set  imageorient = 8 where imageorient = 0  and CType = 'textpic'");
    }
}
