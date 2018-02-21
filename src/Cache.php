<?php
final class Cache {
    private $log;
    private $filepath;

    public function __construct(Log $log, $filepath) {
        $this->log = $log;
        $this->filepath = $filepath;
    }

    public function save($data) {
        if (!file_put_contents($this->filepath, $data)) {
            $this->log->error("Couldn't save the cache to '{$this->filepath}'");
            return false;
        }
        return true;
    }

    public function load() {
        $content = file_get_contents($this->filepath);
        if ($content === false) {
            $this->log->error("Couldn't read the cache from '{$this->filepath}'");
        }

        return $content;
    }
}
