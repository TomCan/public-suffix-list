<?php

namespace TomCan\PublicSuffixList;

interface PSLInterface
{
    public function isTld(string $tld): bool;

    public function getType(string $tld): ?string;

    public function getTldOfDomain(string $domain): ?string;

    /**
     * @return array<string,string[]>
     */
    public function getLists(): array;

    /**
     * @return string[]
     */
    public function getFullList(): array;
}
