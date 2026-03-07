<?php
/***
 * 投票事件管理类
 * 包括投票事件的创建、编辑、删除等功能
 * 以及投票选项的管理等
 */

class VOTE_EVENTS
{

    private const TABLE_NAME = "voteEvents" // 投票事件表名

    private static function DBInstance(): DB
    {
        return DB_Connections::default();
    }

    public static function initVoteList() : bool 
    {
        try
        {
            $db = self::DBInstance();
            if(!$db->tableExists(self::TABLE_NAME)){
                
            }
        }
    }





}