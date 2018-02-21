<style>
	table {
		font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
		font-size: 14px;
		border-collapse: collapse;
		text-align: center;
	}

	th {
		background: #AFCDE7;
		color: white;
		padding: 10px 20px;
	}

	th, td {
		border-style: solid;
		border-width: 0 1px 1px 0;
		border-color: white;
	}

	td {
		background: #D8E6F3;
		padding: 2px;
	}

	th:first-child, td:first-child {
		text-align: left;
	}

	.highlight, span{
		background: #F6D27E;
	}

	span{
		padding: 0 0.1em;
	}

    h1{
    	border-bottom: 2px solid #c0c0c0;
    	padding-bottom: 3px;
    	display: inline-block;
    }
</style>

<?php
$CFG = [
    'fee_parse_url'                 => 'https://btc.com/stats/unconfirmed-tx',
    'fee_parse_min_percent_usage'    => 0.1,
    'fee_parse_max'                    => 1000,
    'fee_parse_use_more_12h'        => false,
    'fee_parse_if_failed'            => 0, // 3 variants: -1: don't use cache, 0 use cache, >0 use this fee
    'fee_parse_cache_file'            => 'fee_parse_cache',

    'fee_parse_titles'                => ['Sat/byte', '< 10m', '< 30m', '< 1h', '< 3h', '< 12h', '>= 12h'],
    'fee_parse_first_cell_number'    => 3,
    'fee_parse_last_cell_number'    => 9,
    'fee_parse_total_cells_parsing' => 12,


    'address_validate_url'     => 'https://blockchain.info/charts/balance?address=',

    'debug_email'             => 'info@artheads.ru',
    'log_file'                 => 'err.log',

];



function validateAddress($address) {
    global $CFG;

    if (!isset($CFG['address_validate_url']) || !$CFG['address_validate_url']){
        err('address_validate_url is not set in configuration');
    }

    // Get content of btc.com address verification page
    $url = $CFG['address_validate_url'] . $address;
    if (!$html = file_get_contents($url)){
        err('Can\'t get address_validate_url: ' . $url);
    }

    if(preg_match('/<h1.*>[\w\s:]+' . $address . '<\/h1>/', $html)){
        return true;
    }

    return false;
}

//echo '<h1>Checked address: 18cBEMRxXHqzWWCxZNtU91F5sbUNKhL5PX &mdash; correct</h1><hr>';



function isIntStr($str) {
    foreach (func_get_args() as $number => $str) {
        if ($str !== (string) (int) $str) {
            return false;
        }
    }
    return true;
}


function isIntVal($val) {
    foreach (func_get_args() as $number => $val) {
        if (!is_int($val) && !isIntStr($val)) {
            return false;
        }
    }

    return true;
}



function isFloatStr($str) {
    foreach (func_get_args() as $number => $str) {
        if ($str !== (string) (float) $str) {
            return false;
        }
    }
    return true;
}

function isFloatVal($val) {
    foreach (func_get_args() as $number => $val) {
        if (!is_float($val) && !isFloatStr($val)) {
            return false;
        }
    }

    return true;
}

function isNumericVal($val) {
    foreach (func_get_args() as $number => $val) {
        if (!isFloatVal($val) && !isIntVal($val)) {
            return false;
        }
    }

    return true;

}


function validateCfgSet($msg_prefix, $validate, $key) {
    $keys = func_get_args();
    if (count($keys) < 3){
        return err('Incorrect count of arguments for validateCfg.');
        die();
    }

    $msg_prefix = array_shift($keys);
    $validate = array_shift($keys);
    if ($msg_prefix){
        $msg_prefix .= ' .';
    }

    global $CFG;
    foreach ($keys as $key){
        if (!isset($CFG[$key])){
            return err($msg_prefix . '"' . $key . '" is not set in configuration.');
        }

        // If strict true -- value must be not false or null or 0, etc.
        if ($validate === true && !$CFG[$key]){
            return err($msg_prefix . '"' . $key . '" is false or empty in configuration.');
        }

        // If string - valdate via function
        if (is_string($validate) && !$validate($CFG[$key])){
            return err($msg_prefix . '"' . $key . '" set in configuration is not correct ' . $validate . '.');
        }
    }

    return true;
}



function dbg($e) {
    dbgne($e);
    exit;
}
function dbgne($e) {
    echo "<pre>";
    print_r($e);
    echo "</pre>";
}
function dbgd($e) {
    dbgdne($e);
    exit;
}
function dbgdne($e) {
    var_dump($e);
}

function err($msg, $elm = '') {
    global $CFG;
    if (!mail($CFG['debug_email'], 'Error on BTC parser', $msg)){
        errlog($msg);
        echo '<br><strong>Error:</strong> Can\t send mail to debug_email';
    }

    $log_msg_add = '';

    if ($elm) {
        //echo '<br>Relevant element: <br /><textarea style="width: 100%; height: 50%;>';
        //var_dump($elm);
        $log_msg_add = ' Relevant element: ' . json_encode($elm);

    }
    errlog($msg . $log_msg_add);

    echo "<strong>Error:</strong> $msg<br />\r\n";

    return false;
}

