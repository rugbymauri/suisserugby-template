<?php

namespace MaurizioMonticelli\SuisseRugby\Command;


use mysqli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = new Config();


        $this->importFiles($config);
        $this->importPages($config);
        $this->importContent($config);

        $config->close();

    }

    private function importFiles(Config $c): void
    {

        $result = $c->connSource->query("SELECT * FROM sys_file  order by pid");

        $pageOldUid = $c->connTarget->query('SHOW COLUMNS FROM `sys_file` LIKE \'old_uid\'');
        if ($pageOldUid->num_rows === 0) {
            $c->connTarget->query('ALTER TABLE `sys_file` ADD COLUMN `old_uid` INT(11)');
        }


        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {

                // check files exists

                $file = null;
                switch ($row['storage']) {
                    case 0;
                        $file = '/home/mauri/PhpstormProjects/suisserugby-new/public' . $row['identifier'];
                        break;
                    case 1;
                        $file = '/home/mauri/PhpstormProjects/suisserugby-new/public/fileadmin' . $row['identifier'];
                        break;

                }
                if (!file_exists($file)) {
                    echo 'missing file' . $file . PHP_EOL;
                    continue;
                }


                $resultExisting = $c->connTarget->query("SELECT * FROM sys_file where identifier = '" . $row['identifier'] . "'");
                if ($resultExisting->num_rows > 0) {
                    $existingRow = $resultExisting->fetch_assoc();
                    $sql = 'update sys_file set old_uid = ' . $row['uid'] . ' where uid = ' . $existingRow['uid'];
                    $c->fileMap[$row['uid']] = $existingRow['uid'];

                    if ($c->connTarget->query($sql) === TRUE) {
                        echo '';
                    } else {
                        echo "Error: " . $c->connTarget->error . PHP_EOL;
                        echo "sql: " . $sql . PHP_EOL;
                    }
                }

                $insertData = $row;
                $insertData['old_uid'] = $row['uid'];
                unset($insertData['uid']);
                unset($insertData['crdate']);
                unset($insertData['cruser_id']);
                unset($insertData['t3ver_oid']);
                unset($insertData['t3ver_id']);
                unset($insertData['t3ver_wsid']);
                unset($insertData['t3ver_label']);
                unset($insertData['t3ver_state']);
                unset($insertData['t3ver_stage']);
                unset($insertData['t3ver_count']);
                unset($insertData['t3ver_tstamp']);
                unset($insertData['t3ver_move_id']);
                unset($insertData['t3_origuid']);
                unset($insertData['title']);
                unset($insertData['width']);
                unset($insertData['height']);
                unset($insertData['description']);
                unset($insertData['alternative']);


                $sql = $this->makeInsert($insertData, 'sys_file', $c->connTarget);


                if ($c->connTarget->query($sql) === TRUE) {
                    echo '';
                    $c->fileMap[$row['uid']] = $c->connTarget->insert_id;
                } else {
                    echo "Error: " . $c->connTarget->error . PHP_EOL;
                    echo "sql: " . $sql . PHP_EOL;
                }

            }
        } else {
            echo "0 results";
        }
    }

    private function importPages(Config $c): void
    {
        $result = $c->connSource->query("SELECT page_id, language_id, pagepath FROM tx_realurl_pathcache");

        $slugs = [];

        while ($row = $result->fetch_assoc()) {
            $slugs[$row['page_id']][$row['language_id']] = $row['pagepath'];
        }

        $result = $c->connSource->query("SELECT * FROM pages_language_overlay");

        $pageTransalations = [];

        while ($row = $result->fetch_assoc()) {
            $pageTransalations[$row['pid']][$row['sys_language_uid']] = $row;
        }


        $result = $c->connSource->query("SELECT * FROM pages where deleted = 0 order by pid");

        $pageOldUid = $c->connTarget->query('SHOW COLUMNS FROM `pages` LIKE \'old_uid\'');
        if ($pageOldUid->num_rows === 0) {
            $c->connTarget->query('ALTER TABLE `suisserugby_new`.`pages` ADD COLUMN `old_uid` INT(11) NULL AFTER `thumbnail`');
        }


        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                if (!isset($c->pageMap[$row['pid']])) {
                    continue;
                }

                if (in_array($row['uid'], $c->skipPages)) {
                    continue;
                }

                echo "uid: " . $row["uid"] .
                    " - pid: " . $row["pid"] .
                    " - title: " . $row["title"] .
                    PHP_EOL;


                $insertData = [];

                $insertData['old_uid'] = $row['uid'];
                $insertData['crdate'] = $row['crdate'];
                $insertData['tstamp'] = $row['tstamp'];
                $insertData['cruser_id'] = 1;
                $insertData['deleted'] = 0;
                $insertData['hidden'] = $row['hidden'];
                $insertData['starttime'] = $row['starttime'];
                $insertData['endtime'] = $row['endtime'];
                $insertData['sorting'] = $row['sorting'];
                $insertData['editlock'] = $row['editlock'];
                $insertData['sys_language_uid'] = 0;
                $insertData['l10n_parent'] = 0;
                $insertData['l10n_source'] = 0;
                $insertData['l10n_state'] = null;
                $insertData['t3_origuid'] = $row['t3_origuid'];
                $insertData['t3ver_oid'] = $row['t3ver_oid'];
                $insertData['t3ver_wsid'] = $row['t3ver_wsid'];
                $insertData['t3ver_state'] = $row['t3ver_state'];
                $insertData['t3ver_stage'] = $row['t3ver_stage'];
                $insertData['t3ver_count'] = $row['t3ver_count'];
                $insertData['t3ver_tstamp'] = $row['t3ver_tstamp'];
                $insertData['t3ver_move_id'] = $row['t3ver_move_id'];
                $insertData['perms_userid'] = 1;
                $insertData['perms_groupid'] = 1;
                $insertData['perms_user'] = 31;
                $insertData['perms_group'] = 31;
                $insertData['perms_everybody'] = 0;
                $insertData['title'] = $row['title'];
                $insertData['doktype'] = $row['doktype'];
                $insertData['TSconfig'] = $row['TSconfig'];
                $insertData['is_siteroot'] = $row['is_siteroot'];
                $insertData['php_tree_stop'] = $row['php_tree_stop'];
                $insertData['url'] = $row['url'];
                $insertData['shortcut'] = $row['shortcut'];
                $insertData['shortcut_mode'] = $row['shortcut_mode'];
                $insertData['subtitle'] = $row['subtitle'];
                $insertData['layout'] = $row['layout'];
                $insertData['target'] = $row['target'];
                if ($row['media'] === null) {
                    $row['media'] = 0;
                }
                $insertData['media'] = $row['media'];
                $insertData['lastUpdated'] = $row['lastUpdated'];
                $insertData['keywords'] = $row['keywords'];
                $insertData['cache_timeout'] = $row['cache_timeout'];
                $insertData['cache_tags'] = $row['cache_tags'];
                $insertData['newUntil'] = $row['newUntil'];
                $insertData['description'] = $row['description'];
                $insertData['no_search'] = $row['no_search'];
                $insertData['SYS_LASTCHANGED'] = $row['SYS_LASTCHANGED'];
                $insertData['abstract'] = $row['abstract'];
                $insertData['module'] = $row['module'];
                $insertData['extendToSubpages'] = $row['extendToSubpages'];
                $insertData['author'] = $row['author'];
                $insertData['author_email'] = $row['author_email'];
                $insertData['nav_title'] = $row['nav_title'];
                $insertData['nav_hide'] = $row['nav_hide'];
                $insertData['content_from_pid'] = $row['content_from_pid'];
                $insertData['mount_pid'] = $row['mount_pid'];
                $insertData['mount_pid_ol'] = $row['mount_pid_ol'];
                $insertData['l18n_cfg'] = $row['l18n_cfg'];
                $insertData['fe_login_mode'] = $row['fe_login_mode'];
                $insertData['backend_layout'] = $row['backend_layout'];
                $insertData['backend_layout_next_level'] = $row['backend_layout_next_level'];
                $insertData['tx_impexp_origuid'] = $row['tx_impexp_origuid'];
                $insertData['pid'] = $c->pageMap[$row['pid']];
                if (isset($slugs[$row['uid']][0])) {
                    $insertData['slug'] = $slugs[$row['uid']][0];
                } elseif (isset($slugs[$row['uid']][1])) {
                    $insertData['slug'] = $slugs[$row['uid']][1];
                }


                $sql = $this->makeInsert($insertData, 'pages', $c->connTarget);

                if ($c->connTarget->query($sql) === TRUE) {
                    $c->pageMap[$row['uid']] = $c->connTarget->insert_id;

                    if (!isset($pageTransalations[$row['uid']])) {
                        continue;
                    }
                    foreach ($pageTransalations[$row['uid']] as $language => $transalation) {

                        echo 'Translate: ' . $language . PHP_EOL;
                        foreach ($transalation as $key => $value) {
                            if (isset($insertData[$key])) {
                                if ($key === 'uid') {
                                    continue;
                                }
                                if ($key === 'pid') {
                                    continue;
                                }
                                if ($key === 'cruser_id') {
                                    continue;
                                }
                                if ($key === 'media') {
                                    if ($value === null) {
                                        $value = 0;
                                    }
                                }
                                $insertData[$key] = $value;
                            }
                        }

                        $insertData['sys_language_uid'] = $language;
                        $insertData['l10n_parent'] = $c->pageMap[$row['uid']];
                        $insertData['l10n_source'] = $c->pageMap[$row['uid']];

                        $insert = [];
                        foreach ($insertData as $data) {
                            $insert[] = "'" . $c->connTarget->real_escape_string($data) . "'";
                        }
                        $sql =
                            'INSERT INTO pages (' . implode(', ', array_keys($insertData)) .
                            ') VALUES (' . implode(', ', $insert) . ')';


                        if ($c->connTarget->query($sql) === FALSE) {
                            echo "Error Translations: " . $sql . PHP_EOL;
                        }
                    }


                } else {
                    echo "Error: " . $sql . PHP_EOL;
                }

            }
        } else {
            echo "0 results";
        }
    }

    private function importContent(Config $c): void
    {

        $skippedContentType = [
            'div' => true,
            'media' => true,
        ];

        $result = $c->connSource->query("SELECT * FROM tt_content where deleted = 0 order by pid");

        $pageOldUid = $c->connTarget->query('SHOW COLUMNS FROM `tt_content` LIKE \'old_uid\'');
        if ($pageOldUid->num_rows === 0) {
            $c->connTarget->query('ALTER TABLE `tt_content` ADD COLUMN `old_uid` INT(11)');
        }


        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {

                if (!isset($c->pageMap[$row['pid']])) {
                    continue;
                }

                if (
                    $row['CType'] !== 'text' &&
                    $row['CType'] !== 'table' &&
                    $row['CType'] !== 'header' &&
                    $row['CType'] !== 'menu' &&
                    $row['CType'] !== 'uploads' &&
                    $row['CType'] !== 'textpic' &&
                    $row['CType'] !== 'image' &&
                    $row['CType'] !== 'html'
                ) {
                    if (!isset($skippedContentType[$row['CType']])) {
                        $skippedContentType[$row['CType']] = true;
                        echo 'skip ' . $row['CType'] . PHP_EOL;
                    }
                    continue;
                }

                if ($row['sys_language_uid'] != 0) {
                    continue;
                }


                if ($row['CType'] == 'menu') {
                    if ($row['menu_type'] == '1') {
                        $row['CType'] = 'menu_subpages';
                    } else {
                        echo 'menu type ' . $row['menu_type'] . ' not defined!';
                    }
                }

                $insertData = $row;
                unset($insertData['uid']);
                unset($insertData['t3ver_id']);
                unset($insertData['t3ver_label']);
                unset($insertData['imagecaption']);


                unset($insertData['spaceBefore']);
                unset($insertData['spaceAfter']);
                unset($insertData['imagecaption_position']);
                unset($insertData['image_link']);
                unset($insertData['image_noRows']);
                unset($insertData['image_effects']);
                unset($insertData['image_compression']);
                unset($insertData['altText']);
                unset($insertData['titleText']);
                unset($insertData['longdescURL']);
                unset($insertData['text_align']);
                unset($insertData['text_face']);
                unset($insertData['text_size']);
                unset($insertData['text_color']);
                unset($insertData['text_properties']);
                unset($insertData['menu_type']);
                unset($insertData['table_border']);
                unset($insertData['table_cellspacing']);
                unset($insertData['table_cellpadding']);
                unset($insertData['table_bgColor']);
                unset($insertData['section_frame']);
                unset($insertData['select_key']);
                unset($insertData['multimedia']);
                unset($insertData['image_frames']);
                unset($insertData['multimedia']);
                unset($insertData['rte_enabled']);
                unset($insertData['backupColPos']);
                unset($insertData['tx_gridelements_backend_layout']);
                unset($insertData['tx_gridelements_children']);
                unset($insertData['tx_gridelements_container']);
                unset($insertData['tx_gridelements_columns']);

                if ($insertData['image'] === null) {
                    $insertData['image'] = 0;
                }
                if ($insertData['media'] === null) {
                    $insertData['media'] = 0;
                }
                if ($insertData['colPos'] == -1) {
                    $insertData['colPos'] = 0;
                }

                $insertData['pid'] = $c->pageMap[$row['pid']];
                $insertData['old_uid'] = $row['pid'];
                $insertData['cruser_id'] = 1;
                $insertData['header_layout'] = 0;
                $insertData['l18n_diffsource'] = '';
                $insertData['background_color_class'] = 'none';
                $insertData['table_delimiter'] = 124;
                $insertData['icon_color'] = '#FFFFFF';
                $insertData['icon_background'] = '#333333';
                $insertData['background_image_options'] = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="parallax">
                    <value index="vDEF">0</value>
                </field>
                <field index="fade">
                    <value index="vDEF">0</value>
                </field>
                <field index="filter">
                    <value index="vDEF"></value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

                $sql = $this->makeInsert($insertData, 'tt_content', $c->connTarget);


                if ($c->connTarget->query($sql) === TRUE) {
                    echo '';
                    $c->contentMap[$row['uid']] = $c->connTarget->insert_id;

                } else {
                    echo "Error: " . $c->connTarget->error . PHP_EOL;
                    echo "sql: " . $sql . PHP_EOL;
                }

                $this->importFileRelation($row['uid'], $row['pid'], $c);
            }
        } else {
            echo "0 results";
        }
    }

    private function importFileRelation($content_uid, $page_uid, Config $c)
    {

        $result = $c->connSource->query("SELECT * FROM sys_file_reference where pid = " . $page_uid . " and uid_foreign = " . $content_uid . " and deleted = 0");

        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {

                if (!isset($c->fileMap[$row['uid_local']])) {
                    continue;
                }

                $insertData = $row;
                unset($insertData['uid']);
                unset($insertData['sorting']);
                unset($insertData['t3ver_id']);
                unset($insertData['t3ver_label']);
                unset($insertData['t3_origuid']);
                unset($insertData['downloadname']);
                unset($insertData['sorting']);
                unset($insertData['sorting']);
                if ($insertData['link'] == null) {
                    $insertData['link'] = '';
                }

                $insertData['cruser_id'] = 1;
                $insertData['uid_local'] = $c->fileMap[$insertData['uid_local']];
                $insertData['pid'] = $c->pageMap[$insertData['pid']];
                $insertData['uid_foreign'] = $c->contentMap[$insertData['uid_foreign']];

                $sql = $this->makeInsert($insertData, 'sys_file_reference', $c->connTarget);

                if ($c->connTarget->query($sql) === TRUE) {
                    echo '';
                } else {
                    echo "Error: " . $c->connTarget->error . PHP_EOL;
                    echo "sql: " . $sql . PHP_EOL;
                }
            }
        }

    }

    private function makeInsert(?array $insertData, string $table, mysqli $connTarget): string
    {
        $values = [];
        foreach ($insertData as $data) {
            if ($data === null) {
                $values[] = "null";
            } else {
                $values[] = "'" . $connTarget->real_escape_string($data) . "'";
            }
        }

        $fields = [];
        foreach ($insertData as $key => $data) {
            $fields[] = '`' . $key . '`';
        }

        $sql =
            'INSERT INTO `' . $table . '` (' . implode(', ', $fields) .
            ') VALUES (' . implode(', ', $values) . ')';

        return $sql;
    }

}
