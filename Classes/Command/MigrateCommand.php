<?php

namespace MaurizioMonticelli\SuisseRugby\Command;


use mysqli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{

    /** @var Config */

    private $config;

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->config = new Config();
        $this->importCategory();
        $this->importFiles();
        $this->importPages();
        $this->importContent();
        $this->importNews();

        $this->config->close();

    }
    private function importCategory (): void
    {
        $result = $this->config->connSource->query("SELECT * FROM tx_news_domain_model_category  order by uid");
    }
    private function importNews(): void
    {
        $newsPid = 25;
        $eventPid = 26;


        $result = $this->config->connSource->query("SELECT * FROM tx_news_domain_model_news  order by uid");

        $pageOldUid = $this->config->connTarget->query('SHOW COLUMNS FROM `tx_news_domain_model_news` LIKE \'old_uid\'');
        if ($pageOldUid->num_rows === 0) {
            $this->config->connTarget->query('ALTER TABLE `tx_news_domain_model_news` ADD COLUMN `old_uid` INT(11)');
        }


        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {


                $insertData = $row;
                $insertData['pid'] = $newsPid;
                $insertData['old_uid'] = $insertData['uid'];
                if ($insertData['t3_origuid'] && isset($this->config->newsMap[$insertData['t3_origuid']])) {
                    $insertData['t3_origuid'] = $this->config->newsMap[$insertData['t3_origuid']];
                }
                if ($insertData['l10n_parent'] && isset($this->config->newsMap[$insertData['l10n_parent']])) {
                    $insertData['l10n_parent'] = $this->config->newsMap[$insertData['l10n_parent']];
                }

                if ($row['tx_roqnewsevent_is_event'] == 1) {
                    $insertData['pid'] = $eventPid;
                }

                unset($insertData['uid']);
                unset($insertData['rte_disabled']);
                unset($insertData['is_dummy_record']);
                unset($insertData['tx_roqnewsevent_is_event']);
                unset($insertData['tx_roqnewsevent_startdate']);
                unset($insertData['tx_roqnewsevent_starttime']);
                unset($insertData['tx_roqnewsevent_enddate']);
                unset($insertData['tx_roqnewsevent_endtime']);
                unset($insertData['tx_roqnewsevent_location']);
                $insertData['content_elements'] = 0;

                if (isset($insertData['sys_language_uid'])) {
                    $insertData['sys_language_uid'] = $this->getSysLangId($insertData['sys_language_uid']);
                }

                if ($row['tx_roqnewsevent_is_event'] == 1) {
                    $insertData['pid'] = $eventPid;
                    $insertData['is_event'] = 1;
                    $insertData['datetime'] = 1;
                    $insertData['event_end'] = 1;
                    $insertData['organizer_simple'] = 1;
                    $insertData['location_simple'] = 1;

                    continue;
                }

                $sql = $this->makeInsert($insertData, 'tx_news_domain_model_news', $this->config->connTarget);


                if ($this->config->connTarget->query($sql) === TRUE) {
                    echo '';
                    $this->config->newsMap[$row['uid']] = $this->config->connTarget->insert_id;


                    $uid = $row['uid'];
                    $this->importNewsMedia($uid, $insertData);
                    $this->importNewsCategory($uid, $insertData);


                } else {
                    echo "Error: " . $this->config->connTarget->error . PHP_EOL;
                    echo "sql: " . $sql . PHP_EOL;
                }

//                $this->importFileRelation($row['uid'], $row['pid']);
            }
        } else {
            echo "0 results";
        }
    }


    private function importFiles(): void
    {

        $result = $this->config->connSource->query("SELECT * FROM sys_file  order by pid");

        $pageOldUid = $this->config->connTarget->query('SHOW COLUMNS FROM `sys_file` LIKE \'old_uid\'');
        if ($pageOldUid->num_rows === 0) {
            $this->config->connTarget->query('ALTER TABLE `sys_file` ADD COLUMN `old_uid` INT(11)');
        }


        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {

                // check files exists

                $file = null;
                switch ($row['storage']) {
                    case 0;
                        $file = $this->config->baseDir . '/public' . $row['identifier'];
                        break;
                    case 1;
                        $file = $this->config->baseDir .'/public/fileadmin' . $row['identifier'];
                        break;

                }
                if (!file_exists($file)) {
//                    echo 'missing file ' . $file . PHP_EOL;
                    continue;
                }


                $resultExisting = $this->config->connTarget->query("SELECT * FROM sys_file where identifier = '" . $row['identifier'] . "'");
                if ($resultExisting->num_rows > 0) {
                    $existingRow = $resultExisting->fetch_assoc();
                    $sql = 'update sys_file set old_uid = ' . $row['uid'] . ' where uid = ' . $existingRow['uid'];
                    $this->config->fileMap[$row['uid']] = $existingRow['uid'];

                    if ($this->config->connTarget->query($sql) === TRUE) {
                        echo '';
                    } else {
                        echo "Error: " . $this->config->connTarget->error . PHP_EOL;
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

                if (isset($insertData['sys_language_uid'])) {
                    $insertData['sys_language_uid'] = $this->getSysLangId($insertData['sys_language_uid']);
                }

                $sql = $this->makeInsert($insertData, 'sys_file', $this->config->connTarget);

                if ($this->config->connTarget->query($sql) === TRUE) {
                    echo '';
                    $this->config->fileMap[$row['uid']] = $this->config->connTarget->insert_id;
                    $insertData['uid'] = $this->config->connTarget->insert_id;

                    $this->importFileMetadata($row, $insertData['uid']);
                } else {
                    echo "Error: " . $this->config->connTarget->error . PHP_EOL;
                    echo "sql: " . $sql . PHP_EOL;
                }
            }
        } else {
            echo "0 results";
        }
    }

    private function importFileMetadata(?array $insertData, $uid)
    {
        $metaData['pid'] = 0;
        $metaData['tstamp'] = time();
        $metaData['crdate'] = time();
        $metaData['cruser_id'] = 1;
        $metaData['sys_language_uid'] = 0;
        $metaData['l10n_parent'] = 0;
        $metaData['l10n_state'] = null;
        $metaData['t3_origuid'] = 0;
        $metaData['l10n_diffsource'] = 0;
        $metaData['t3ver_oid'] = 0;
        $metaData['t3ver_wsid'] = 0;
        $metaData['t3ver_state'] = 0;
        $metaData['t3ver_stage'] = 0;
        $metaData['t3ver_count'] = 0;
        $metaData['t3ver_tstamp'] = 0;
        $metaData['t3ver_move_id'] = 0;
        $metaData['file'] = $uid;
        $metaData['title'] = $insertData['title'];
        $metaData['width'] = $insertData['width'];
        $metaData['height'] = $insertData['height'];
        $metaData['description'] = $insertData['description'];
        $metaData['alternative'] = $insertData['alternative'];
        $metaData['categories'] = 0;

        if (isset($metaData['sys_language_uid'])) {
            $metaData['sys_language_uid'] = $this->getSysLangId($metaData['sys_language_uid']);
        }

        $sql = $this->makeInsert($metaData, 'sys_file_metadata', $this->config->connTarget);

        if ($this->config->connTarget->query($sql) === TRUE) {
            echo '';
        } else {
            echo "Error: " . $this->config->connTarget->error . PHP_EOL;
            echo "sql: " . $sql . PHP_EOL;
        }

    }

    private function importPages(): void
    {
        $result = $this->config->connSource->query("SELECT page_id, language_id, pagepath FROM tx_realurl_pathcache");
        $slugs = [];

        while ($row = $result->fetch_assoc()) {
            $slugs[$row['page_id']][$row['language_id']] = $row['pagepath'];
        }

        $result = $this->config->connSource->query("SELECT * FROM pages_language_overlay");
        $pageTransalations = [];

        while ($row = $result->fetch_assoc()) {
            $pageTransalations[$row['pid']][$row['sys_language_uid']] = $row;
        }

        $result = $this->config->connSource->query("SELECT * FROM pages where deleted = 0 order by pid");

        $pageOldUid = $this->config->connTarget->query('SHOW COLUMNS FROM `pages` LIKE \'old_uid\'');
        if ($pageOldUid->num_rows === 0) {
            $this->config->connTarget->query('ALTER TABLE `pages` ADD COLUMN `old_uid` INT(11) NULL AFTER `thumbnail`');
        }


        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                if (!isset($this->config->pageMap[$row['pid']])) {
                    continue;
                }

                if (in_array($row['uid'], $this->config->skipPages)) {
                    continue;
                }


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
                $insertData['pid'] = $this->config->pageMap[$row['pid']];
                if (isset($slugs[$row['uid']][0])) {
                    $insertData['slug'] = $slugs[$row['uid']][0];
                } elseif (isset($slugs[$row['uid']][1])) {
                    $insertData['slug'] = $slugs[$row['uid']][1];
                }

                if (isset($insertData['sys_language_uid'])) {
                    $insertData['sys_language_uid'] = $this->getSysLangId($insertData['sys_language_uid']);
                }

                $sql = $this->makeInsert($insertData, 'pages', $this->config->connTarget);

                if ($this->config->connTarget->query($sql) === TRUE) {
                    $this->config->pageMap[$row['uid']] = $this->config->connTarget->insert_id;

                    if (!isset($pageTransalations[$row['uid']])) {
                        continue;
                    }
                    foreach ($pageTransalations[$row['uid']] as $language => $transalation) {

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
                        $insertData['l10n_parent'] = $this->config->pageMap[$row['uid']];
                        $insertData['l10n_source'] = $this->config->pageMap[$row['uid']];

                        if (isset($insertData['sys_language_uid'])) {
                            $insertData['sys_language_uid'] = $this->getSysLangId($language);
                        }

                        $insert = [];
                        foreach ($insertData as $data) {
                            $insert[] = "'" . $this->config->connTarget->real_escape_string($data) . "'";
                        }
                        $sql =
                            'INSERT INTO pages (' . implode(', ', array_keys($insertData)) .
                            ') VALUES (' . implode(', ', $insert) . ')';


                        if ($this->config->connTarget->query($sql) === true) {
//                            $this->config->pageMap[$row['uid']] = $this->config->connTarget->insert_id;
                        } else {
                            echo "Error Translations: " . $sql . PHP_EOL;
                        }
                    }


                } else {
                    echo "Error: " . $this->config->connTarget->error . PHP_EOL;
                    echo "sql: " . $sql . PHP_EOL;
                }

            }
        } else {
            echo "0 results";
        }
    }

    private function importContent(): void
    {
        $skippedContentType = [
            'div' => true,
            'media' => true,
        ];

        $result = $this->config->connSource->query("SELECT * FROM tt_content where deleted = 0 order by pid");

        $pageOldUid = $this->config->connTarget->query('SHOW COLUMNS FROM `tt_content` LIKE \'old_uid\'');
        if ($pageOldUid->num_rows === 0) {
            $this->config->connTarget->query('ALTER TABLE `tt_content` ADD COLUMN `old_uid` INT(11)');
        }


        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {

                if (!isset($this->config->pageMap[$row['pid']])) {
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
                if (is_numeric($insertData['header_link'])) {
                    $insertData['header_link'] = $this->config->pageMap[$insertData['header_link']];
                }

                if ($insertData['t3_origuid']) {
                    if (isset($this->config->contentMap[$insertData['t3_origuid']])) {
                        $insertData['t3_origuid'] = $this->config->contentMap[$insertData['t3_origuid']];
                    } else {
                        $insertData['t3_origuid']=0;
                    }
                }
                if ($insertData['l18n_parent']) {
                    if (isset($this->config->contentMap[$insertData['l18n_parent']])) {
                        $insertData['l18n_parent'] = $this->config->contentMap[$insertData['l18n_parent']];
                    } else {
                        $insertData['l18n_parent']=0;
                    }
                }

                $insertData['pid'] = $this->config->pageMap[$row['pid']];
                $insertData['old_uid'] = $row['uid'];
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
                if (isset($insertData['sys_language_uid'])) {
                    $insertData['sys_language_uid'] = $this->getSysLangId($insertData['sys_language_uid']);
                }

                $sql = $this->makeInsert($insertData, 'tt_content', $this->config->connTarget);


                if ($this->config->connTarget->query($sql) === TRUE) {
                    echo '';
                    $this->config->contentMap[$row['uid']] = $this->config->connTarget->insert_id;

                } else {
                    echo "Error: " . $this->config->connTarget->error . PHP_EOL;
                    echo "sql: " . $sql . PHP_EOL;
                }

                $this->importFileRelation($row['uid'], $row['pid']);
            }
        } else {
            echo "0 results";
        }
    }

    private function importFileRelation($content_uid, $page_uid)
    {

        $result = $this->config->connSource->query("SELECT * FROM sys_file_reference where pid = " . $page_uid . " and uid_foreign = " . $content_uid . " and deleted = 0");

        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {

                if (!isset($this->config->fileMap[$row['uid_local']])) {
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
                $insertData['uid_local'] = $this->config->fileMap[$insertData['uid_local']];
                $insertData['pid'] = $this->config->pageMap[$insertData['pid']];
                $insertData['uid_foreign'] = $this->config->contentMap[$insertData['uid_foreign']];
                $insertData['l10n_diffsource'] = '';
                $insertData['crop'] = '{"default":{"cropArea":{"x":0,"y":0,"width":1,"height":1},"selectedRatio":"NaN","focusArea":null},"large":{"cropArea":{"x":0,"y":0,"width":1,"height":1},"selectedRatio":"NaN","focusArea":null},"medium":{"cropArea":{"x":0,"y":0,"width":1,"height":1},"selectedRatio":"NaN","focusArea":null},"small":{"cropArea":{"x":0,"y":0,"width":1,"height":1},"selectedRatio":"NaN","focusArea":null},"extrasmall":{"cropArea":{"x":0,"y":0,"width":1,"height":1},"selectedRatio":"NaN","focusArea":null}}';

                if ($insertData['l10n_parent']) {
                    if (isset($this->config->contentMap[$insertData['l10n_parent']])) {
                        $insertData['l10n_parent'] = $this->config->contentMap[$insertData['l10n_parent']];
                    } else {
                        $insertData['l10n_parent']=0;
                    }
                }

                if (isset($insertData['sys_language_uid'])) {
                    $insertData['sys_language_uid'] = $this->getSysLangId($insertData['sys_language_uid']);
                }

                $sql = $this->makeInsert($insertData, 'sys_file_reference', $this->config->connTarget);

                if ($this->config->connTarget->query($sql) === TRUE) {
                    echo '';
                } else {
                    echo "Error: " . $this->config->connTarget->error . PHP_EOL;
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

    private function getSysFileId($image)
    {
        $result = $this->config->connTarget->query("SELECT uid FROM sys_file where identifier = '/uploads/tx_news/" . $image . "'");


        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                return $row['uid'];
            }
        }
        return null;
    }

    /**
     * @param $uid
     * @param $insertData
     */
    private function importNewsMedia($uid, $insertData): void
    {
        $resultNewsMedia = $this->config->connSource->query('select * from tx_news_domain_model_media where parent = ' . $uid);

        if ($resultNewsMedia->num_rows > 0) {
            // output data of each row
            while ($rowNewsMedia = $resultNewsMedia->fetch_assoc()) {

                $mediaRel = [];

                $mediaUid = $this->getSysFileId($rowNewsMedia['image']);
                if ($mediaUid !== null) {
                    $mediaRel ['uid_local'] = $this->getSysFileId($rowNewsMedia['image']);
                } else {
                    continue;
                }

                $mediaRel ['uid_foreign'] = $this->config->newsMap[$uid];

                $mediaRel ['pid'] = $insertData['pid'];
                $mediaRel ['tstamp'] = $rowNewsMedia['tstamp'];
                $mediaRel ['crdate'] = $rowNewsMedia['crdate'];
                $mediaRel ['cruser_id'] = $rowNewsMedia['cruser_id'];
                $mediaRel ['deleted'] = $rowNewsMedia['deleted'];
                $mediaRel ['hidden'] = $rowNewsMedia['hidden'];
                $mediaRel ['sys_language_uid'] = $rowNewsMedia['sys_language_uid'];
                $mediaRel ['l10n_parent'] = 0;
                $mediaRel ['l10n_state'] = null;
                $mediaRel ['l10n_diffsource'] = '';
                $mediaRel ['t3ver_oid'] = 0;
                $mediaRel ['t3ver_wsid'] = 0;
                $mediaRel ['t3ver_state'] = 0;
                $mediaRel ['t3ver_stage'] = 0;
                $mediaRel ['t3ver_count'] = 0;
                $mediaRel ['t3ver_tstamp'] = 0;
                $mediaRel ['t3ver_move_id'] = 0;

                $mediaRel ['tablenames'] = 'tx_news_domain_model_news';
                $mediaRel ['fieldname'] = 'fal_media';
                $mediaRel ['sorting_foreign'] = 1;
                $mediaRel ['table_local'] = 'sys_file';
                $mediaRel ['title'] = $rowNewsMedia['title'];
                $mediaRel ['description'] = $rowNewsMedia['description'] . $rowNewsMedia['copyright'];
                $mediaRel ['alternative'] = $rowNewsMedia['alt'];
                $mediaRel ['link'] = '';
                $mediaRel ['crop'] = '{"default":{"cropArea":{"x":0,"y":0,"width":1,"height":1},"selectedRatio":"NaN","focusArea":null}}';
                $mediaRel ['autoplay'] = 0;
                $mediaRel ['showinpreview'] = 0;

                if (isset($mediaRel['sys_language_uid'])) {
                    $mediaRel['sys_language_uid'] = $this->getSysLangId($mediaRel['sys_language_uid']);
                }

                $mediaSql = $this->makeInsert($mediaRel, 'sys_file_reference', $this->config->connTarget);

                if ($this->config->connTarget->query($mediaSql) === TRUE) {
                    echo '';
                } else {
                    echo "Error: " . $this->config->connTarget->error . PHP_EOL;
                    echo "sql: " . $mediaSql . PHP_EOL;
                }

            }
        }
    }

    private function importNewsCategory($uid, $insertData)
    {
        $resultNewsCatagory = $this->config->connSource->query('select * from tx_news_domain_model_news_category_mm where uid_local = ' . $uid);

        if ($resultNewsCatagory->num_rows > 0) {
            // output data of each row
            while ($rowNewsCatgory = $resultNewsCatagory->fetch_assoc()) {

                $categoryRelation = [];

                // catgoriy
                if (!$this->config->categoryMap[$rowNewsCatgory['uid_foreign']]) {
                    continue;
                }
                $categoryRelation ['uid_local'] = $this->config->categoryMap[$rowNewsCatgory['uid_foreign']];
                $categoryRelation ['uid_foreign'] = $this->config->newsMap[$uid];
                $categoryRelation ['sorting_foreign'] = $rowNewsCatgory['sorting'];
                $categoryRelation ['tablenames'] = 'tx_news_domain_model_news';
                $categoryRelation ['fieldname'] = 'categories';


                if (isset($categoryRelation['sys_language_uid'])) {
                    $categoryRelation['sys_language_uid'] = $this->getSysLangId($categoryRelation['sys_language_uid']);
                }

                $mediaSql = $this->makeInsert($categoryRelation, 'sys_category_record_mm', $this->config->connTarget);

                if ($this->config->connTarget->query($mediaSql) === TRUE) {
                    echo '';
                } else {
                    echo "Error: " . $this->config->connTarget->error . PHP_EOL;
                    echo "sql: " . $mediaSql . PHP_EOL;
                }

            }
        }
    }

    private function getSysLangId($id)
    {
        if  ($id == 0) return 0;
        if  ($id == 1) return 1;
        if  ($id == 2) return 3;
        if  ($id == 3) return 2;
    }

}
