<?php

namespace Tower;

use Exception;
use Tower\Console\Color;

class Inserter
{
    protected string $table;
    protected array $data;
    protected string $connection = 'mysql';
    protected array $insertData;

    public function __construct(string $table , array $data)
    {
        $this->table = $table;
        $this->data = $data;
    }

    public function connection(string $connection = 'mysql'): static
    {
        $this->connection = $connection;

        return $this;
    }

    public function create(int $count): string
    {
        if ($count > 10000)
            $count = 10000;

        for ($i=0;$i<$count;$i++){
            $this->insertData[] = $this->data;
        }
        $this->insert();
        return "success";
    }

    protected function insert(): void
    {
        try {
            $time = microtime(true);

            if ($this->connection == 'mysql'){
                DB::table($this->table)->insert($this->insertData);
            } else{
                for($i = 0; $i < count($this->insertData); $i++) {

                    if (isset($this->insertData[$i]['id']))
                        $params['body'][] = [
                            'index' => [
                                '_index' => $this->table,
                                '_id' => $this->insertData[$i]['id'],
                            ]
                        ];
                    else
                        $params['body'][] = [
                            'index' => [
                                '_index' => $this->table,
                            ]
                        ];

                    $params['body'][] = $this->insertData[$i];
                }

                Elastic::getInstance()->bulk($params);
            }

            $time = microtime(true) - $time;
            
            if ($this->connection == 'mysql')
                $table = 'table';
            else
                $table = 'index';

            echo Color::LIGHT_YELLOW . "Fake data was successfully inserted in the $this->table $table ($time ms)". Color::RESET . PHP_EOL;
        }catch (Exception $e){
            (new Log())->channel('inserter')->alert($e->getMessage());

            if ($this->connection == 'mysql')
                $table = 'table';
            else
                $table = 'index';

            echo Color::error("Fake data was not successfully inserted in the $this->table $table");
        }
    }
}