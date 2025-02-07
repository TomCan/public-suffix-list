<?php

namespace TomCan\PublicSuffixList;

abstract class AbstractPSL implements PSLInterface
{
    /** @var array<string,string[]> */
    protected array $lists = [];

    public function isTld(string $tld): bool
    {
        if ($match = $this->getMatch($tld)) {
            if ('!' !== substr($match['match'], 0, 1)) {
                return true;
            }
        }

        return false;
    }

    public function getType(string $tld): ?string
    {
        if ($match = $this->getMatch($tld)) {
            if ('!' !== substr($match['match'], 0, 1)) {
                return $match['type'];
            }
        }

        return null;
    }

    /**
     * @return array{'type': string, 'match': string, 'value': string}|null
     */
    private function getMatch(string $tld): ?array
    {
        // sanetize tld
        $tld = $this->sanetizeTld($tld);
        foreach ($this->lists as $type => $list) {
            if (in_array('!'.$tld, $list)) {
                // exact exclusion
                return ['type' => $type, 'match' => '!'.$tld, 'value' => $tld];
            } elseif (in_array($tld, $list)) {
                // exact match
                return ['type' => $type, 'match' => $tld, 'value' => $tld];
            } else {
                // try wildcard matching, replace first label with *
                if (false !== strpos($tld, '.')) {
                    $wild_tld = '*.' . substr($tld, strpos($tld, '.') + 1);
                } else {
                    $wild_tld = '*';
                }

                if (in_array('!' . $wild_tld, $list)) {
                    // wildcard exclusion
                    return ['type' => $type, 'match' => '!' . $wild_tld, 'value' => $tld];
                } elseif (in_array($wild_tld, $list)) {
                    // wildcard match
                    return ['type' => $type, 'match' => $wild_tld, 'value' => $tld];
                }
            }
        }

        return null;
    }

    public function getTldOfDomain(string $domain): ?string
    {
        // sanetize the domain
        $domain = $this->sanetizeTld($domain);
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

    private function sanetizeTld(string $tld): string
    {
        return strtolower(
            (string) idn_to_ascii(
                trim(
                    (string) explode(
                        ' ',
                        $tld, 2)[0],
                    " \n\r\t\v\x00.")
            )
        );
    }
}
