<?php

/**
 * Sensor Module for Nervsys
 *
 * Author Jerry Shaw <jerry-shaw@live.com>
 * Author 秋水之冰 <27206617@qq.com>
 *
 * Copyright 2017 Jerry Shaw
 * Copyright 2017 秋水之冰
 *
 * This file is part of NervSys.
 *
 * NervSys is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NervSys is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NervSys. If not, see <http://www.gnu.org/licenses/>.
 */
class sensor
{
    public static $api = [
        'broadcast' => [],
        'save_key'  => ['user', 'name', 'hash', 'key'],
        'capture'   => ['user', 'name', 'hash'],
        'get_key'   => ['user', 'name', 'hash'],
        'del_key'   => ['user', 'name', 'hash']
    ];

    /**
     * Initialize
     */
    public static function init()
    {
        load_lib('core', 'data_pool');
        load_lib('core', 'ctrl_socket');
    }

    /**
     * For Client
     */

    /**
     * Self Broadcast
     */
    public static function broadcast()
    {
        $id = \ctrl_socket::get_identity();
        if ('' !== $id) {
            $data = '--cmd="sensor/sensor,capture" --get="result" --data="' . http_build_query(['user' => $_SERVER['USERNAME'], 'name' => $_SERVER['COMPUTERNAME'], 'hash' => $id]) . '"';
            while (true) \ctrl_socket::udp_broadcast($data);
        }
    }

    /**
     * Save public key
     *
     * @return string
     */
    public static function save_key(): string
    {
        if ('' !== \data_pool::$data['user'] && '' !== \data_pool::$data['name'] && '' !== \data_pool::$data['hash']) {
            //Get Node identity
            $node = CLI_CAS_PATH . \data_pool::$data['user'] . '@' . \data_pool::$data['name'] . '@' . \data_pool::$data['hash'];
            //Check Key
            $key = base64_decode(\data_pool::$data['key'], true);
            $data = false !== $key && 0 < (int)file_put_contents($node, $key)
                ? '--cmd="sensor/sensor,del_key" --data="' . http_build_query(['user' => $_SERVER['USERNAME'], 'name' => $_SERVER['COMPUTERNAME'], 'hash' => \ctrl_socket::get_identity()]) . '"'
                : '';
            unset($node, $key);
        } else $data = '';
        return $data;
    }

    /**
     * For Server
     */

    /**
     * Capture broadcast
     *
     * @return string
     */
    public static function capture(): string
    {
        $id = \ctrl_socket::get_identity();
        //Escape self-broadcast
        if ('' !== $id && $id !== \data_pool::$data['hash']) {
            //Get Node identity
            $node = CLI_CAS_PATH . \data_pool::$data['user'] . '@' . \data_pool::$data['name'] . '@' . \data_pool::$data['hash'];
            //Detect new node
            if (!is_file($node)) {
                load_lib('core', 'data_crypt');
                $keys = \data_crypt::get_pkey();
                file_put_contents($node, $keys['private']);
                file_put_contents($node . '@public', $keys['public']);
                $data = '--cmd="sensor/sensor,save_key" --get="result" --data="' . http_build_query(['user' => $_SERVER['USERNAME'], 'name' => $_SERVER['COMPUTERNAME'], 'hash' => $id, 'key' => base64_encode($keys['public'])]) . '"';
                unset($keys);
            } else $data = '';
            unset($node);
        } else $data = '';
        unset($id);
        return $data;
    }

    /**
     * Get Public Key
     * Only one chance
     */
    public static function get_key()
    {
        $node = CLI_CAS_PATH . \data_pool::$data['user'] . '@' . \data_pool::$data['name'] . '@' . \data_pool::$data['hash'] . '@public';
        if (!is_file($node)) {
            $data = (string)file_get_contents($node);
            unlink($node);
        } else $data = '';
        unset($node);
        return $data;
    }

    /**
     * Delete Public Key
     */
    public static function del_key()
    {
        $node = CLI_CAS_PATH . \data_pool::$data['user'] . '@' . \data_pool::$data['name'] . '@' . \data_pool::$data['hash'] . '@public';
        if (!is_file($node)) unlink($node);
        unset($node);
    }
}