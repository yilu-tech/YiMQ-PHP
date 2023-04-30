<?php
// GENERATED CODE -- DO NOT EDIT!

namespace YiluTech\YiMQ\Grpc\Server;

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
     * @param \YiluTech\YiMQ\Grpc\Server\TransBeginRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TransBegin(\YiluTech\YiMQ\Grpc\Server\TransBeginRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/server.Server/TransBegin',
        $argument,
        ['\YiluTech\YiMQ\Grpc\Server\TransActionReply', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \YiluTech\YiMQ\Grpc\Server\TransPrepareRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TransPrepare(\YiluTech\YiMQ\Grpc\Server\TransPrepareRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/server.Server/TransPrepare',
        $argument,
        ['\YiluTech\YiMQ\Grpc\Server\TransActionReply', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \YiluTech\YiMQ\Grpc\Server\TransActionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TransSubmit(\YiluTech\YiMQ\Grpc\Server\TransActionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/server.Server/TransSubmit',
        $argument,
        ['\YiluTech\YiMQ\Grpc\Server\TransActionReply', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \YiluTech\YiMQ\Grpc\Server\TransActionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TransCancel(\YiluTech\YiMQ\Grpc\Server\TransActionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/server.Server/TransCancel',
        $argument,
        ['\YiluTech\YiMQ\Grpc\Server\TransActionReply', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \YiluTech\YiMQ\Grpc\Server\TransChildPrepareRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TransChildPrepare(\YiluTech\YiMQ\Grpc\Server\TransChildPrepareRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/server.Server/TransChildPrepare',
        $argument,
        ['\YiluTech\YiMQ\Grpc\Server\TransChildPrepareReply', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \YiluTech\YiMQ\Grpc\Server\PrepareRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GeneralDispatch(\YiluTech\YiMQ\Grpc\Server\PrepareRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/server.Server/GeneralDispatch',
        $argument,
        ['\YiluTech\YiMQ\Grpc\Server\PrepareReply', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \YiluTech\YiMQ\Grpc\Server\PrepareRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function BroadcastDispatch(\YiluTech\YiMQ\Grpc\Server\PrepareRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/server.Server/BroadcastDispatch',
        $argument,
        ['\YiluTech\YiMQ\Grpc\Server\PrepareReply', 'decode'],
        $metadata, $options);
    }

}
