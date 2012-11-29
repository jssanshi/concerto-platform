<?php

/*
  Concerto Platform - Online Adaptive Testing Platform
  Copyright (C) 2011-2012, The Psychometrics Centre, Cambridge University

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; version 2
  of the License, and not any of the later versions.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
if (!isset($ini)) {
    require_once __DIR__ . '/../Ini.php';
    $ini = new Ini();
}

$group = Ini::$r_users_group;

//add new users
$sql = sprintf("SELECT `id` FROM `%s`", User::get_mysql_table());
$z = mysql_query($sql);
while ($r = mysql_fetch_array($z)) {
    $name = Ini::$r_users_name_prefix . $r['id'];

    $sql = sprintf("SELECT * FROM `%s` WHERE `User_id`=%d", UserR::get_mysql_table(), $r["id"]);
    $z2 = mysql_query($sql);

    //UNIX user doesn't exist
    if (mysql_num_rows($z2) == 0) {
        //adgroup
        `addgroup $group`;

        //adduser
        `adduser --disabled-login --gecos "" --ingroup $group $name`;

        //passwd
        $password = UserR::generate_password();
        `passwd $name <<EOF\n$password\n$password\nEOF`;

        //insert UserR record
        $user = new UserR();
        $user->login = $name;
        $user->password = $password;
        $user->User_id = $r['id'];
        $user->mysql_save();

        //dirs
        $session_dir = Ini::$path_temp . $r['id'];
        if (!is_dir($session_dir)) {
            mkdir($session_dir, 0770, true);
        }
        chown($session_dir, $name);
        chgrp($session_dir, Ini::$apache_user);
        chmod($session_dir, 0770);
        
        $media_dir = Ini::$path_internal_media . $r['id'];
        if (!is_dir($media_dir)) {
            mkdir($media_dir, 0770, true);
        }
        chown($media_dir, $name);
        chgrp($media_dir, Ini::$apache_user);
        chmod($media_dir, 0770);
    }
}

//remove unused users
$sql = sprintf("SELECT * FROM `%s`", UserR::get_mysql_table());
$z = mysql_query($sql);
while ($r = mysql_fetch_array($z)) {
    $sql2 = sprintf("SELECT `id` FROM `%s` WHERE `id`=%d", User::get_mysql_table(), $r['User_id']);
    $z2 = mysql_query($sql2);

    //Concerto user doesn't exist
    if (mysql_num_rows($z2) == 0) {
        $userR = UserR::from_mysql_id($r['id']);

        //deluser
        `deluser --remove-home $userR->login`;

        //delete UserR record
        $userR->mysql_delete();
    }
}

$z = mysql_query($sql);
if (mysql_num_rows($z) == 0) {
    `delgroup $group`;
}
?>