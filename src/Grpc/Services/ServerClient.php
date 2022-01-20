<?php
// GENERATED CODE -- DO NOT EDIT!

namespace YiluTech\YiMQ\Grpc\Services;

/**
 */
class ServerClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \YiluTech\YiMQ\Grpc\Services\TryRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TccTry(\YiluTech\YiMQ\Grpc\Services\TryRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/services.Server/TccTry',
        $argument,
        ['\YiluTech\YiMQ\Grpc\Services\TryReply', 'decode'],
        $metadata, $options);
    }

}
