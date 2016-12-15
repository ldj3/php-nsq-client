<?php
/**
 * Queue pool
 * User: moyo
 * Date: 5/7/15
 * Time: 3:50 PM
 */

namespace Kdt\Iron\Queue;

use Kdt\Iron\Queue\Adapter\Nsq\Client;
use Kdt\Iron\Queue\Interfaces\MessageInterface;
use Kdt\Iron\Tracing\Sample\Scene\MQ;
use Closure;

class Queue
{
    /**
     * @var int
     */
    private static $maxKeepSeconds = 900;

    /**
     * @var float
     */
    private static $ksRandPercent = 0.25;

    /**
     * @var string
     */
    private static $lastPushError = '';

    /**
     * queue msg publish
     * @param $topic
     * @param $message
     * @return bool
     */
    public static function push($topic, $message)
    {
        $TID = MQ::actionBegin($topic, 'publish');
        $result = self::nsq()->push($topic, $message);
        MQ::actionFinish($TID);
        if ($result['error_code'])
        {
            self::$lastPushError = $result['error_message'];
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * queue msg publish (bulk)
     * @param $topic
     * @param $messages
     * @return bool
     */
    public static function bulkPush($topic, array $messages)
    {
        $result = self::nsq()->bulk($topic, $messages);
        if ($result['error_code'])
        {
            self::$lastPushError = $result['error_message'];
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * queue subscribe
     * @param $topic
     * @param callable $callback
     * @param $options
     * @return string
     */
    public static function pop($topic, callable $callback, $options = [])
    {
        // options
        $options['auto_delete'] = isset($options['auto_delete']) ? $options['auto_delete'] : false;
        $options['keep_seconds'] = self::filterKeepSeconds(isset($options['keep_seconds']) ? $options['keep_seconds'] : self::$maxKeepSeconds);
        $options['max_retry'] = isset($options['max_retry']) ? $options['max_retry'] : 3;
        $options['retry_delay'] = isset($options['retry_delay']) ? $options['retry_delay'] : 5;
        $options['exception_observer'] = isset($options['exception_observer']) ? $options['exception_observer'] : null;
        $options['sub_ordered'] = isset($options['sub_ordered']) ? $options['sub_ordered'] : false;
        // pop
        return self::nsq()->pop
        (
            $topic,
            function (MessageInterface $msg) use ($callback)
            {
                call_user_func_array($callback, [$msg]);
            },
            $options
        );
    }

    /**
     * exiting pop loop
     */
    public static function exitPop()
    {
        self::nsq()->stop();
    }

    /**
     * queue msg done
     * @param $messageId
     * @return bool
     */
    public static function delete($messageId)
    {
        return self::nsq()->delete($messageId);
    }

    /**
     * queue msg delay
     * @param $seconds
     */
    public static function later($seconds)
    {
        self::nsq()->later($seconds);
    }

    /**
     * queue msg retry
     */
    public static function retry()
    {
        self::nsq()->retry();
    }

    /**
     * get last push error
     * @return string
     */
    public static function lastPushError()
    {
        return self::$lastPushError;
    }

    /**
     * close all connections
     */
    public static function close()
    {
        self::nsq()->close();
    }

    /**
     * @return Client
     */
    private static function nsq()
    {
        static $nsqInstance = null;
        if (is_null($nsqInstance))
        {
            $nsqInstance = new Client();
        }
        return $nsqInstance;
    }

    /**
     * @param $custom
     * @return int
     */
    private static function filterKeepSeconds($custom)
    {
        $custom = intval($custom);
        // limit
        $custom = $custom > self::$maxKeepSeconds ? self::$maxKeepSeconds : $custom;
        // random
        $random = mt_rand(0, intval($custom * self::$ksRandPercent));
        // merge
        return $custom + $random;
    }
}