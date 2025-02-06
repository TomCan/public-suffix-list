# PHP Public Suffix List

A PHP consumable version of the DNS top level domains public suffix list from publicsuffix.org.
It contains the actual list, and is updated periodically.

The library also includes a easy way to check for validity, or derive the TLD from a given domain name.

## Installation
You can install the library through composer
```
composer require tomcan/public-suffix-list
```

The ```v1.5.x``` versions of the library support PHP 5.6 and above.

The ```v2.x.y``` versions of the library support PHP 7.4 and above.

## Usage
There are 2 variants of the list. The `PSLIcann` class contains all 'official' TLDs as defined by ICANN.
The 'PSLFull' class also contains private TLDs that are children of the ICANN list, but also allow
registration by users, or even domains of large/popular services like AWS S3.
```
$psl = new PSLIcann();
// check if tld is valid
$valid = $psl->isTld('be'); // -> true
$valid = $psl->isTld('tom.be); // -> false
// get tld of given domain
$tld = $psl->getTldOfDomain('mt.tom.be'); // -> be
$tld = $psl->getTldOfDomain('oh.fc.it'); // -> fc.it

$psl = new PSLFull();
// check if tld is valid
$valid = $psl->isTld('s3.dualstack.ap-east-1.amazonaws.com'); // -> true
// get tld of given domain
$tld = $psl->getTldOfDomain('someappname.3.azurestaticapps.net'); // -> 3.azurestaticapps.net
// get type of given tld
$type = $psl->getType('be'); // -> icann
$type = $psl->getType('3.azurestaticapps.net'); // -> private
```