<?php
namespace YiluTech\YiMQ\Laravel;

use Illuminate\Support\Facades\Log;
use YiluTech\YiMQ\Clients\PdoClient;
use Illuminate\Support\Facades\DB;

class LaravelClient extends PdoClient
{
    private string $dbConnection;

    /**
     * @param $name string
     * @param $connectionName string
     * @param $options array('pdo'=>\Pdo,'address' => string,'message_table'=>string,'process_table'=>string,'address'=string,'dsn'=>string,'username'=>string,'password'=>string)
     */
    public function __construct(string $name, string $connectionName, array $options)
    {
        $this->dbConnection = $connectionName;
        parent::__construct($name,$this->getDbConnection()->getPdo(),$options);
    }

    private function getDbConnection(){
        return DB::connection($this->dbConnection);
    }

    public function insert($sql,$data){
        $conn = $this->getDbConnection();
        $conn->insert($sql,array_values($data));
    }

    public function update($sql,$data){
        $conn = $this->getDbConnection();
        $conn->update($sql,array_values($data));
    }


    public function localBegin()
    {
        $conn = $this->getDbConnection();
        $conn->beginTransaction();
    }

    public function localCommit()
    {
        $conn = $this->getDbConnection();
        $conn->commit();
    }

    public function localRollback()
    {
        $conn = $this->getDbConnection();
        $conn->rollBack();
    }

    public function logInfo(string $message, array $context = [])
    {
        Log::info($message,$context);
    }

    public function logError(string $message, array $context = [])
    {
        Log::error($message,$context);
    }
}