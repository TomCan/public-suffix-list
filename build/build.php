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
                    $icann_list[] = trim($line);
                } else {
                    $private_list[] = trim($line);
                }
            }
        } elseif (trim($line) == $icann_end) {
            // end of ICANN list
            $icann = false;
        }
    }
    // sort lists
    sort($icann_list);
    sort($private_list);

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
    $prev = '';

    $output = $indent."'".$name."' => [";
    foreach ($values as $value) {
        if ($prev != substr($value, 0, 1)) {
            $output .= "\n".$indent.'   ';
            $prev = substr($value, 0, 1);
        }
        $output .= " '".$value."',";
    }
    $output .= "\n".$indent.']';

    return $output;
}
