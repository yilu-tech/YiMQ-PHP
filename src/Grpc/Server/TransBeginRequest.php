<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: server.proto

namespace YiluTech\YiMQ\Grpc\Server;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>server.TransBeginRequest</code>
 */
class TransBeginRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string topic = 1;</code>
     */
    protected $topic = '';
    /**
     * Generated from protobuf field <code>uint32 delay = 2;</code>
     */
    protected $delay = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $topic
     *     @type int $delay
     * }
     */
    public function __construct($data = NULL) {
        \YiluTech\YiMQ\Grpc\GPBMetadata\Server::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string topic = 1;</code>
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Generated from protobuf field <code>string topic = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setTopic($var)
    {
        GPBUtil::checkString($var, True);
        $this->topic = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint32 delay = 2;</code>
     * @return int
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * Generated from protobuf field <code>uint32 delay = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setDelay($var)
    {
        GPBUtil::checkUint32($var);
        $this->delay = $var;

        return $this;
    }

}
