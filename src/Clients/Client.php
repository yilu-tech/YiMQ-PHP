<?php
namespace YiluTech\YiMQ\Clients;

use YiluTech\YiMQ\Constants\MessageAction;
use YiluTech\YiMQ\Exceptions\SystemException;
use YiluTech\YiMQ\Grpc\Server\ActorInfo;
use YiluTech\YiMQ\Grpc\Server\ClientInfo;
use YiluTech\YiMQ\Grpc\Server\ServerClient;
use YiluTech\YiMQ\Grpc\Server\TransPrepareRequest;
use YiluTech\YiMQ\Laravel\LaravelClient;
use YiluTech\YiMQ\Messages\Trans\TransChildMessage;
use YiluTech\YiMQ\Messages\Trans\TransEc;
use YiluTech\YiMQ\Messages\Trans\TransSaga;
use YiluTech\YiMQ\Messages\Trans\TransTcc;
use YiluTech\YiMQ\Messages\Trans\TransXa;
use YiluTech\YiMQ\Messages\TransMessage;
use YiluTech\YiMQ\Mocker;
use YiluTech\YiMQ\ClientProcessorManager;

abstract class Client
{
    protected string $name;
    protected string $broker;
    protected string $secret;
    protected string $address;
    protected TransMessage $transaction;
    public ServerClient $grpcClient;
    public Mocker $mocker;
    protected ClientProcessorManager $processorManager;
    /**
     * @param $name string
     * @param $options array('address' => string)
     */
    public function __construct(string $name, array $options)
    {
        $this->name = $name;
        $this->broker = $options["broker"];
        $this->secret = $options["secret"];


        $this->address = $options['address'];
        $this->processorManager = new ClientProcessorManager($this);
    }
    public abstract function testClear();
    public abstract function logInfo(string $message, array $context = []);
    public abstract function logError(string $message, array $context = []);

    public function transaction(string $topic=null,callable $callback=null){
        if(isset($this->transaction)){
            throw new SystemException("The client has started the transaction.");
        }
        $this->transaction =  new TransMessage($this,$topic,$callback);
        return $this->transaction;
    }


    public function childTransaction(string $topic=null,string $relationId){
        $this->transaction($topic);
        $this->transaction->childTransInit($relationId);
        return $this->transaction;
    }

    public function restoreTransaction(string $relationId){
        if(isset($this->transaction)){
            throw new SystemException("This client has already started a transaction.");
        }
        $this->transaction = new TransMessage($this,'',null);

        $record = $this->loadAndLockTrans($relationId);
        if(!$record){
            throw new SystemException("process $relationId not found trans message.");
        }
        $this->transaction->restore($record);
        return $this->transaction;
    }

    public abstract function loadAndLockTrans(string $relation_id);

    public function prepare(){
        $this->ifNotHasTransactionThrow();
        $this->transaction->prepare();
    }
    public function end(){
        unset($this->transaction);
    }

    public function commit($localCommit=true){
        $this->ifNotHasTransactionThrow();

        $this->transaction->commit($localCommit);
        unset($this->transaction);
    }

    public function rollback($localRollback=true){
        $this->ifNotHasTransactionThrow();
        $this->transaction->rollback($localRollback);
        unset($this->transaction);
    }


    public function ec(string $processor):TransEc{
        $this->ifNotHasTransactionThrow();
        return $this->transaction->ec($processor);
    }

    public function xa(string $processor):TransXa{
        $this->ifNotHasTransactionThrow();
        return $this->transaction->xa($processor);
    }

    public function tcc(string $processor):TransTcc{
        $this->ifNotHasTransactionThrow();
        return $this->transaction->tcc($processor);

    }

    public function saga(string $processor):TransSaga{
        $this->ifNotHasTransactionThrow();
        return $this->transaction->saga($processor);
    }
    private function ifNotHasTransactionThrow(){
        if(empty($this->transaction)){
            throw new SystemException("The client did not start a transaction.");
        }
    }


    public abstract function localBegin();
    public abstract function localCommit();
    public abstract function localRollback();


    public abstract function transBeginTransaction($data);

    public abstract function transRollBack($data);

    public abstract function transCommit($data);

    public abstract function transCheck($data);



//    public function transChildPrepare(TryRequest $tryRequest):array{
//        if(isset($this->mocker) && $result = $this->mocker->match($tryRequest)){
//            return $result;
//        }
//
//        $this->initGrpc();
//
//        /* @var $tryReply TryReply */
//        list($tryReply, $status) = $this->grpcClient->TccTry($tryRequest)->wait();
//        if($status->code != 0){
//            throw new SystemException("code: {$status->code}, client try {$status->details}");
//        }
//        $result = json_decode($tryReply->getResult(),true);
//        $result = is_null($result) ?  $tryReply->getResult() : $result;
//
//        return [$result,$tryReply->getError()];
//    }
    public function initGrpc(){
        if(isset($this->grpcClient)){
            return;
        }
        $this->grpcClient = new ServerClient($this->address,[
            'credentials' => \Grpc\ChannelCredentials::createInsecure(),
            'update_metadata' => function($metaData) {
                $timestamp = strval(time());
                $sign = $this->getSign($timestamp);

                $metaData['broker'] = [$this->broker];
                $metaData['actor'] = [$this->name];


                $metaData['sign'] = [$sign];
                $metaData['timestamp'] = [$timestamp];
                return $metaData;
            }
        ]);
    }
    public function mock():Mocker{
        $this->initMocker();;
        return $this->mocker;
    }
    public function initMocker(){
        if(isset($this->mocker)){
            return;
        }
        $this->mocker = new Mocker();
    }


    public function processorManager(): ClientProcessorManager
    {
        return $this->processorManager;
    }


    public abstract function createProcess($data);

    public abstract function setProcessAction($id,$action);

    public abstract function loadProcess(string $id,bool $lock = false);

    public function getPdoClient():PdoClient{
        return $this;
    }
    public function getLaravelClient():LaravelClient{
        return $this;
    }

    public function getSign(string $timestamp):string{
        return md5("$this->broker-$this->name-$this->secret-$timestamp");
    }
    public function verify(string $timestamp,string $sign){
        $verifySign = md5("$this->broker-$this->name-$this->secret-$timestamp");

        if($sign != $verifySign){
            throw new SystemException("Unauthenticated");
        }
    }
}