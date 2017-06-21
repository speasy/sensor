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

namespace sensor;

use \core\ctrl\pool, \core\ctrl\crypt, \core\ctrl\socket;

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
     * For Self
     */

    /**
     * Get Self Identity
     *
     * @return string
     */
    private static function get_self_id(): string
    {
        $identity = CLI_CAS_PATH . 'identity';
        if (is_file($identity)) $id = (string)file_get_contents($identity);
        else {
            $id = get_uuid();
            if (0 === (int)file_put_contents($identity, $id)) $id = '';
        }
        unset($identity);
        return $id;
    }

    /**
     * Get Pair Identity
     *
     * @param string $self
     * @param string $hash
     *
     * @return string
     */
    private static function get_pair_id(string $self, string $hash): string
    {
        if ('' !== $self && '' !== $hash) {
            $pair = get_uuid($self . $hash);
            $identity = CLI_CAS_PATH . $pair;
            if (is_file($identity)) $id = (string)file_get_contents($identity);
            else {
                $id = get_uuid();
                if (0 === (int)file_put_contents($identity, $id)) $id = '';
            }
            unset($pair, $identity);
        } else $id = '';
        unset($self, $hash);
        return $id;
    }

    /**
     * For Client
     */

    /**
     * Self Broadcast
     */
    public static function broadcast()
    {
        $id = self::get_self_id();
        if ('' !== $id) {
            $data = '--cmd="sensor/sensor,capture" --get="result" --data="' . http_build_query(['user' => $_SERVER['USERNAME'], 'name' => $_SERVER['COMPUTERNAME'], 'hash' => $id]) . '"';
            while (true) {
                socket::udp_broadcast($data);
                sleep(60);
            }
        }
    }

    /**
     * Save public key
     *
     * @return string
     */
    public static function save_key(): string
    {
        if ('' !== pool::$data['user'] && '' !== pool::$data['name'] && '' !== pool::$data['hash']) {
            //Get Node identity
            $node = CLI_CAS_PATH . get_uuid(pool::$data['user'] . '@' . pool::$data['name'] . '@' . pool::$data['hash']);
            //Check Key
            $key = base64_decode(pool::$data['key'], true);
            $data = false !== $key && 0 < (int)file_put_contents($node, $key)
                ? '--cmd="sensor/sensor,del_key" --data="' . http_build_query(['user' => $_SERVER['USERNAME'], 'name' => $_SERVER['COMPUTERNAME'], 'hash' => self::get_self_id()]) . '"'
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
        $self = self::get_self_id();
        //Escape self-broadcast
        if ('' !== $self && '' !== pool::$data['hash'] && $self !== pool::$data['hash']) {
            //Get Node identity
            $node = CLI_CAS_PATH . get_uuid(pool::$data['user'] . '@' . pool::$data['name'] . '@' . pool::$data['hash']);
            //Detect new node
            if (!is_file($node)) {
                //Generate new node id
                $id = self::get_pair_id($self, pool::$data['hash']);
                $keys = crypt::get_pkey();
                file_put_contents($node, $keys['private']);
                file_put_contents($node . '@key', $keys['public']);
                $data = '--cmd="sensor/sensor,save_key" --get="result" --data="' . http_build_query(['user' => $_SERVER['USERNAME'], 'name' => $_SERVER['COMPUTERNAME'], 'hash' => $id, 'key' => base64_encode($keys['public'])]) . '"';
                unset($id, $keys);
            } else $data = '';
            unset($node);
        } else $data = '';
        unset($self);
        return $data;
    }

    /**
     * Get Public Key
     * Only one chance
     */
    public static function get_key()
    {
        $node = CLI_CAS_PATH . get_uuid(pool::$data['user'] . '@' . pool::$data['name'] . '@' . pool::$data['hash']) . '@key';
        if (is_file($node)) {
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
        $node = CLI_CAS_PATH . get_uuid(pool::$data['user'] . '@' . pool::$data['name'] . '@' . pool::$data['hash']) . '@key';
        if (is_file($node)) unlink($node);
        unset($node);
    }
}