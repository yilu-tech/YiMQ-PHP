<?php
namespace YiluTech\YiMQ\Clients;

use YiluTech\YiMQ\Exceptions\SystemException;
use YiluTech\YiMQ\Grpc\Services\TryReply;
use YiluTech\YiMQ\Grpc\Services\TryRequest;
use YiluTech\YiMQ\Grpc\Services\YiMQClient;
use YiluTech\YiMQ\Laravel\LaravelClient;
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
    protected string $address;
    protected TransMessage $transaction;
    protected YiMQClient $grpcClient;
    protected Mocker $mocker;
    protected ClientProcessorManager $processorManager;
    /**
     * @param $name string
     * @param $options array('address' => string)
     */
    public function __construct(string $name, array $options)
    {
        $this->name = $name;
        $this->address = $options['address'];
        $this->processorManager = new ClientProcessorManager($this);
    }


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

    public abstract function prepareChildren(array $children);

    public function prepareToServer(TryRequest $tryRequest):array{
        if(isset($this->mocker) && $result = $this->mocker->match($tryRequest)){
            return $result;
        }

        $this->initGrpc();

        /* @var $tryReply TryReply */
        list($tryReply, $status) = $this->grpcClient->TccTry($tryRequest)->wait();
        if($status->code != 0){
            throw new SystemException("code: {$status->code}, client try {$status->details}");
        }
        $result = json_decode($tryReply->getResult(),true);
        $result = is_null($result) ?  $tryReply->getResult() : $result;

        return [$result,$tryReply->getError()];
    }
    public function initGrpc(){
        if(isset($this->grpcClient)){
            return;
        }
        $this->grpcClient = new YiMQClient($this->address,[
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
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
}