<?php
namespace BtcCom;

final class Parser {
    /**
     * @return Stat[]
     */
    public function parseStats($html) {
        $html = $this->fixSpecialChars($html);
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $rows = $crawler->filter('.stats_table_row.row_select');

        if (count($rows) === 0) {
            throw new \Exception("Couldn't find the table with stats");
        }

        return $rows->each(function($row) {
            $children = $row->children();

            $stat = new Stat();
            $stat->transactionFee = $this->toText($children->eq(1));
            $stat->feeDistribution = $this->extractFeeDistribution($children->eq(2));
            $stat->isOptimal = false;

            return $stat;
        });
    }

    private function toText($node) {
        return trim($node->text());
    }

    private function extractFeeDistribution($parent) {
        $distribution = $parent->children()->children()->each(function($node) {
            $style = $node->attr('style');
            $found = preg_match('/width:\s*(.*)%/', $style, $matches);

            if (!$found) {
                throw new \Exception("Couldn't parse the width. The style was '$style'");
            }

            return (float) trim($matches[1]);
        });

        if (($c = count($distribution)) !== ($r = 6)) {
            throw new \Exception("The number of distributed fee values is $c, but $r is expected");
        }

        return $distribution;
    }

    private function fixSpecialChars($html) {
        return str_replace('< ', '&lt;', $html);
    }
}