function errlog($msg) {
    global $CFG;
    if (isset($CFG['log_file'])){
        $filename = $CFG['log_file'];
    } else {
        $filename = 'err.log';
    }

    $fp = fopen($filename, "a+");
    fputs($fp, '[' . date('Y-m-d H:i:s') . ']  ' . $msg . "\r\n");
    fclose($fp);
}


class feeParser{
    public $matrix = false;
    public $fee = false;

    function getFee() {
        global $CFG;

        if ($this->grab() && $this->optimal()){

            //return array('fee' => $optimal_fee, 'matrix' => $matrix);

            // Save to cache
            $this->cacheSave();

            return $this->fee['price'];

        } else {
            err('Fee parsing. Failed to grab.');

            if (!validateCfgSet('Fee parsing.', 'isIntVal', 'fee_parse_if_failed')) return false;
            $algo = $CFG['fee_parse_if_failed'];

            if ($algo == 0){
                if ($price = $this->cacheLoadPrice()){
                    return $price;
                }

                return err('Fee parsing. Failed to use cache..');

            } elseif ($algo > 0){
                err('Fee parsing. Used strict value from configuration (' . $algo . ').');
                return $algo;
            }

        }

        return err('Fee parsing. Failed.');
    }

    // Grabs new fees via http and returns $matrix
    function grab() {
        global $CFG;

        // Check cfg variables
        if (!validateCfgSet('Fee parsing', true, 'fee_parse_url')) return false;
        if (!validateCfgSet('Fee parsing', 'isNumericVal', 'fee_parse_min_percent_usage')) return false;
        if (!validateCfgSet('Fee parsing', 'isIntVal', 'fee_parse_first_cell_number', 'fee_parse_last_cell_number', 'fee_parse_total_cells_parsing', 'fee_parse_max')) return false;

        // Get content of btc.com fees page
        if (!$html = file_get_contents($CFG['fee_parse_url'])){
            return err('Fee parsing. Can\'t get "fee_parse_url": ' . $CFG['fee_parse_url']);
        }

        // Parse content
        if (!preg_match_all('/(<div class="row stats_table_row row_select">\s+<div class="col-md.*">[\s<>=]*\s*([\d.]+)\s+<\/div>\s+<div class="col-md.*">[\s<>=]*\s*([\d.]+)\s+<\/div>\s+<div class="col-md.*">\s+<div class="progress_modify">\s+<div class="progress-bar.*width:.*([\d.]+)%">\s+<\/div>\s+<div class="progress-bar.*width:.*([\d.]+)%">\s+<\/div>\s+<div class="progress-bar.*width:.*([\d.]+)%">\s+<\/div>\s+<div class="progress-bar.*width:.*([\d.]+)%">\s+<\/div>\s+<div class="progress-bar.*width:.*([\d.]+)%">\s+<\/div>\s+<div class="progress-bar.*width:.*([\d.]+)%">\s+<\/div>\s+<\/div>\s+<\/div>\s+<div class="col-md.*>\s+([\d,]+)\s+<\/div>\s+<div class="col-md.*>\s+([\d,]+)\s+<\/div>\s+<\/div>)+/sU', $html, $rows, PREG_SET_ORDER)){
            return err('Fee parsing. Can\'t parse rows. Possible format of page was changed.');
        }

        // Validate fee_parse_titles
        if (!validateCfgSet('Fee parsing', 'is_array', 'fee_parse_titles')) return false;

        $matrix_lastcell = $CFG['fee_parse_last_cell_number'] - $CFG['fee_parse_first_cell_number'];
        if (count($CFG['fee_parse_titles']) != $matrix_lastcell + 1) {
            return err('Fee parsing. Incorrect count of "fee_parse_titles" elements in configuration. Must be ' . ($matrix_lastcell + 1));
        }

        // Validate content and cut array to new matrix
        $matrix = [$CFG['fee_parse_titles']];
        foreach ($rows as $rkey => $row){
            $matrix_row = [];

            if (count($row) != $CFG['fee_parse_total_cells_parsing']){
                return err('Fee parsing. Count of cells is incorrect (must be ' . $CFG['fee_parse_total_cells_parsing'] . ') in row ' . $rkey . '. Possible format of page was changed.', $rows);
            }

            // Check first usefull cell to be integer, cause satoshi can't be float
            if (!isIntVal($row[$CFG['fee_parse_first_cell_number']])){
                return err('Fee parsing. Satoshi per byte is not integer in row ' . $rkey . ' cell ' . $CFG['fee_parse_first_cell_number'] . '. ', $rows);
            }
            $matrix_row[] = $row[$CFG['fee_parse_first_cell_number']];

            // Check percents to be float or int.
            for ($ckey = $CFG['fee_parse_first_cell_number'] + 1; $ckey <= $CFG['fee_parse_last_cell_number']; $ckey++){
                if (!isNumericVal($row[$ckey]) ){
                    return err('Fee parsing. Percent of transactions is not float in row ' . $rkey . ' cell ' . $ckey . '. (' . $row[$ckey] . ')', $rows);
                }
                $matrix_row[] = $row[$ckey];
            }

            $matrix[] = $matrix_row;

        }

        $this->matrix = $matrix;
        return true;
    }



