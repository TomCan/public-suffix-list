<?php

namespace TomCan\PublicSuffixList;

abstract class AbstractPSL implements PSLInterface
{
    /** @var array<string,string[]> */
    protected array $lists = [];

    public function isTld(string $tld): bool
    {
        // trim, but also trim dot
        $tld = trim($tld, " \n\r\t\v\x00.");
        foreach ($this->lists as $list) {
            if (in_array($tld, $list)) {
                return true;
            }
        }

        return false;
    }

    public function getType(string $tld): ?string
    {
        // trim, but also trim dot
        $tld = trim($tld, " \n\r\t\v\x00.");
        foreach ($this->lists as $type => $list) {
            if (in_array($tld, $list)) {
                return $type;
            }
        }

        return null;
    }

    public function getTldOfDomain(string $domain): ?string
    {
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

    /**
     * @return array<string,string[]>
     */
    public function getLists(): array
    {
        return $this->lists;
    }

    /**
     * @return string[]
     */
    public function getFullList(): array
    {
        return array_merge(...array_values($this->lists));
    }
}
