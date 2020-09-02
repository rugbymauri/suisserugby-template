<?php

namespace MaurizioMonticelli\SuisseRugby\Command;


use mysqli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateImageLinksCommand extends Command
{
    /** @var Config */

    private $config;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->config = new Config();
        $this->updateNewsMedia();
        $this->config->close();
    }

    private function updateNewsMedia()
    {
        $resultPreviewMedia = $this->config->connTarget->query('select * from sys_file_reference where link > 0;');

        if ($resultPreviewMedia->num_rows > 0) {
            // output data of each row
            while ($row = $resultPreviewMedia->fetch_assoc()) {

                $linkId = (int) $row['link'];
                echo $row['link'] . ':' . $linkId . PHP_EOL;
                if ($linkId) {

                    $newPAgeId = 0;

                    $resultFileID = $this->config->connTarget->query('select uid from pages where old_uid = ' . $linkId);
                    if ($resultFileID->num_rows > 0) {
                        while ($rowUid = $resultFileID->fetch_assoc()) {
                            $newPAgeId = $rowUid['uid'];
                            break;
                        }
                    }

                    if ($newPAgeId === 0 ) {
                        continue;
                    }

                    $update = "update sys_file_reference set link = '" . $newPAgeId . "' where uid = " . $row['uid'];

                    if ($this->config->connTarget->query($update) === TRUE) {
                        echo '.';
                    } else {
                        echo "Error: " . $this->config->connTarget->error . PHP_EOL;
                        echo "sql: " . $update . PHP_EOL;
                    }


                }
            }
        }
    }
}
