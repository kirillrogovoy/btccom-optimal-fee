<?php
final class OptimalFee {
    public $stat;
    public $percentIndex;

    public function __construct(\BtcCom\Stat $stat, $percentIndex) {
        $this->stat = $stat;
        $this->percentIndex = $percentIndex;
    }

    static public function fromStats($stats, $minThreshold) {
        foreach (array_reverse($stats) as $stat) {
            foreach ($stat->feeDistribution as $index => $percent) {
                if ($percent >= $minThreshold) {
                    return new self($stat, $index);
                }
            }
        }
        return null;
    }
}