    // Find minimal fee with enough $CFG['fee_parse_min_percent_usage']
    function optimal() {

        if (!$this->matrix){
            return err("Fee parsing. Matrix is not exists");
        }

        global $CFG;

        $matrix_lastcell = $CFG['fee_parse_last_cell_number'] - $CFG['fee_parse_first_cell_number'];

        // Maybe don't use last cell
        if (!isset($CFG['fee_parse_use_more_12h']) || !$CFG['fee_parse_use_more_12h']){
            $CFG['fee_parse_use_more_12h'] = false;
        } else {
            $matrix_lastcell--;
        }

        // Pregenerate storage
        $fees_found = [];
        for($i = 1; $i <= $matrix_lastcell; $i++){
            $fees_found[$i] = false;
        }

        // Rows walking last to first
        $rows_count = count($this->matrix);
        foreach(array_reverse($this->matrix) as $rkey => $row) {

            if ($rkey == 0) {
                continue;
            }

            if ($row[0] <= $CFG['fee_parse_max']){

                // Cell walking
                for ($ckey = 1; $ckey <= $matrix_lastcell; $ckey++){

                    if (!$fees_found[$ckey]){

                        if ($row[$ckey] >= $CFG['fee_parse_min_percent_usage']){
                            $fees_found[$ckey] = ['price' => $row[0], 'row' => ($rows_count - $rkey - 1), 'col' => $ckey];
                        }
                    }
                }
            }
        }

        // Now get one value
        $optimal_fee = false;
        foreach ($fees_found as $key => $e){
            if ($e){
                $optimal_fee = $e;
                break;
            }
        }

        if (!$optimal_fee){
            return err('Fee parsing. No "fee_parse_min_percent_usage" (' . $CFG['fee_parse_min_percent_usage'] . ') found in whole table except limitations.');
        }

        $this->fee = $optimal_fee;
        return true;

    }


    // Save fee parsing cache
    function cacheSave() {
        global $CFG;
        if (!validateCfgSet('Fee parsing', 'is_string', 'fee_parse_cache_file')) return false;

        $now = date('Y-m-d H:i:s');

        $fp = fopen($CFG['fee_parse_cache_file'], "w");
        fputs($fp, $this->fee['price'] . "\r\n$now\r\n" . json_encode(['matrix' => $this->matrix, 'date' => $now, 'fee' => $this->fee]));
        fclose($fp);

           return true;
    }


    // Load only fee price from fee parsing cache
    function cacheLoadPrice() {
        global $CFG;
        if (!validateCfgSet('Fee parsing', 'is_string', 'fee_parse_cache_file')) return false;

        if (!file_exists($CFG['fee_parse_cache_file'])){
            return err("Fee parsing. No cache file ($CFG[fee_parse_cache_file]) found.");
        }

        $fp = fopen($CFG['fee_parse_cache_file'], "r");
        $price = fgets($fp);
        fclose($fp);

        if (!$price) {
            return false;
        }

        $price = trim($price);

        if (!isIntStr($price)){
            return false;
        }

        err('Fee parsing. Used cached result.');
        return $price;
    }


    // Draw table of pased fees, highlight optimal if needed
    function html_table() {

        $fn = func_get_args();

        $result = '';
        $result .= '<table>';

        $rows_count = count($this->matrix);
        foreach ($this->matrix as $rkey => $row){
            $result .= '<tr>';
            foreach ($row as $ckey => $cell){

                // This is fees row and fees cell
                if ($rkey != 0 && $ckey != 0){

                    // Set equality
                    $equality = '';

                    if ($rkey == 1){
                        $equality = '>=';
                    } elseif ($rkey == $rows_count - 1){
                        $equality = '';
                    } else {
                        $equality = '<';
                    }

                    // Highlight if optimal fee
                    $highlight = '';
                    if ($this->fee && $rkey == $this->fee['row'] && $ckey == $this->fee['col']){
                        $highlight = ' class="highlight";';
                    }

                    // Use function to convert fee price if needed
                    if (!empty($fn)){
                        $fn_args = $fn;
                        $fn_args[0] = $cell;
                        $cell = call_user_func_array($fn[0], $fn_args);
                    }
                    $result .= '<td' . $highlight . '>' . $equality . ' ' . $cell . '%</td>';
                } else {
                    $result .= '<th>' . $cell . '</td>';
                }

            }
            $result .= '</tr>';
        }
        $result .= '</table>';
        $result = str_replace("\n", '', str_replace("\r", '', $result));
        return $result;

    }

}



$parser = new feeParser();
if ($price = $parser->getFee()){

    echo '<h1>Optimal fee found: <span>' . $price . '</span></h1>';
    echo "<br />";

    // Result is not from cache
    if ($parser->fee){
        echo "<h1>Rounded values with precision of 2 digits after comma</h1>";
        echo $parser->html_table('round', 2);
        echo "<br />";

        echo "<h1>Absolute values (as on BTC.com)</h1>";
        echo $parser->html_table();
        echo "<br />";
    }
}













?>
