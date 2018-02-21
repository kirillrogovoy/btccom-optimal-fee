<html>
<head>
    <title>Optimal Bitcoin transaction fee</title>
    <style>
        body {
            font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
        }
        .header {
            border-bottom: 2px solid #c0c0c0;
            padding-bottom: 3px;
            font-size: 32px;
            display: inline-block;
        }
        .fees {
            font-size: 14px;
            border-collapse: collapse;
            text-align: center;
        }
        .fees th {
            background: #AFCDE7;
            color: white;
            padding: 10px 20px;
        }

        .fees th, .fees td {
            border-style: solid;
            border-width: 0 1px 1px 0;
            border-color: white;
        }

        .fees td {
            background: #D8E6F3;
            padding: 2px;
        }

        .fees th:first-child, .fees td:first-child {
            text-align: left;
        }

        .highlight {
            background: #F6D27E !important;
        }
    </style>
</head>
<body>
    <header class="header">
        Optimal fee found:
        <span class="highlight"><?php echo $optimalFee->stat->transactionFee; ?> sat/byte</span>
    </header>
    <div style="height: 20px;"></div>
    <header class="header">
        The information is actual as for:
        <span class="highlight"><?php echo date(DATE_RFC2822); ?></span>
    </header>
    <div style="height: 20px;"></div>
    <span>Hover on a cell to see the unrounded value</span>
    <div style="height: 5px;"></div>
    <table class="fees">
        <tr>
            <th>Sat/byte</th>
            <th>&lt; 10m</th>
            <th>&lt; 30m</th>
            <th>&lt; 1h</th>
            <th>&lt; 3h</th>
            <th>&lt; 12h</th>
            <th>&gt;= 12h</th>
        </tr>
        <?php foreach($stats as $stat): ?>
            <tr>
                <th><?php echo $stat->transactionFee; ?></th>
                <?php foreach($stat->feeDistribution as $k => $percent): ?>
                    <?php
                    $highlight = $optimalFee->stat === $stat && $optimalFee->percentIndex === $k
                        ? 'highlight'
                        : '';
                    ?>
                        <td title="<?php echo $percent; ?>%" class="<?php echo $highlight ?>">
                        <?php echo round($percent, 2); ?>%
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
