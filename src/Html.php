<?php
final class Html {
    static public function render($stats, OptimalFee $optimalFee) {
        ob_start();
        include __DIR__ . '/view.php';
        $result = ob_get_contents();
        ob_end_clean();

        return $result;
    }
}
