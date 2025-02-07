<?php

$url = 'https://publicsuffix.org/list/public_suffix_list.dat';
$icann_end = '// ===END ICANN DOMAINS===';

$icann_list = [];
$private_list = [];

$icann = true;
if ($list = file_get_contents($url)) {
    foreach (explode("\n", $list) as $line) {
        if ('' != trim($line) && '//' != substr($line, 0, 2)) {
            // read until first space, convert idn to ascii, make lowercase, and trim including dots, all in one
            $tld = trim(strtolower((string) idn_to_ascii((string) explode(' ', $line, 2)[0])), " \n\r\t\v\x00.");
            if ('' != $tld) {
                if ($icann) {
                    $icann_list[] = $tld;
                } else {
                    $private_list[] = $tld;
                }
            }
        } elseif (trim($line) == $icann_end) {
            // end of ICANN list
            $icann = false;
        }
    }
    // sort lists by inverse label order (eg. tom.be would be compared as be.tom)
    $fn_sort = function ($a, $b) {
        // explode, reverse, implode, compare
        return strcmp(
            implode('.', array_reverse(explode('.', $a))),
            implode('.', array_reverse(explode('.', $b)))
        );
    };
    usort($icann_list, $fn_sort);
    usort($private_list, $fn_sort);

    $meta = [];
    if ($metaContent = file_get_contents(__DIR__.'/meta.txt')) {
        $data = explode("\n", $metaContent);
        foreach ($data as $line) {
            $var = explode('=', $line);
            if (count($var) > 1) {
                $meta[$var[0]] = $var[1];
            }
        }
    } else {
        $meta = [
            'version' => '1.0.0',
            'icann' => '',
            'full' => '',
        ];
    }
    $updated = false;

    // write classes
    $indent = str_repeat(' ', 8);
    $name = 'PSLIcann';
    $values = makeArrayString('icann', $icann_list, $indent)."\n".$indent;
    $content = <<<EOF
    <?php
    
    namespace TomCan\PublicSuffixList;
    
    class $name extends AbstractPSL
    {
        protected array \$lists = [
    $values
        ];
    }
    
    EOF;

    if (sha1($content) != $meta['icann']) {
        $updated = true;
        $meta['icann'] = sha1($content);
        file_put_contents(__DIR__.'/../src/'.$name.'.php', $content);
    }

    $name = 'PSLFull';
    $values = makeArrayString('icann', $icann_list, $indent).",\n".makeArrayString('private', $private_list, $indent);
    $content = <<<EOF
    <?php
    
    namespace TomCan\PublicSuffixList;
    
    class $name extends AbstractPSL
    {
        protected array \$lists = [
    $values
        ];
    }
    
    EOF;

    if (sha1($content) != $meta['full']) {
        $updated = true;
        $meta['full'] = sha1($content);
        file_put_contents(__DIR__.'/../src/'.$name.'.php', $content);
    }

    if ($updated) {
        // version bump
        $version = explode('.', $meta['version']);
        ++$version[count($version) - 1];
        $meta['version'] = implode('.', $version);

        // rewrite meta.txt
        $content = '';
        foreach ($meta as $key => $value) {
            $content .= "$key=$value\n";
        }
        if (false !== file_put_contents(__DIR__.'/meta.txt', $content)) {
            echo 'Updated version of lists have been generated: '.$meta['version'].PHP_EOL;
        } else {
            throw new RuntimeException('Could not save meta data to meta.txt.');
        }
    }
} else {
    throw new RuntimeException('Could not download public prefix list.');
}

/**
 * @param string[] $values
 */
function makeArrayString(string $name, array $values, string $indent = '    '): string
{
    $ownLine = ['br', 'it', 'jp', 'no'];
    $prevChar = $prevPart = '';

    $output = $indent."'".$name."' => [";
    foreach ($values as $value) {
        // get first character of last part of the tld
        $lastPart = array_reverse(explode('.', $value))[0];
        $firstChar = substr($lastPart, 0, 1);
        if ($prevChar != $firstChar || ($prevPart != $lastPart && (in_array($lastPart, $ownLine) || in_array($prevPart, $ownLine)))) {
            $output .= "\n".$indent.'   ';
            $prevChar = $firstChar;
            $prevPart = $lastPart;
        }
        $output .= " '".$value."',";
    }
    $output .= "\n".$indent.']';

    return $output;
}
