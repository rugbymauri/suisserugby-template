<?php

namespace MaurizioMonticelli\SuisseRugby\Command;


use mysqli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
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
        $resultPreviewMedia = $this->config->connSource->query('select * from tx_news_domain_model_media where showinpreview = 1');

        if ($resultPreviewMedia->num_rows > 0) {
            // output data of each row
            while ($rowPreviewMedia = $resultPreviewMedia->fetch_assoc()) {

                if ($rowPreviewMedia['image']) {
                    $oldImageUrl = $rowPreviewMedia['image'];

                    $oldUid = 0;
                    $sql = "select uid from sys_file where identifier = '/uploads/tx_news/" . $oldImageUrl . "'";
                    $resultOldUId = $this->config->connSource->query($sql);
                    if ($resultOldUId->num_rows > 0) {
                        while ($rowUid = $resultOldUId->fetch_assoc()) {
                            $oldUid = $rowUid['uid'];
                            break;
                        }
                    }

                    if ($oldUid === 0) {
                        continue;
                    }

                    $newFileId = 0;

                    $resultFileID = $this->config->connTarget->query('select uid from sys_file where old_uid = ' . $oldUid);
                    if ($resultFileID->num_rows > 0) {
                        while ($rowUid = $resultFileID->fetch_assoc()) {
                            $newFileId = $rowUid['uid'];
                            break;
                        }
                    }

                    if ($newFileId === 0 ) {
                        continue;
                    }

                    $update = 'update sys_file_reference set showinpreview = 1 where uid_local = ' . $newFileId;
                    $this->config->connTarget->query($update);

                }
            }
        }
    }
}
