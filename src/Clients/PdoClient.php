<?php


namespace YiluTech\YiMQ\Clients;


use Illuminate\Support\Facades\Log;
use PDO;
use YiluTech\YiMQ\Exceptions\SystemException;

class PdoClient extends Client
{
    protected PDO $pdo;
    protected string $messageTable;
    protected string $processTable;
    protected string $driverName;
    protected string $serverVersion;
    /**
     * @param $name string
     * @param $pdo PDO
     * @param $options array('pdo'=>\Pdo,'address' => string,'message_table'=>string,'process_table'=>string,'address'=string,'dsn'=>string,'username'=>string,'password'=>string)
     */
    public function __construct(string $name, $pdo,array $options){
        if(empty($pdo)){
            $this->pdo = new PDO($options['dsn'], $options['username'], $options['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }else{
            $this->pdo = $pdo;
        }

        $this->messageTable = $options['table_prefix'].'_messages';
        $this->processTable = $options['table_prefix'].'_processes';

        $this->driverName = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->serverVersion = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        parent::__construct($name, $options);
    }

    public function insert($sql,$data){
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function update($sql,$data){
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
    }

    public function find($sql,$data){
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function delete($sql,$data=[]){
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
    }

    public function transBeginTransaction($data){
        $sql = "insert into {$this->messageTable} (
                   id,relation_id,type,topic,action,status,delay,attempts,created_at,available_at,results
                   ) 
                   values (
                           :id,:relation_id,:type,:topic,:action,:status,:delay,:attempts,:created_at,:available_at,'[]'
                           )";
        $this->insert($sql,$data);
    }

    public function loadAndLockTrans(string $relationId)
    {
        $sql = "select * from {$this->messageTable} where relation_id = :relation_id";

        if ($this->driverName == 'mysql' && version_compare($this->serverVersion,'8.0.1', '>=')||
            ($this->driverName === 'pgsql' && version_compare($this->serverVersion, '9.5', '>='))){

            $sql = $sql." for update nowait";
        }else{
            $sql = $sql." for update";
        }
        return $this->find($sql,['relation_id'=>$relationId]);
    }


    public function transCommit($data){
        $sql = "update {$this->messageTable} set action=?,status=?,available_at=? where id=?";
        $this->update($sql,array_values($data));
    }

    public function transRollBack($data){
        $sql = "update {$this->messageTable} set action=?,status=?,available_at=? where id=?";
        $this->update($sql,array_values($data));
    }


    public function localBegin()
    {
        $this->pdo->beginTransaction();
    }

    public function localCommit()
    {
        $this->pdo->commit();
    }

    public function localRollback()
    {
        $this->pdo->rollBack();
    }

    public function createProcess($data)
    {
        $sql = "INSERT INTO {$this->processTable} (
                   id, producer, trans_id, type, processor, data, action, created_at, updated_at
                   ) VALUES (
                             :id, :producer, :trans_id, :type,:processor, :data, :action, :created_at, :updated_at
                             );";
        $this->insert($sql,$data);
    }

    public function setProcessAction($id,$action)
    {
        $sql = "update {$this->processTable} set action=? where id=?";

        $this->update($sql,[$action,$id]);
    }

    public function loadProcess(string $id,bool $lock = false)
    {
        $sql = "select * from {$this->processTable} where id = :id";
        if($lock){
            if ($this->driverName == 'mysql' && version_compare($this->serverVersion,'8.0.1', '>=')||
                ($this->driverName === 'pgsql' && version_compare($this->serverVersion, '9.5', '>='))){

                $sql = $sql." for update nowait";
            }else{
                $sql = $sql." for update";
            }
        }
        return $this->find($sql,['id'=>$id]);
    }

    public function messageTable(){
        return $this->messageTable;
    }
    public function processTable(){
        return $this->processTable;
    }

    public function testClear(){
        if($_ENV['APP_ENV'] != 'testing'){
            throw new SystemException("The value of APP_ENV is {$_ENV['APP_ENV']} so it cannot be executed.");
        }
        $this->delete("delete from $this->messageTable");
        $this->delete("delete from $this->processTable");
    }

    public function transCheck($data):array{
        $sql = "select * from {$this->messageTable} where id = :id";

        if ($this->driverName == 'mysql' && version_compare($this->serverVersion,'8.0.1', '>=')||
            ($this->driverName === 'pgsql' && version_compare($this->serverVersion, '9.5', '>='))){

            $sql = $sql." for update nowait";
        }else{
            $sql = $sql." for update";
        }
        $trans = $this->find($sql,['id'=>$data["message_id"]]);

        if (!$trans){
            return [
                "error"=>"TRANS_NOT_EXIST"
            ];
        }
        return [
            "data" => [
                "action"=>$trans["action"]
            ]
        ];
    }

    public function logInfo(string $message, array $context = [])
    {

    }

    public function logError(string $message, array $context = [])
    {

    }
}