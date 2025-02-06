<?php

namespace TomCan\PublicSuffixList;

abstract class AbstractPSL
{
    protected $lists = array();

    public function isTld($tld) {
        // trim, but also trim dot
        $tld = trim($tld, " \n\r\t\v\x00.");
        foreach ($this->lists as $list) {
            if (in_array($tld, $list)) {
                return true;
            }
        }

        return false;
    }

    public function getType($tld) {
        // trim, but also trim dot
        $tld = trim($tld, " \n\r\t\v\x00.");
        foreach ($this->lists as $type => $list) {
            if (in_array($tld, $list)) {
                return $type;
            }
        }

        return null;
    }

    public function getTldOfDomain($domain) {
        // trim, but also trim dot
        $domain = trim($domain, " \n\r\t\v\x00.");
        while ('' !== $domain) {
            if ($this->isTld($domain)) {
                return $domain;
            } else {
                $offset = strpos($domain, '.');
                if (false === $offset) {
                    $domain = '';
                } else {
                    $domain = substr($domain, $offset + 1);
                }
            }
        }

        return null;
    }

    public function getLists() {
        return $this->lists;
    }

    public function getFullList() {
        $ret = array();
        foreach ($this->lists as $list) {
            $ret = array_merge($ret, $list);
        }

        return $ret;
    }
}