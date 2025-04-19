<?php


interface IEchoer {
    public function query(string $sql): array;
}

class EchoerService implements IEchoer {
    public function query(string $sql): array {
        return [['id' => 1, 'name' => 'Alice']];
    }
}
