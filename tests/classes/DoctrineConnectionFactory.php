<?php

namespace rotdrop\bav;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;

class DoctrineConnectionFactory
{
    public static function makeConnection(): Connection
    {
        $parser = new DsnParser(array('mysql' => 'pdo_mysql', 'sqlite' => 'pdo_sqlite'));
        $params = $parser->parse(getenv('DBAL_URL') ?: 'mysql://test@localhost/test');
        return DriverManager::getConnection($params);
    }
}
